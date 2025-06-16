document.addEventListener("DOMContentLoaded", () => {
  loadTab("films");

  document.querySelectorAll("[data-tab]").forEach(tab => {
    tab.addEventListener("click", (e) => {
      document.querySelectorAll(".nav-link").forEach(el => el.classList.remove("active"));
      e.target.classList.add("active");
      loadTab(e.target.dataset.tab);
    });
  });
});

const TMDB_BEARER = 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJhYzhmMzliYWFiNThlOGZjMWU1MzU2ZmExMTY0NjE3NyIsIm5iZiI6MTc0ODg2NzkxNC41NTEsInN1YiI6IjY4M2Q5YjRhNGU4ODljZjA3NjY4OWQyMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.yZegMUEuDzZ2DgNqy_uI6dwrWpLjItOOcmGbHhaqrDI';

/* ---------- Ajout du film via TMDb ---------- */
async function addFilmViaTmdb(tmdbId) {
  if (!tmdbId) return;

  try {
    const res = await fetch('php/add_film.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ tmdb_id: tmdbId })
    });

    const data = await res.json();

    if (data.success) {
      // Ferme le modal s'il existe
      const modalEl = document.getElementById('addFilmModal');
      if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.hide();
      }
      // Recharge la liste des films
      loadTab('films');
    } else {
      alert(data.error || 'Erreur lors de l\'ajout du film.');
    }
  } catch (err) {
    console.error('Erreur réseau :', err);
  }
}

async function searchTmdb(query) {
  const res = await fetch(
    `https://api.themoviedb.org/3/search/movie?language=fr-FR&query=${encodeURIComponent(query)}`,
    {
      method: 'GET',
      headers: {
        Authorization: TMDB_BEARER,
        Accept: 'application/json'
      }
    }
  );
  const data = await res.json();
  return data.results || [];
}

/* ---------- Suggestions TMDb (3 résultats) ---------- */
const tmdbInput   = document.getElementById('tmdbQuery');   // champ de saisie
const tmdbResults = document.getElementById('tmdbResults'); // <ul> pour afficher les résultats
let selectedTmdbId = null;                                  // id du film choisi
let debounceTimer  = null;

if (tmdbInput && tmdbResults) {

  // Recherche déclenchée au fil de la frappe (debounce 300 ms)
  tmdbInput.addEventListener('input', e => {
    const q = e.target.value.trim();
    clearTimeout(debounceTimer);
    if (q.length < 2) {
      tmdbResults.innerHTML = '';
      selectedTmdbId = null;
      return;
    }
    debounceTimer = setTimeout(async () => {
      const results = await searchTmdb(q);
      const top3 = results.slice(0, 3);

      tmdbResults.innerHTML = top3.length
        ? top3.map(f => {
            const poster = f.poster_path
              ? `https://image.tmdb.org/t/p/w92${f.poster_path}`
              : 'https://via.placeholder.com/92x138?text=No+Image';
            return `
              <li class="list-group-item list-group-item-action d-flex align-items-center gap-3"
                  data-id="${f.id}">
                <img src="${poster}" alt="" style="width:50px;height:auto;border-radius:4px;">
                <span>${f.title} (${(f.release_date || '').slice(0, 4) || '—'})</span>
              </li>`;
          }).join('')
        : '<li class="list-group-item">Aucun résultat</li>';

      // Remove previous buttons if they exist
      const existingButtons = document.getElementById('tmdbButtons');
      if (existingButtons) existingButtons.remove();

      // Append buttons after the <ul>
      const buttonGroup = document.createElement('div');
      buttonGroup.id = 'tmdbButtons';
      buttonGroup.className = 'mt-2 d-flex justify-content-end gap-2';
      buttonGroup.innerHTML = `
        <button id="confirmTmdbBtn" class="btn btn-primary" disabled>Ajouter à la base</button>
        <button id="manualFallbackBtn" class="btn btn-secondary">Modifier manuellement</button>
      `;
      tmdbResults.insertAdjacentElement('afterend', buttonGroup);

      // Bind button event listeners
      bindTmdbButtons();

      selectedTmdbId = null; // reset sélection
    }, 300);
  });

  // Sélection d'un film : sur clic on marque "active"
  tmdbResults.addEventListener('click', e => {
    const li = e.target.closest('li[data-id]');
    if (!li) return;
    selectedTmdbId = li.dataset.id;
    // visuel : met la classe active sur l'élément choisi
    [...tmdbResults.children].forEach(el => el.classList.remove('active'));
    li.classList.add('active');
    // Enable the confirm button if present
    document.getElementById('confirmTmdbBtn')?.removeAttribute('disabled');
  });
}

/* -- Fonction utilitaire pour récupérer le film choisi lors du submit -- */
function getSelectedTmdbId() {
  return selectedTmdbId;
}

function loadTab(tabName) {
  const tabContent = document.getElementById("tab-content");
  tabContent.innerHTML = "<p>Chargement...</p>";

  fetch(`php/get_${tabName}.php`)
    .then(res => res.json())
    .then(data => {
      if (tabName === "films") renderFilms(data);
      if (tabName === "playsets") renderPlaysets(data);
      if (tabName === "users") renderUsers(data);
    })
    .catch(err => tabContent.innerHTML = "<p>Erreur de chargement.</p>");
}

function renderFilms(films) {
  const tab = document.getElementById("tab-content");
  tab.innerHTML = `
    <h2>🎥 Films</h2>
    <table class="table table-bordered">
      <thead><tr><th>ID</th><th>Titre</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
        ${films.map(f => `
          <tr>
            <td>${f.ID}</td>
            <td>${f.Name || '(aucun nom)'}</td>
            <td>${f.Date || '—'}</td>
            <td><button class="btn btn-danger btn-sm" onclick="deleteFilm(${f.ID})">🗑️ Supprimer</button></td>
          </tr>
        `).join("")}
      </tbody>
    </table>
  `;
}

function deleteFilm(id) {
  if (!confirm("Supprimer ce film ?")) return;
  fetch(`php/delete_film.php?id=${id}`)
    .then(() => loadTab("films"))
    .catch(() => alert("Erreur lors de la suppression"));
}
// Function to bind TMDb suggestion buttons
function bindTmdbButtons() {
  document.getElementById('confirmTmdbBtn')?.addEventListener('click', () => {
    if (selectedTmdbId) {
      addFilmViaTmdb(selectedTmdbId);
    }
  });

  document.getElementById('manualFallbackBtn')?.addEventListener('click', () => {
    document.getElementById('useTmdb').checked = false;
    document.getElementById('tmdbSearchArea').classList.add('d-none');
    document.getElementById('manualArea').classList.remove('d-none');
  });
}
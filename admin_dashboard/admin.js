document.addEventListener("DOMContentLoaded", () => {
  loadTab(currentTab);

  document.querySelectorAll("[data-tab]").forEach(tab => {
    tab.addEventListener("click", (e) => {
      document.querySelectorAll(".nav-link").forEach(el => el.classList.remove("active"));
      e.target.classList.add("active");
      currentTab = e.target.dataset.tab;
      loadTab(e.target.dataset.tab);
    });
  });
});

let currentTab = 'films';

let filmsCache = [];
let filmCurrentPage = 1;
const filmPageSize = 10;

let seriesCache = [];
let seriesCurrentPage = 1;
const seriesPageSize = 10;

let usersCache = [];
let userCurrentPage = 1;
const userPageSize = 10;

document.getElementById('addFilmBtn')?.addEventListener('click', () => {
  currentTab = 'films';
  const modal = new bootstrap.Modal(document.getElementById('addFilmModal'));
  modal.show();
});
document.getElementById('addSeriesBtn')?.addEventListener('click', () => {
  currentTab = 'series';
  const modal = new bootstrap.Modal(document.getElementById('addSeriesModal'));
  modal.show();
});

const TMDB_BEARER = 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJhYzhmMzliYWFiNThlOGZjMWU1MzU2ZmExMTY0NjE3NyIsIm5iZiI6MTc0ODg2NzkxNC41NTEsInN1YiI6IjY4M2Q5YjRhNGU4ODljZjA3NjY4OWQyMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.yZegMUEuDzZ2DgNqy_uI6dwrWpLjItOOcmGbHhaqrDI';

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
      const modalEl = document.getElementById('addFilmModal');
      if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.hide();
        document.body.classList.remove('modal-open');
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
      }
      loadTab('films');
    } else {
      alert(data.error || 'Erreur lors de l\'ajout du film.');
    }
  } catch (err) {
    console.error('Erreur réseau :', err);
  }
}

async function addSeriesViaTmdb(tmdbId) {
  if (!tmdbId) return;
  try {
    const res = await fetch('php/add_series.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ tmdb_id: tmdbId })
    });
    const data = await res.json();
    if (data.success) {
      const seriesModalEl = document.getElementById('addSeriesModal');
      if (seriesModalEl) {
        const seriesModalInstance = bootstrap.Modal.getInstance(seriesModalEl) || new bootstrap.Modal(seriesModalEl);
        seriesModalInstance.hide();
      }
      document.body.classList.remove('modal-open');
      document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    } else {
      alert(data.error || 'Erreur lors de l\'ajout de la série.');
    }
    loadTab('series');
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

async function searchTmdbSeries(query) {
  const res = await fetch(
    `https://api.themoviedb.org/3/search/tv?language=fr-FR&query=${encodeURIComponent(query)}`,
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

const tmdbInput   = document.getElementById('tmdbQuery');
const tmdbResults = document.getElementById('tmdbResults');
let selectedTmdbId = null;
let debounceTimer  = null;

if (tmdbInput && tmdbResults) {

  tmdbInput.addEventListener('input', e => {
    const q = e.target.value.trim();
    clearTimeout(debounceTimer);
    if (q.length < 2) {
      tmdbResults.innerHTML = '';
      selectedTmdbId = null;
      return;
    }
    debounceTimer = setTimeout(async () => {
      const results = currentTab === 'series' ? await searchTmdbSeries(q) : await searchTmdb(q);
      const top3 = results.slice(0, 3);

      tmdbResults.innerHTML = top3.length
        ? top3.map(f => {
            const poster = f.poster_path
              ? `https://image.tmdb.org/t/p/w92${f.poster_path}`
              : 'https://via.placeholder.com/92x138?text=No+Image';
            return `
              <li class="list-group-item list-group-item-action d-flex align-items-start gap-3"
                  data-id="${f.id}">
                <img src="${poster}" alt="" style="width:50px;height:auto;border-radius:4px;">
                <div class="flex-grow-1">
                  <div>${f.title || f.name} (${(f.release_date || f.first_air_date || '').slice(0, 4) || '—'})</div>
                  <small class="text-muted">TMDb ID&nbsp;: ${f.id}</small>
                </div>
              </li>`;
          }).join('')
        : '<li class="list-group-item">Aucun résultat</li>';

      const existingButtons = document.getElementById('tmdbButtons');
      if (existingButtons) existingButtons.remove();

      const buttonGroup = document.createElement('div');
      buttonGroup.id = 'tmdbButtons';
      buttonGroup.className = 'mt-2 d-flex justify-content-end gap-2';
      buttonGroup.innerHTML = `
        <button id="confirmTmdbBtn" class="btn btn-primary" type="button" disabled>Ajouter à la base</button>
        <button id="manualFallbackBtn" class="btn btn-secondary" type="button">Modifier manuellement</button>
      `;
      tmdbResults.insertAdjacentElement('afterend', buttonGroup);

      bindTmdbButtons();

      document.getElementById('tmdbForm')?.addEventListener('submit', (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
      });

      selectedTmdbId = null;
    }, 300);
  });

  tmdbResults.addEventListener('click', e => {
    const li = e.target.closest('li[data-id]');
    if (!li) return;
    selectedTmdbId = li.dataset.id;
    [...tmdbResults.children].forEach(el => el.classList.remove('active'));
    li.classList.add('active');
    document.getElementById('confirmTmdbBtn')?.removeAttribute('disabled');
  });
}

const tmdbInputSeries   = document.getElementById('tmdbQuerySeries');
const tmdbResultsSeries = document.getElementById('tmdbResultsSeries');
let debounceTimerSeries = null;

if (tmdbInputSeries && tmdbResultsSeries) {
  tmdbInputSeries.addEventListener('input', e => {
    const q = e.target.value.trim();
    clearTimeout(debounceTimerSeries);
    if (q.length < 2) {
      tmdbResultsSeries.innerHTML = '';
      selectedTmdbId = null;
      return;
    }
    debounceTimerSeries = setTimeout(async () => {
      const results = await searchTmdbSeries(q);
      const top3 = results.slice(0, 3);

      tmdbResultsSeries.innerHTML = top3.length
        ? top3.map(f => {
            const poster = f.poster_path
              ? `https://image.tmdb.org/t/p/w92${f.poster_path}`
              : 'https://via.placeholder.com/92x138?text=No+Image';
            return `
              <li class="list-group-item list-group-item-action d-flex align-items-start gap-3"
                  data-id="${f.id}">
                <img src="${poster}" alt="" style="width:50px;height:auto;border-radius:4px;">
                <div class="flex-grow-1">
                  <div>${f.name} (${(f.first_air_date || '').slice(0, 4) || '—'})</div>
                  <small class="text-muted">TMDb ID&nbsp;: ${f.id}</small>
                </div>
              </li>`;
          }).join('')
        : '<li class="list-group-item">Aucun résultat</li>';

      const existingButtonsSeries = document.getElementById('tmdbButtons');
      if (existingButtonsSeries) existingButtonsSeries.remove();

      const buttonGroupSeries = document.createElement('div');
      buttonGroupSeries.id = 'tmdbButtons';
      buttonGroupSeries.className = 'mt-2 d-flex justify-content-end gap-2';
      buttonGroupSeries.innerHTML = `
        <button id="confirmTmdbBtn" class="btn btn-primary" type="button" disabled>Ajouter à la base</button>
        <button id="manualFallbackBtn" class="btn btn-secondary" type="button">Modifier manuellement</button>
      `;
      tmdbResultsSeries.insertAdjacentElement('afterend', buttonGroupSeries);

      bindTmdbButtons();

      document.getElementById('tmdbFormSeries')?.addEventListener('submit', ev => {
        ev.preventDefault();
        ev.stopPropagation();
      });

      selectedTmdbId = null;
    }, 300);
  });

  tmdbResultsSeries.addEventListener('click', e => {
    const li = e.target.closest('li[data-id]');
    if (!li) return;
    selectedTmdbId = li.dataset.id;
    [...tmdbResultsSeries.children].forEach(el => el.classList.remove('active'));
    li.classList.add('active');
    document.getElementById('confirmTmdbBtn')?.removeAttribute('disabled');
  });
}

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
      if (tabName === "series") renderSeries(data);
    })
    .catch(err => tabContent.innerHTML = "<p>Erreur de chargement.</p>");
}

function renderFilms(films) {
  filmsCache = films;
  const total = filmsCache.length;
  const totalPages = Math.ceil(total / filmPageSize);
  if (filmCurrentPage > totalPages) filmCurrentPage = totalPages || 1;
  const start = (filmCurrentPage - 1) * filmPageSize;
  const pageFilms = filmsCache.slice(start, start + filmPageSize);

  const tab = document.getElementById("tab-content");
  tab.innerHTML = `
    <h2>🎥 Films</h2>
    <table class="table table-bordered">
      <thead><tr><th>ID</th><th>Titre</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
        ${pageFilms.map(f => `
          <tr>
            <td>${f.ID}</td>
            <td>${f.Name || '(aucun nom)'}</td>
            <td>${f.Date || '—'}</td>
            <td>
              <button class="btn btn-sm btn-outline-primary me-2" onclick="openEditFilmModal(${f.ID})">✏️ Modifier</button>
              <button class="btn btn-sm btn-danger" onclick="deleteFilm(${f.ID})">🗑️ Supprimer</button>
            </td>
          </tr>
        `).join("")}
      </tbody>
    </table>
    <nav>
      <ul class="pagination justify-content-center mt-2">
        <li class="page-item ${filmCurrentPage === 1 ? 'disabled' : ''}">
          <a class="page-link" href="#" onclick="changeFilmPage(${filmCurrentPage - 1}); return false;">Précédent</a>
        </li>
        <li class="page-item disabled">
          <span class="page-link">Page ${filmCurrentPage} / ${totalPages}</span>
        </li>
        <li class="page-item ${filmCurrentPage === totalPages ? 'disabled' : ''}">
          <a class="page-link" href="#" onclick="changeFilmPage(${filmCurrentPage + 1}); return false;">Suivant</a>
        </li>
      </ul>
    </nav>
  `;
}

function changeFilmPage(page) {
  if (page < 1) return;
  const totalPages = Math.ceil(filmsCache.length / filmPageSize);
  if (page > totalPages) return;
  filmCurrentPage = page;
  renderFilms(filmsCache);
}

function renderSeries(series) {
  seriesCache = series;
  const totalS = seriesCache.length;
  const totalPagesS = Math.ceil(totalS / seriesPageSize);
  if (seriesCurrentPage > totalPagesS) seriesCurrentPage = totalPagesS || 1;
  const startS = (seriesCurrentPage - 1) * seriesPageSize;
  const pageSeries = seriesCache.slice(startS, startS + seriesPageSize);

  const tab = document.getElementById("tab-content");
  tab.innerHTML =
    `
    <h2>📺 Séries</h2>
    <table class="table table-bordered">
      <thead><tr><th>ID</th><th>Titre</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
        ${pageSeries.map(s => `
          <tr>
            <td>${s.ID}</td>
            <td>${s.Name || '(aucun titre)'}</td>
            <td>${s.Date || '—'}</td>
            <td>
              <button class="btn btn-sm btn-outline-primary me-2" onclick="openEditSeriesModal(${s.ID})">✏️ Modifier</button>
              <button class="btn btn-sm btn-danger" onclick="deleteSeries(${s.ID})">🗑️ Supprimer</button>
            </td>
          </tr>
        `).join("")}
      </tbody>
    </table>
    `
    + `
      <nav>
        <ul class="pagination justify-content-center mt-2">
          <li class="page-item ${seriesCurrentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changeSeriesPage(${seriesCurrentPage - 1}); return false;">Précédent</a>
          </li>
          <li class="page-item disabled">
            <span class="page-link">Page ${seriesCurrentPage} / ${totalPagesS}</span>
          </li>
          <li class="page-item ${seriesCurrentPage === totalPagesS ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changeSeriesPage(${seriesCurrentPage + 1}); return false;">Suivant</a>
          </li>
        </ul>
      </nav>
    `;
}

function changeSeriesPage(page) {
  if (page < 1) return;
  const totalPagesS = Math.ceil(seriesCache.length / seriesPageSize);
  if (page > totalPagesS) return;
  seriesCurrentPage = page;
  renderSeries(seriesCache);
}

/* ---------- Affichage paginé des utilisateurs (Users) ---------- */
function renderUsers(data) {
  // data may be array or { users: [...] }
  const usersArray = Array.isArray(data) ? data : (data.users || []);
  usersCache = usersArray;
  const total = usersCache.length;
  const totalPages = Math.ceil(total / userPageSize);
  if (userCurrentPage > totalPages) userCurrentPage = totalPages || 1;
  const start = (userCurrentPage - 1) * userPageSize;
  const pageUsers = usersCache.slice(start, start + userPageSize);

  const tab = document.getElementById("tab-content");
  tab.innerHTML = `
    <h2>👥 Utilisateurs</h2>
    <table class="table table-bordered">
      <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Grade</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
        ${pageUsers.map(u => `
          <tr>
            <td>${u.ID}</td>
            <td>${u.Username}</td>
            <td>${u.Mail}</td>
            <td>${u.Grade}</td>
            <td>${u.Date}</td>
            <td>
              <button class="btn btn-danger btn-sm" onclick="deleteUser(${u.ID})">🗑️ Supprimer</button>
            </td>
          </tr>
        `).join("")}
      </tbody>
    </table>
    <nav>
      <ul class="pagination justify-content-center mt-2">
        <li class="page-item ${userCurrentPage === 1 ? 'disabled' : ''}">
          <a class="page-link" href="#" onclick="changeUserPage(${userCurrentPage - 1}); return false;">Précédent</a>
        </li>
        <li class="page-item disabled">
          <span class="page-link">Page ${userCurrentPage} / ${totalPages}</span>
        </li>
        <li class="page-item ${userCurrentPage === totalPages ? 'disabled' : ''}">
          <a class="page-link" href="#" onclick="changeUserPage(${userCurrentPage + 1}); return false;">Suivant</a>
        </li>
      </ul>
    </nav>
  `;
}

function changeUserPage(page) {
  if (page < 1) return;
  const totalPages = Math.ceil(usersCache.length / userPageSize);
  if (page > totalPages) return;
  userCurrentPage = page;
  renderUsers(usersCache);
}

function deleteUser(id) {
  if (!confirm("Supprimer cet utilisateur ?")) return;
  fetch(`php/delete_user.php?id=${id}`)
    .then(() => loadTab("users"))
    .catch(() => alert("Erreur lors de la suppression de l'utilisateur"));
}

function deleteFilm(id) {
  if (!confirm("Supprimer ce film ?")) return;
  fetch(`php/delete_film.php?id=${id}`)
    .then(() => loadTab("films"))
    .catch(() => alert("Erreur lors de la suppression"));
}

function deleteSeries(id) {
  if (!confirm("Supprimer cette série ?")) return;
  fetch(`php/delete_series.php?id=${id}`)
    .then(() => loadTab('series'))
    .catch(() => alert("Erreur lors de la suppression de la série"));
}
// Function to bind TMDb suggestion buttons
function bindTmdbButtons() {
  document.getElementById('confirmTmdbBtn')?.addEventListener('click', () => {
    if (!selectedTmdbId) return;
    if (currentTab === 'films') addFilmViaTmdb(selectedTmdbId);
    else if (currentTab === 'series') addSeriesViaTmdb(selectedTmdbId);
  });

  document.getElementById('manualFallbackBtn')?.addEventListener('click', () => {
    document.getElementById('useTmdb').checked = false;
    document.getElementById('tmdbSearchArea').classList.add('d-none');
    document.getElementById('manualArea').classList.remove('d-none');
  });
}

// --- Edition d'un film ---
async function openEditFilmModal(id) {
  try {
    const res = await fetch(`php/edit_film.php?id=${id}`);
    const text = await res.text();  // read body once

    if (!res.ok) {
      console.error('edit_film.php HTTP error', res.status, text);
      alert('Erreur serveur lors de la récupération du film.');
      return;
    }

    let data;
    try {
      data = JSON.parse(text);
    } catch (parseErr) {
      console.error('Invalid JSON from edit_film.php:', parseErr, text);
      alert('Réponse invalide du serveur.');
      return;
    }

    if (!data.success) {
      alert(data.error || 'Impossible de récupérer les informations du film.');
      return;
    }

    const film = data.film;
    document.getElementById('editFilmId').value = film.ID;
    document.getElementById('editFilmName').value = film.Name || '';
    document.getElementById('editFilmDescription').value = film.Description || '';
    document.getElementById('editFilmDate').value = film.Date || '';
    const modal = new bootstrap.Modal(document.getElementById('editFilmModal'));
    modal.show();
  } catch (err) {
    console.error('Erreur réseau :', err);
    alert('Erreur lors de la récupération du film.');
  }
}

// --- Édition d'une série ---
async function openEditSeriesModal(id) {
  try {
    // Appel GET pour récupérer les données de la série
    const res = await fetch(`php/edit_series.php?id=${id}`);
    const text = await res.text();  // on lit le corps une seule fois

    if (!res.ok) {
      console.error('edit_series.php HTTP error', res.status, text);
      alert('Erreur serveur lors de la récupération de la série.');
      return;
    }

    let data;
    try {
      data = JSON.parse(text);
    } catch (parseErr) {
      console.error('Invalid JSON from edit_series.php:', parseErr, text);
      alert('Réponse invalide du serveur.');
      return;
    }

    if (!data.success) {
      alert(data.error || 'Impossible de récupérer les informations de la série.');
      return;
    }

    const series = data.series;
    // Remplissage du formulaire
    document.getElementById('editSeriesId').value = series.ID;
    document.getElementById('editSeriesName').value = series.Name || '';
    document.getElementById('editSeriesDescription').value = series.Description || '';
    document.getElementById('editSeriesDate').value = series.Date || '';

    // Affichage du modal
    const modal = new bootstrap.Modal(document.getElementById('editSeriesModal'));
    modal.show();
  } catch (err) {
    console.error('Erreur réseau :', err);
    alert('Erreur lors de la récupération de la série.');
  }
}

// Handle edit form submission
document.getElementById('editFilmForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const id = document.getElementById('editFilmId').value;
  const name = document.getElementById('editFilmName').value.trim();
  const description = document.getElementById('editFilmDescription').value.trim();
  const date = document.getElementById('editFilmDate').value;
  try {
    const res = await fetch('php/edit_film.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, name, description, date })
    });
    const data = await res.json();
    if (data.success) {
      // Hide modal and clean up backdrop
      const modalEl = document.getElementById('editFilmModal');
      const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
      modalInstance.hide();
      document.body.classList.remove('modal-open');
      document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
      // Reload films list
      loadTab('films');
    } else {
      alert(data.error || 'Erreur lors de la modification du film.');
    }
  } catch (err) {
    console.error('Erreur réseau :', err);
  }
});

// Handle edit series form submission
document.getElementById('editSeriesForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const id = document.getElementById('editSeriesId').value;
  const name = document.getElementById('editSeriesName').value.trim();
  const description = document.getElementById('editSeriesDescription').value.trim();
  const date = document.getElementById('editSeriesDate').value;
  try {
    const res = await fetch('php/edit_series.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, name, description, date })
    });
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch {
      alert('Réponse invalide du serveur.');
      return;
    }
    if (data.success) {
      // Hide modal and clean up backdrop
      const modalEl = document.getElementById('editSeriesModal');
      const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
      modalInstance.hide();
      document.body.classList.remove('modal-open');
      document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
      // Reload series list
      loadTab('series');
    } else {
      alert(data.error || 'Erreur lors de la modification de la série.');
    }
  } catch (err) {
    console.error('Erreur réseau :', err);
    alert('Erreur réseau lors de la modification de la série.');
  }
});

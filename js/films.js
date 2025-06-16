let movieToAdd = null;
let currentPage = 1;
let isFetching = false;
let hasMorePages = true;

let currentSearchQuery = '';

const TMDB_PARAMS = {
  include_adult: false,
  include_video: false,
  language: 'fr-FR',
  page: 1,
  sort_by: 'popularity.desc'
};

function getTmdbUrl(type = 'movie') {
  return `https://api.themoviedb.org/3/discover/${type}?${new URLSearchParams(TMDB_PARAMS).toString()}`;
}

const OPTIONS = {
  method: 'GET',
  headers: {
    accept: 'application/json',
    Authorization: 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJhYzhmMzliYWFiNThlOGZjMWU1MzU2ZmExMTY0NjE3NyIsIm5iZiI6MTc0ODg2NzkxNC41NTEsInN1YiI6IjY4M2Q5YjRhNGU4ODljZjA3NjY4OWQyMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.yZegMUEuDzZ2DgNqy_uI6dwrWpLjItOOcmGbHhaqrDI'
  }
};

const IMG_BASE_URL = 'https://image.tmdb.org/t/p/w500';

const cardContainer = document.getElementById('cardContainer');

async function fetchPopularMovies() {
  if (isFetching || !hasMorePages || currentSearchQuery.trim()) return;

  isFetching = true;

  try {
    const response = await fetch(`./php/get_movies.php?page=${currentPage}&type=${currentType}`);
    if (!response.ok) throw new Error('Erreur de récupération depuis la base de données');

    const data = await response.json();
    const movies = data.map(movie => ({
      id: movie.TMDB_ID,
      title: movie.Name,
      overview: movie.Description || '',
      vote_average: movie.Rating || 'N/A',
      release_date: movie.Date,
      poster_path: movie.Poster_Img,
      backdrop_path: movie.Banner_Img
    }));

    renderMovieCards(movies);

    currentPage++;
    hasMorePages = data.length >= 20;
  } catch (err) {
    console.error('Erreur fetchPopularMovies (BDD):', err);
  } finally {
    isFetching = false;
  }
}

async function fetchSearchResults(query) {
  if (!query.trim()) {
    currentSearchQuery = '';
    currentPage = 1;
    hasMorePages = true;
    cardContainer.innerHTML = '';
    fetchPopularMovies();
    return;
  }

  isFetching = true;

  try {
    const response = await fetch(`./php/get_movies.php?search=${encodeURIComponent(query)}&page=${currentPage}&type=${currentType}`);
    if (!response.ok) throw new Error('Erreur de recherche');

    const data = await response.json();
    const movies = data.map(movie => ({
      id: movie.TMDB_ID,
      title: movie.Name,
      overview: movie.Description || '',
      vote_average: movie.Rating || 'N/A',
      release_date: movie.Date,
      poster_path: movie.Poster_Img,
      backdrop_path: movie.Banner_Img
    }));

    if (currentPage === 1) cardContainer.innerHTML = '';
    renderMovieCards(movies);

    if (data.length < 20) {
      hasMorePages = false;
    } else {
      currentPage++;
    }
  } catch (e) {
    console.error("Erreur recherche via BDD :", e);
  } finally {
    isFetching = false;
  }
}

async function fetchCredits(id) {
  const url = `https://api.themoviedb.org/3/${currentType}/${id}/credits`;
  const res = await fetch(url, OPTIONS);
  if (!res.ok) throw new Error("Erreur lors du chargement des crédits");
  const data = await res.json();
  return data.cast.slice(0, 10);
}

async function fetchProviders(id) {
  const url = `https://api.themoviedb.org/3/${currentType}/${id}/watch/providers`;
  const res = await fetch(url, OPTIONS);
  if (!res.ok) throw new Error("Erreur lors du chargement des plateformes");
  const data = await res.json();
  return data.results['FR'];
}

async function loadUserPlaysets() {
  const select = document.getElementById('playsetSelect');
  select.innerHTML = '<option value="">Sélectionne un playset</option>';

  try {
    const res = await fetch(`./php/playset_bdd_access.php?action=get&user_id=1&tmdb_id=${movieToAdd.id}&type=${movieToAdd.type}`);
    const data = await res.json();

    if (Array.isArray(data.playsets)) {
      data.playsets.forEach(ps => {
        const option = document.createElement('option');
        option.value = ps.ID;
        option.textContent = ps.Name + (ps.contains ? ' (déjà ajouté)' : '');
        if (ps.contains) {
          option.disabled = true;
          option.style.color = 'gray';
        }
        select.appendChild(option);
      });
    } else {
      select.innerHTML = '<option disabled>Erreur de réponse du serveur</option>';
    }
  } catch (e) {
    console.error('Erreur chargement playsets:', e);
    select.innerHTML = '<option disabled>Erreur de chargement</option>';
  }
}

const confirmBtn = document.getElementById('confirmAddToPlaysetBtn');
const playsetSelect = document.getElementById('playsetSelect');
const newPlaysetInput = document.getElementById('newPlaysetName');

function updateBtnState() {
  confirmBtn.disabled = !(
    (playsetSelect.value && !playsetSelect.options[playsetSelect.selectedIndex].disabled)
    || newPlaysetInput.value.trim()
  );
}
playsetSelect.addEventListener('change', updateBtnState);
newPlaysetInput.addEventListener('input', updateBtnState);
updateBtnState();

document.getElementById('confirmAddToPlaysetBtn').addEventListener('click', async () => {
  const select = document.getElementById('playsetSelect');
  const playsetId = select.value;

  if (!playsetId) {
    alert("Tu dois sélectionner un playset.");
    return;
  }

  try {
    await fetch('./php/playset_bdd_access.php?action=add', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        playset_id: playsetId,
        tmdb_id: movieToAdd.id,
        type: movieToAdd.type
      })
    });

    bootstrap.Modal.getInstance(document.getElementById('addToPlaysetModal')).hide();
    alert(`Ajouté à ton playset avec succès !`);
  } catch (e) {
    alert("Erreur lors de l'ajout au playset");
    console.error(e);
  }
});


function renderMovieCards(movies) {

  movies.forEach(movie => {
    if (!movie.poster_path) return;

    const posterURL = IMG_BASE_URL + movie.poster_path;

    const col = document.createElement('div');
    col.className = 'col-6 col-sm-4 col-md-4 col-lg-3 mb-4';

    const card = document.createElement('div');
    card.className = 'card h-100 shadow-sm position-relative overflow-hidden';

    const img = document.createElement('img');
    img.src = posterURL;
    img.alt = movie.title + ' poster';
    img.className = 'card-img-top';

    const overlay = document.createElement('div');
    overlay.className = 'card-hover-overlay';

    const addButton = document.createElement('button');
    addButton.className = 'btn btn-sm add-button';
    addButton.innerHTML = '<i class="bi bi-bookmark"></i>';
    addButton.title = 'Ajouter au playset';

    const moreButton = document.createElement('button');
    moreButton.className = 'btn more-button w-100';
    moreButton.innerText = 'En savoir plus';

    addButton.addEventListener('click', async () => {
      movieToAdd = {
        id: movie.id,
        title: movie.title || movie.name,
        type: currentType
      };
    
      await loadUserPlaysets();
    
      const playsetModal = new bootstrap.Modal(document.getElementById('addToPlaysetModal'));
      playsetModal.show();
    });

    moreButton.addEventListener('click', async () => {
      document.getElementById('movieModalLabel').textContent = movie.title || movie.name;
      document.getElementById('movieModalOverview').textContent = movie.overview || 'Aucune description disponible.';
      document.getElementById('movieModalRating').textContent = movie.vote_average || 'N/A';
      document.getElementById('movieModalDate').textContent = movie.release_date || movie.first_air_date;
    
      const backdropUrl = movie.backdrop_path 
        ? `https://image.tmdb.org/t/p/w780${movie.backdrop_path}` 
        : 'https://via.placeholder.com/780x439?text=Pas+d\'image';
    
      document.getElementById('movieModalImage').src = backdropUrl;

      try {
        const providers = await fetchProviders(movie.id);
        const container = document.getElementById('movieModalProviders');
        if (!providers) {
          container.innerHTML = "<p>Non disponible en France</p>";
        } else {
          const logos = [
            ...(providers.flatrate || []),
            ...(providers.rent || []),
            ...(providers.buy || [])
          ];
      
          container.innerHTML = logos.length
            ? `<div class="d-flex flex-wrap gap-2 align-items-center">
                 ${logos.map(p => `
                   <div class="provider-logo" title="${p.provider_name}">
                     <img src="https://image.tmdb.org/t/p/w45${p.logo_path}" alt="${p.provider_name}" />
                   </div>
                 `).join('')}
               </div>`
            : "<p>Pas de plateforme connue</p>";
        }
      } catch (e) {
        document.getElementById('movieModalProviders').innerHTML = "<p>Erreur chargement plateformes</p>";
      }
    
      try {
        const cast = await fetchCredits(movie.id);
        const castList = document.getElementById('movieModalCast');
        castList.innerHTML = cast.map(actor => `
          <div class="actor-card text-center">
            <img src="${actor.profile_path ? `https://image.tmdb.org/t/p/w185${actor.profile_path}` : 'https://via.placeholder.com/80x120?text=?'}" alt="${actor.name}" class="actor-img mb-1" />
            <small>${actor.name}</small>
          </div>
        `).join('');
      } catch (e) {
        document.getElementById('movieModalCast').innerHTML = "<p>Acteurs non disponibles</p>";
      }
    
      const modal = new bootstrap.Modal(document.getElementById('movieModal'));
      modal.show();
    });
       
    overlay.appendChild(addButton);
    overlay.appendChild(moreButton);

    card.appendChild(img);
    card.appendChild(overlay);
    col.appendChild(card);
    cardContainer.appendChild(col);

  });
}

let currentType = 'movie';

fetchPopularMovies();

window.addEventListener('scroll', () => {
  const scrollPosition = window.innerHeight + window.scrollY;
  const threshold = document.body.offsetHeight - 300;

  if (scrollPosition >= threshold && !isFetching && hasMorePages && !currentSearchQuery) {
    fetchPopularMovies();
  }
});


const switchButtons = document.querySelectorAll('#contentSwitcher .btn');
switchButtons.forEach(button => {
  button.addEventListener('click', () => {

    switchButtons.forEach(b => b.classList.remove('active'));
    button.classList.add('active');

    currentType = button.dataset.type;

    currentPage = 1;
    hasMorePages = true;
    cardContainer.innerHTML = '';
    currentSearchQuery = '';

    fetchPopularMovies();
  });
});

document.getElementById('searchButton').addEventListener('click', () => {
  const query = document.getElementById('searchInput').value.trim();
  if (!query) return;
  currentPage = 1;
  hasMorePages = true;
  cardContainer.innerHTML = '';
  currentSearchQuery = query;
  fetchSearchResults(query);
});

document.getElementById('searchInput').addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    document.getElementById('searchButton').click();
  }
});

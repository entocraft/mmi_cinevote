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
  try {
    cardContainer.innerHTML = `
      <div class="d-flex justify-content-center w-100 my-2">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Chargement...</span>
        </div>
      </div>
    `;

    const TMDB_URL = getTmdbUrl(currentType);
    const response = await fetch(TMDB_URL, OPTIONS);
    if (!response.ok) throw new Error('Statut HTTP ' + response.status);

    const data = await response.json();
    renderMovieCards(data.results);
  } catch (err) {
    console.error('Erreur fetchPopularMovies:', err);
    cardContainer.innerHTML = `
      <div class="col-12">
        <div class="alert alert-danger" role="alert">
          Impossible de récupérer les ${currentType === 'tv' ? 'séries' : 'films'} populaires.
        </div>
      </div>
    `;
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

function renderMovieCards(movies) {
  cardContainer.innerHTML = '';

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

    // Overlay container
    const overlay = document.createElement('div');
    overlay.className = 'card-hover-overlay';

    // "+" button
    const addButton = document.createElement('button');
    addButton.className = 'btn btn-sm add-button';
    addButton.innerHTML = '<i class="bi bi-bookmark"></i>';
    addButton.title = 'Ajouter au playset';

    // "En savoir plus" button
    const moreButton = document.createElement('button');
    moreButton.className = 'btn more-button w-100';
    moreButton.innerText = 'En savoir plus';

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
       

    // Append buttons to overlay
    overlay.appendChild(addButton);
    overlay.appendChild(moreButton);

    // Append everything
    card.appendChild(img);
    card.appendChild(overlay);
    col.appendChild(card);
    cardContainer.appendChild(col);

  });
}

let currentType = 'movie'; // Défaut : films

// Initialisation après chargement
fetchPopularMovies();

// Gestion du switch "Films / Séries"
const switchButtons = document.querySelectorAll('#contentSwitcher .btn');
switchButtons.forEach(button => {
  button.addEventListener('click', () => {
    // Visuel actif
    switchButtons.forEach(b => b.classList.remove('active'));
    button.classList.add('active');

    // Mise à jour du type
    currentType = button.dataset.type;
    fetchPopularMovies();
  });
});

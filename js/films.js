const TMDB_URL = 'https://api.themoviedb.org/3/discover/movie?include_adult=false&include_video=false&language=en-US&page=1&sort_by=popularity.desc';

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
      <div class="d-flex justify-content-center w-100 my-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Chargement...</span>
        </div>
      </div>
    `;
    const response = await fetch(TMDB_URL, OPTIONS);
    if (!response.ok) throw new Error('Statut HTTP ' + response.status);

    const data = await response.json();
    const movies = data.results;
    renderMovieCards(movies);
  } catch (err) {
    console.error('Erreur fetchPopularMovies:', err);
    cardContainer.innerHTML = `
      <div class="col-12">
        <div class="alert alert-danger" role="alert">
          Impossible de récupérer les films populaires.
        </div>
      </div>
    `;
  }
}

function renderMovieCards(movies) {
  cardContainer.innerHTML = '';

  movies.forEach(movie => {
    if (!movie.poster_path) return;

    const posterURL = IMG_BASE_URL + movie.poster_path;

    const col = document.createElement('div');
    col.className = 'col-sm-3 col-md-4 col-lg-2 mb-4';

    const card = document.createElement('div');
    card.className = 'card h-100 shadow-sm';

    const img = document.createElement('img');
    img.src = posterURL;
    img.alt = movie.title + ' poster';
    img.className = 'card-img-top';

    card.appendChild(img);
    col.appendChild(card);
    cardContainer.appendChild(col);
  });
}

window.addEventListener('DOMContentLoaded', fetchPopularMovies);
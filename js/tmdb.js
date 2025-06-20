const TMDB_OPTIONS = {
    method: 'GET',
    headers: {
      accept: 'application/json',
      Authorization: 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJhYzhmMzliYWFiNThlOGZjMWU1MzU2ZmExMTY0NjE3NyIsIm5iZiI6MTc0ODg2NzkxNC41NTEsInN1YiI6IjY4M2Q5YjRhNGU4ODljZjA3NjY4OWQyMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.yZegMUEuDzZ2DgNqy_uI6dwrWpLjItOOcmGbHhaqrDI'
    }
  };
  
  const IMG_BASE = 'https://image.tmdb.org/t/p/w500';
  const BANNER_BASE = 'https://image.tmdb.org/t/p/original';
  
  async function fetchTmdbDetails(type, id) {
    const res = await fetch(`https://api.themoviedb.org/3/${type}/${id}?language=fr-FR&append_to_response=images`, {
      headers: {
        accept: 'application/json',
        Authorization: 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJhYzhmMzliYWFiNThlOGZjMWU1MzU2ZmExMTY0NjE3NyIsIm5iZiI6MTc0ODg2NzkxNC41NTEsInN1YiI6IjY4M2Q5YjRhNGU4ODljZjA3NjY4OWQyMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.yZegMUEuDzZ2DgNqy_uI6dwrWpLjItOOcmGbHhaqrDI'
      }
    });
    return await res.json();
  }  

const bannerSearchBtn = document.getElementById('bannerSearchBtn');
if (bannerSearchBtn) {
  bannerSearchBtn.addEventListener('click', async () => {
    const query = document.getElementById('bannerSearchInput').value.trim();
    const bannerOptions = document.getElementById('bannerOptions');
    if (!query) return;
  
    bannerOptions.innerHTML = 'Recherche...';
  
    try {
      const searchUrl = `https://api.themoviedb.org/3/search/multi?query=${encodeURIComponent(query)}&include_adult=false&language=fr-FR`;
      const res = await fetch(searchUrl, {
        headers: {
          accept: 'application/json',
          Authorization: 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJhYzhmMzliYWFiNThlOGZjMWU1MzU2ZmExMTY0NjE3NyIsIm5iZiI6MTc0ODg2NzkxNC41NTEsInN1YiI6IjY4M2Q5YjRhNGU4ODljZjA3NjY4OWQyMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.yZegMUEuDzZ2DgNqy_uI6dwrWpLjItOOcmGbHhaqrDI'
        }
      });
  
      const data = await res.json();
  
      if (!Array.isArray(data.results) || data.results.length === 0) {
        bannerOptions.innerHTML = '<p class="text-danger">Aucun résultat.</p>';
        return;
      }
  
      let allImages = [];
  
      for (const result of data.results.slice(0, 5)) {
        if (!['movie', 'tv'].includes(result.media_type)) continue;
  
        const detailsRes = await fetch(`https://api.themoviedb.org/3/${result.media_type}/${result.id}?language=fr-FR&append_to_response=images`, {
          headers: {
            accept: 'application/json',
            Authorization: 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJhYzhmMzliYWFiNThlOGZjMWU1MzU2ZmExMTY0NjE3NyIsIm5iZiI6MTc0ODg2NzkxNC41NTEsInN1YiI6IjY4M2Q5YjRhNGU4ODljZjA3NjY4OWQyMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.yZegMUEuDzZ2DgNqy_uI6dwrWpLjItOOcmGbHhaqrDI'
          }
        });
  
        const details = await detailsRes.json();
        const backdrops = (details.images?.backdrops || []);

        if (backdrops.length === 0 && details.backdrop_path) {
            allImages.push(details.backdrop_path);
        } else {
            allImages.push(...backdrops.map(b => b.file_path));
        }
      }
  
      bannerOptions.innerHTML = allImages.map(path => `
        <img src="https://image.tmdb.org/t/p/w300${path}" data-path="${path}" class="img-thumbnail m-2" style="cursor:pointer; max-width: 50%;">
      `).join('');
  
      bannerOptions.querySelectorAll('img').forEach(img => {
        img.addEventListener('click', async () => {
          const path = img.getAttribute('data-path');
          await fetch(`./php/playset_bdd_access.php?action=setbanner`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: playsetId, banner: path })
          });
  
          document.getElementById('playsetBanner').style.backgroundImage = `url(https://image.tmdb.org/t/p/w1280${path})`;
          bootstrap.Modal.getInstance(document.getElementById('bannerModal')).hide();
        });
      });
  
    } catch (e) {
      console.error("Erreur TMDb :", e);
      bannerOptions.innerHTML = '<p class="text-danger">Erreur lors du chargement.</p>';
    }
  });
}
  
  
  
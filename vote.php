<?php
session_start();
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: login_form.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Session de vote</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="css/base.css">
  <style>
    body {
      background: #232226;
      color: #fff;
      font-family: 'Montserrat', Arial, sans-serif;
    }
    .banner {
      background-size: cover;
      background-position: center 30%;
      height: 44vh;
      position: relative;
      border-bottom: 3px solid #312940;
      margin-bottom: 1.3rem;
      display: flex;
      align-items: flex-start;
    }
    .vote-panel {
      position: absolute;
      top: 20px; right: 30px;
      background: rgba(34,34,36,0.88);
      border-radius: 22px;
      padding: 1.5rem 2.1rem;
      min-width: 330px;
      min-height: 310px;
      box-shadow: 0 4px 32px 0 rgba(30,30,70,0.13);
      z-index: 2;
      color: #fff;
    }
    .vote-graph {
      width: 115px; height: 115px; background: transparent;
      display: block;
    }
    .timer {
      font-size: 1.1rem; margin-bottom: .5rem;
      font-weight: bold; letter-spacing: .06em;
    }
    .btn-vote {
      width: 100%; font-weight: bold; font-size: 1.1rem; margin-top: .6rem;
    }
    .gallery-title {
      font-size: 1.4rem; font-weight: 700; letter-spacing: .07em;
      text-align: center; margin: 2rem 0 1rem 0; color: #ded3fa;
    }
    .card-movie {
      border-radius: 14px; overflow: hidden; background: #222; border: none;
      box-shadow: 0 2px 16px 0 rgba(45,32,58,0.09);
    }
    .card-movie img { object-fit: cover; height: 260px; }
  </style>
</head>
<body>
  <div class="banner d-flex align-items-start justify-content-end" id="voteBanner">
    <div class="vote-card shadow-lg p-4">
        <div class="d-flex flex-column flex-md-row align-items-stretch gap-3">
            <!-- Colonne gauche (titre + timer + graph) -->
            <div class="d-flex flex-column align-items-center justify-content-center" style="min-width:160px;max-width:220px;">
                <span id="voteName" style="font-size:1.15em;font-weight:700;margin-bottom:0.2em;text-align:center;">Session Vote</span>
                <span class="timer mb-2" id="voteTimer" style="font-size:1.08em;font-weight:500;letter-spacing:.09em;text-align:center;">0J : 0H : 0M : 0S</span>
                <canvas id="voteChart" class="vote-graph mt-1"></canvas>
            </div>
            <!-- Colonne droite (affiche + select + bouton) -->
            <div class="vote-panel-content d-flex flex-column justify-content-between flex-grow-1 ps-md-4">
                <div>
                <div class="vote-selected-poster mb-2 d-none d-md-flex align-items-center justify-content-center" id="selectedPoster" style="height:140px;min-width:90px;"></div>
                <select id="voteSelect" class="form-select mb-2"></select>
                </div>
                <button id="voteBtn" class="btn btn-vote btn-primary mt-2">VOTER</button>
            </div>
        </div>
    </div>
  </div>

  <div class="container">
    <div class="gallery-title">La sélection</div>
    <div class="row g-3" id="moviesGallery">
      <!-- Films/séries dynamiques ici -->
    </div>
  </div>

  <script src="./js/tmdb.js"></script>
  <script>
    const USER_ID = <?= isset($user_id) ? $user_id : 'null' ?>;
    const VOTE_ID = new URLSearchParams(location.search).get('vote_id');
    let PLAYSET_ID = null; // on l'obtient via l'API

    // Fonction utilitaire : récupérer le titre par type et ID depuis TMDb
    async function fetchTitleById(type, id) {
      const url = `https://api.themoviedb.org/3/${type}/${id}?language=fr-FR`;
      try {
        const response = await fetch(url, {
          headers: {
            accept: 'application/json',
            Authorization: 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJhYzhmMzliYWFiNThlOGZjMWU1MzU2ZmExMTY0NjE3NyIsIm5iZiI6MTc0ODg2NzkxNC41NTEsInN1YiI6IjY4M2Q5YjRhNGU4ODljZjA3NjY4OWQyMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.yZegMUEuDzZ2DgNqy_uI6dwrWpLjItOOcmGbHhaqrDI' // Remplace par ton vrai token
          }
        });
        if (!response.ok) throw new Error(`Erreur HTTP ${response.status}`);
        const data = await response.json();
        return data.title || data.name || 'Titre inconnu';
      } catch (err) {
        console.error('Erreur lors de la récupération du titre :', err);
        return 'Erreur de récupération';
      }
    }

    // 1. Charger la session de vote (nom, date fin, playset id, etc)
    async function loadVoteSession() {
      const res = await fetch(`./php/playset_bdd_access.php?action=get_vote_session&id=${VOTE_ID}`);
      const data = await res.json();
      document.getElementById('voteName').textContent = data.name;
      PLAYSET_ID = data.playset_id;
      // Bannière
      const banner = data.banner ? (data.banner.startsWith('http') ? data.banner : BANNER_BASE + data.banner) : '';
      document.getElementById('voteBanner').style.backgroundImage = banner ? `url(${banner})` : '';
      // Timer
      setupTimer(data.end);
      // Charger films
      await loadGalleryAndOptions(PLAYSET_ID);
    }

    async function loadGalleryAndOptions(playsetId) {
      const res = await fetch(`./php/playset_bdd_access.php?action=view&id=${playsetId}`);
      const data = await res.json();
      // Galerie de films
      const gallery = document.getElementById('moviesGallery');
      gallery.innerHTML = '';
      // Fetch all TMDb details in parallel for better performance
      const detailPromises = data.entries.map(item =>
        fetchTmdbDetails(item.Type, item.TMDB_ID)
      );
      const detailsList = await Promise.all(detailPromises);

      detailsList.forEach((details, index) => {
        const div = document.createElement('div');
        div.className = 'col-6 col-sm-4 col-md-4 col-lg-3';
        div.innerHTML = `
          <div class="card mb-4 position-relative">
            <img src="${IMG_BASE}${details.poster_path}" class="card-img-top" alt="${details.title || details.name}">
          </div>
        `;
        gallery.appendChild(div);
      });
      // Select d’options de vote with title/name labels
      const sel = document.getElementById('voteSelect');
      sel.innerHTML = data.entries.map((opt, index) => {
        const details = detailsList[index];
        const title = details.title || details.name || opt.TMDB_ID;
        const icon = opt.Type === "movie" ? "🎬" : "📺";
        return `<option value="${opt.Type}|${opt.TMDB_ID}">${icon} ${title}</option>`;
      }).join('');
    }

    // 3. Gérer le vote
    document.getElementById('voteBtn').onclick = async function() {
        const val = document.getElementById('voteSelect').value;
        if (!val) return;
        const [type, tmdb_id] = val.split('|');
        const user_id = USER_ID;
        const res = await fetch(
            './php/playset_bdd_access.php?action=user_vote',
            {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id, vote_id: VOTE_ID, tmdb_id, type })
            }
        );
        const d = await res.json();
        if (d.success) {
            // On désactive le bouton, on change le texte, etc.
            const btn = document.getElementById('voteBtn');
            btn.disabled = true;
            btn.textContent = 'Vous avez déjà voté';
            // On rafraîchit immédiatement le graphique pour faire apparaître le vote
            updateChart();
        } else {
            alert(d.error || "Erreur lors du vote.");
        }
    };

    // 4. Graphique en direct
    let chart = null;

    async function updateChart() {
        // 1. On récupère les résultats depuis l'API PHP
        const res = await fetch(`./php/playset_bdd_access.php?action=vote_results&vote_id=${VOTE_ID}`);
        const data = await res.json();

        // 2. S'il n'y a pas de "results" ou que ce n'est pas un tableau, on détruit et on sort
        if (!data.results || !Array.isArray(data.results)) {
            if (chart) {
            chart.destroy();
            chart = null;
            }
            return;
        }
        // Build labels (fetch titles asynchronously)
        const labelPromises = data.results.map(e => fetchTitleById(e.type, e.tmdb_id));
        const labels = await Promise.all(labelPromises);
        const votes = data.results.map(e => e.votes);

        // 4. Si le graphique n'existe pas encore, on le crée
        if (!chart) {
            const ctx = document.getElementById('voteChart').getContext('2d');
            chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                data: votes,
                backgroundColor: [
                    '#8b7fab', '#fc829e', '#ffe17c', '#a7edce', '#e4844a',
                    // Vous pouvez ajouter d’autres couleurs si besoin
                ]
                }]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                const votes = context.parsed || context.raw;
                                return `${votes} vote(s)`;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }
    // 5. Sinon, on met simplement à jour les données existantes
    else {
        chart.data.labels = labels;
        chart.data.datasets[0].data = votes;
        chart.update();
    }
    }

    // 5. Timer
    function setupTimer(end) {
        function updateTimer() {
            const endTime = new Date(end);
            const now = new Date();

            // Si le temps est écoulé, affiche “0J : 0H : 0M : 0S” et désactive le bouton VOTER
            if (endTime <= now) {
            document.getElementById('voteTimer').textContent = `0J : 0H : 0M : 0S`;
            const btn = document.getElementById('voteBtn');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('disabled');
            }
            return;
            }

            // Sinon, calcule le temps restant
            let diff = (endTime - now) / 1000;
            const j = Math.floor(diff / 86400); diff %= 86400;
            const h = Math.floor(diff / 3600); diff %= 3600;
            const m = Math.floor(diff / 60); diff %= 60;
            const s = Math.floor(diff);

            document.getElementById('voteTimer').textContent = `${j}J : ${h}H : ${m}M : ${s}S`;

            // Veille à ce que le bouton reste actif tant que le temps n'est pas écoulé
            const btn = document.getElementById('voteBtn');
            if (btn) {
            btn.disabled = false;
            btn.classList.remove('disabled');
            }

            setTimeout(updateTimer, 1000);
        }
        updateTimer();
    }

    // After loading session and chart, check if user already voted
    async function checkUserVoted() {
      const user_id = USER_ID;
      const res = await fetch(`./php/playset_bdd_access.php?action=has_voted&vote_id=${VOTE_ID}`);
      const data = await res.json();
      if (data.voted) {
        const btn = document.getElementById('voteBtn');
        btn.disabled = true;
        btn.textContent = 'Vous avez déjà voté';
      }
    }

    function updateSelectedPoster(details) {
      const posterDiv = document.getElementById('selectedPoster');
      if (details && details.poster_path) {
          posterDiv.innerHTML = `<img src="${IMG_BASE}${details.poster_path}" alt="Affiche" style="max-height:140px;">`;
      } else {
          posterDiv.innerHTML = '';
      }
    }

    const sel = document.getElementById('voteSelect');
    sel.onchange = async function() {
      const val = sel.value;
      if (!val) return updateSelectedPoster(null);
      const [type, tmdb_id] = val.split('|');
      const details = await fetchTmdbDetails(type, tmdb_id);
      updateSelectedPoster(details);
    };
    // Affichage initial de l’affiche (si option par défaut)
    if (sel.options.length) {
      const [type, tmdb_id] = sel.options[0].value.split('|');
      fetchTmdbDetails(type, tmdb_id).then(updateSelectedPoster);
    }

    window.addEventListener('DOMContentLoaded', () => {
        loadVoteSession()
        .then(() => {
            // Après avoir chargé la session (bannière, timer, galerie, etc.), on affiche le graphique une première fois
            updateChart();
            // Puis on rafraîchit toutes les 3500 ms
            setInterval(updateChart, 3500);
            // Et on vérifie si l'utilisateur a déjà voté
            checkUserVoted();
        });
    });
        
  </script>
</body>
</html>
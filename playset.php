<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login_form.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Playset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/base.css">
    <style>
        .banner {
            position: relative;
            height: 30vh;
            background-size: cover;
            background-position: center 10%; /* ✅ 10% du haut */
            display: flex;
            align-items: center;
            justify-content: flex-end;
            color: white;
            padding: 2rem;
        }
        .banner-overlay {
            background: rgba(0, 0, 0, 0.5);
            padding: 1rem;
            border-radius: 0.5rem;
            text-align: right;
        }
        .poster-img {
            height: 250px;
            object-fit: cover;
        }
    </style>
</head>

<div class="modal fade" id="createVoteModal" tabindex="-1" aria-labelledby="createVoteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content text-dark">
      <div class="modal-header">
        <h5 class="modal-title" id="createVoteModalLabel">Créer une session de vote</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <form id="createVoteForm">
        <div class="modal-body">
          <div class="mb-3">
            <label for="voteName" class="form-label">Nom du vote</label>
            <input type="text" class="form-control" id="voteName" required>
          </div>
          <div class="mb-3">
            <label for="voteEndDate" class="form-label">Date et heure de fin</label>
            <input type="datetime-local" class="form-control" id="voteEndDate" required>
          </div>
          <div class="mb-3">
            <label for="voteDesc" class="form-label">Description (optionnelle)</label>
            <textarea class="form-control" id="voteDesc"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Créer le vote</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editPlaysetModal" tabindex="-1" aria-labelledby="editPlaysetModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content text-dark">
      <div class="modal-header">
        <h5 class="modal-title" id="editPlaysetModalLabel">Éditer le playset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <form id="editPlaysetForm">
        <div class="modal-body">
          <div class="mb-3">
            <label for="editPlaysetName" class="form-label">Nom du playset</label>
            <input type="text" class="form-control" id="editPlaysetName" required>
          </div>
          <div class="mb-3">
            <label for="editPlaysetDesc" class="form-label">Description</label>
            <textarea class="form-control" id="editPlaysetDesc" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="bannerModal" tabindex="-1" aria-labelledby="bannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Choisir une bannière</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <div class="input-group mb-3">
            <input type="text" id="bannerSearchInput" class="form-control" placeholder="Rechercher un film ou une série...">
            <button class="btn btn-primary" id="bannerSearchBtn">Rechercher</button>
          </div>
          <div id="bannerOptions" class="d-flex flex-wrap gap-2 justify-content-center"></div>
        </div>
      </div>
    </div>
  </div>
  
 <header class="bg-dark text-white py-3">
    <nav>
      <div class="container">
        <ul class="nav">
          <li class="nav-item">
            <a class="nav-link text-white" href="index.php">Accueil</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white" href="php/logout.php">Se déconnecter</a>
          </li>
          <?php if (isset($_SESSION['user']) && $_SESSION['user'] == 1): ?>
            <li class="nav-item">
              <a class="nav-link text-white" href="./admin_dashboard/index.html">Admin</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
  </header>

<body>
    <div class="banner" id="playsetBanner">
        <div class="banner-overlay">
            <h2 id="playsetTitle">Titre du Playset</h2>
            <p id="playsetDesc" class="mb-0"></p>
            <button class="btn btn-primary mt-2" id="createVoteBtn">
                <i class="bi bi-plus-circle"></i> Créer un vote
            </button>
            <div class="btn-group" role="group" aria-label="Basic example">
                <button class="btn btn-outline-light mt-2" id="changeBannerBtn"><i class="bi bi-card-image"></i></button>
                <button class="btn btn-outline-light mt-2" id=""><i class="bi bi-pencil-fill"></i></button>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row" id="playsetContent">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./js/tmdb.js"></script>
    <script>
    const playsetId = new URLSearchParams(location.search).get('id');

    async function loadPlayset() {
        const res = await fetch(`./php/playset_bdd_access.php?action=view&id=${playsetId}`);
        const data = await res.json();

        document.title = data.name;

        document.getElementById('playsetTitle').textContent = data.name;
        document.getElementById('playsetDesc').textContent = data.description || '';

        const listContainer = document.getElementById('playsetContent');

        if (data.banner) {
            const backdrop = data.banner.startsWith('http') ? data.banner : BANNER_BASE + data.banner;
            document.getElementById('playsetBanner').style.backgroundImage = `url(${backdrop})`;
        } else if (data.entries && data.entries.length > 0) {
            const details = await fetchTmdbDetails(data.entries[0].Type, data.entries[0].TMDB_ID);
            if (details.backdrop_path) {
                document.getElementById('playsetBanner').style.backgroundImage = `url(${BANNER_BASE + details.backdrop_path})`;
            } else {
                document.getElementById('playsetBanner').style.backgroundImage = '';
            }
        } else {
            document.getElementById('playsetBanner').style.backgroundImage = '';
        }

        for (const item of data.entries) {
            const details = await fetchTmdbDetails(item.Type, item.TMDB_ID);

            const div = document.createElement('div');
            div.className = 'col-6 col-sm-4 col-md-4 col-lg-3';
            div.innerHTML = `
                <div class="card mb-4 position-relative">
                    <button class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 delete-entry-btn" title="Supprimer">
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                    <img src="${IMG_BASE}${details.poster_path}" class="card-img-top" alt="${details.title || details.name}">
                </div>`;
            listContainer.appendChild(div);

            const deleteBtn = div.querySelector('.delete-entry-btn');
            deleteBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                await fetch('./php/playset_bdd_access.php?action=remove_entry', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        playset_id: playsetId,
                        tmdb_id: item.TMDB_ID,
                        type: item.Type
                    })
                });
                div.remove();
            });
        }
    }

    loadPlayset();

    const editBtn = document.querySelector('.banner-overlay .bi-pencil-fill').closest('button');
    async function checkActiveVote() {
      const res  = await fetch(`./php/playset_bdd_access.php?action=get_active_vote&playset_id=${playsetId}`);
      const info = await res.json();
      if (info.active) {
          const createBtn = document.getElementById('createVoteBtn');
          const voteBtn   = document.createElement('a');
          voteBtn.href    = `vote.php?vote_id=${info.vote_id}`;
          voteBtn.className = 'btn btn-success mt-2';
          voteBtn.innerHTML = '<i class="bi bi-bar-chart-line"></i> Aller au vote';
          createBtn.replaceWith(voteBtn);
      }
    }
    checkActiveVote();
    const editModal = new bootstrap.Modal(document.getElementById('editPlaysetModal'));
    const editForm = document.getElementById('editPlaysetForm');
    const nameInput = document.getElementById('editPlaysetName');
    const descInput = document.getElementById('editPlaysetDesc');

    let playsetData = null;

    async function refreshPlaysetHeader(data) {
        document.getElementById('playsetTitle').textContent = data.name;
        document.getElementById('playsetDesc').textContent = data.description || '';
    }

    editBtn.addEventListener('click', async () => {
        const res = await fetch(`./php/playset_bdd_access.php?action=view&id=${playsetId}`);
        playsetData = await res.json();

        nameInput.value = playsetData.name || '';
        descInput.value = playsetData.description || '';
        editModal.show();
    });

    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        await fetch(`./php/playset_bdd_access.php?action=edit`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: playsetId,
                name: nameInput.value,
                description: descInput.value
            })
        });

        refreshPlaysetHeader({ name: nameInput.value, description: descInput.value });
        editModal.hide();
    });

    document.getElementById('changeBannerBtn').addEventListener('click', async () => {
        const bannerOptions = document.getElementById('bannerOptions');
        bannerOptions.innerHTML = 'Chargement...';

        const data = await (await fetch(`./php/playset_bdd_access.php?action=view&id=${playsetId}`)).json();
        
        if (data.entries.length > 0) {
            const exampleItem = data.entries[0];
            const details = await fetchTmdbDetails(exampleItem.Type, exampleItem.TMDB_ID);
            const backdrops = details.images.backdrops || [];

            bannerOptions.innerHTML = backdrops.map(b => `
            <img src="https://image.tmdb.org/t/p/w300${b.file_path}" data-path="${b.file_path}" class="img-thumbnail" style="cursor:pointer;max-width:30%;">
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
        } else {
            bannerOptions.innerHTML = `
            <div class="alert alert-info w-100 text-center">
                Ce playset ne contient aucun film/série.<br>
                Faites une recherche TMDb pour choisir une bannière ou collez l'URL d'une image ci-dessous.
            </div>
            <div class="input-group mb-3">
                <input type="text" id="customBannerUrl" class="form-control" placeholder="URL d'une image…">
                <button class="btn btn-success" id="setCustomBannerBtn">Valider</button>
            </div>
            `;

            document.getElementById('setCustomBannerBtn').onclick = async () => {
            const url = document.getElementById('customBannerUrl').value.trim();
            if (url) {
                await fetch(`./php/playset_bdd_access.php?action=setbanner`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: playsetId, banner: url })
                });
                document.getElementById('playsetBanner').style.backgroundImage = `url(${url})`;
                bootstrap.Modal.getInstance(document.getElementById('bannerModal')).hide();
            }
            };
        }

        new bootstrap.Modal(document.getElementById('bannerModal')).show();
        });

        document.getElementById('createVoteBtn').addEventListener('click', () => {
        new bootstrap.Modal(document.getElementById('createVoteModal')).show();
        });

        document.getElementById('createVoteForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const name = document.getElementById('voteName').value.trim();
            const endDate = document.getElementById('voteEndDate').value;
            const desc = document.getElementById('voteDesc').value.trim();
            if (!name || !endDate) return;

            const res = await fetch('./php/playset_bdd_access.php?action=create_vote_session', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                playset_id: playsetId,
                name,
                end: endDate,
                description: desc
                })
            });
            const data = await res.json();
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('createVoteModal')).hide();
                window.location.reload();
            } else {
                alert(data.error || "Erreur lors de la création du vote.");
            }
        });

    </script>
</body>
</html>

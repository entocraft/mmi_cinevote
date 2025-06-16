<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login_form.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page d'accueil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/base.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</head>

<div class="modal fade" id="createPlaysetModal" tabindex="-1" aria-labelledby="createPlaysetModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content text-dark">
      <div class="modal-header">
        <h5 class="modal-title" id="createPlaysetModalLabel">Créer un nouveau playset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <form id="createPlaysetForm">
        <div class="modal-body">
          <div class="mb-3">
            <label for="createPlaysetName" class="form-label">Nom du playset</label>
            <input type="text" class="form-control" id="createPlaysetName" required>
          </div>
          <div class="mb-3">
            <label for="createPlaysetDesc" class="form-label">Description</label>
            <textarea class="form-control" id="createPlaysetDesc" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Créer</button>
        </div>
      </form>
    </div>
  </div>
</div>

  <div class="modal fade" id="addToPlaysetModal" tabindex="-1" aria-labelledby="addToPlaysetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content text-dark">
        <div class="modal-header">
          <h5 class="modal-title" id="addToPlaysetModalLabel">Ajouter à un Playset</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <form id="playsetForm">
            <div class="mb-3">
              <label for="playsetSelect" class="form-label">Choisir un playset existant</label>
              <select class="form-select" id="playsetSelect" required>
                <option value="">Sélectionnez un playset</option>
              </select>
            </div>
  
            <div class="text-center my-3">— ou —</div>
  
            <div class="mb-3">
              <label for="newPlaysetName" class="form-label">Créer un nouveau playset</label>
              <input type="text" class="form-control" id="newPlaysetName" placeholder="Nom du nouveau playset">
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button id="confirmAddToPlaysetBtn" class="btn btn-primary">Ajouter</button>
        </div>
      </div>
    </div>
  </div>  

  <div class="modal fade" id="movieModal" tabindex="-1" aria-labelledby="movieModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
      <div class="modal-content text-dark">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal" aria-label="Fermer"></button>
          <img id="movieModalImage" src="" alt="Backdrop" class="w-100 rounded-top">
          <div class="p-4">
            <div class="row">
              <div class="col-12 col-lg-8 mb-4 mb-lg-0">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                  <div>
                    <p class="fs-3 modal-title" id="movieModalLabel"></p>
                    <p><span id="movieModalRating"></span> / 10</p>
                  </div>
                  <div class="btn-group" role="group" aria-label="Basic example">
                    <button type="button" class="btn"><i class="bi bi-bookmark"></i></button>
                  </div>
                </div>
                <p id="movieModalOverview"></p>
                <p><strong>Date de sortie :</strong> <span id="movieModalDate"></span></p>
              </div>
          
              <div class="col-12 col-lg-4">
                <h6 class="text-uppercase fw-bold mb-3">Disponible sur</h6>
                <div id="movieModalProviders" class="mb-4"></div>
                <h6 class="text-uppercase fw-bold mb-3">Acteurs</h6>
                <div id="movieModalCast" class="d-flex flex-row flex-lg-column gap-3 overflow-auto pb-2"></div>
              </div>
            </div>
          </div>          
        </div>
      </div>
    </div>
  </div>
  

<body>

  <header class="bg-dark text-white py-3">
    <nav>
      <div class="container">
        <ul class="nav">
          <li class="nav-item">
            <a class="nav-link text-white" href="index.php">Accueil</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white" href="#">Films</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white" href="#">Séries</a>
          </li>
        </ul>
      </div>
  </header>

  <main class="container my-2">
    <div class="mb-4">
      <h5 class="mb-3">Mes Playsets</h5>
      <div class="row" id="playsetGrid"></div>    
    </div>    
    <div class="navcontainer mb-4">
      <div class="container py-2">
        <div class="row g-2 align-items-center">
          <!-- Groupe de boutons Films/Séries -->
          <div class="col-12 col-md-auto">
            <div class="btn-group" role="group" aria-label="Switcher Films/Séries" id="contentSwitcher">
              <button type="button" class="btn btn-outline-primary active" data-type="movie">🎬 Films</button>
              <button type="button" class="btn btn-outline-primary" data-type="tv">📺 Séries</button>
            </div>
          </div>
    
          <!-- Barre de recherche avec bouton -->
          <div class="col">
            <div class="input-group">
              <input type="text" id="searchInput" class="form-control" placeholder="Rechercher un film ou une série...">
              <button class="btn searchbtn" id="searchButton" type="button">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>    
    <div class="row" id="cardContainer">

    </div>
  </main>

  <!-- Bootstrap JS Bundle (optionnel) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    window.currentUserId = <?= $_SESSION['user']['id'] ?? 'null' ?>;
  </script>
  <script src="js/films.js"></script>
  <script src="js/playsets.js"></script>
</body>
</html>

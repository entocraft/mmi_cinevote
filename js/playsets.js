async function loadPlaysets() {
  const userId = 1; // À remplacer dynamiquement

  try {
    const res = await fetch(`./php/playset_bdd_access.php?action=list&user_id=${userId}`);
    const data = await res.json();

    const container = document.getElementById('playsetGrid');
    container.innerHTML = '';

    const createCol = document.createElement('div');
    createCol.className = 'col-6 col-sm-4 col-md-4 col-lg-3';
    createCol.innerHTML = `
      <div class="playset-card create-card" onclick="openCreatePlaysetModal()">
        <div>➕ Créer un nouveau playset</div>
      </div>
    `;
    container.appendChild(createCol);

    data.playsets.forEach(p => {
      const bannerUrl = p.Banner
        ? `https://image.tmdb.org/t/p/w500${p.Banner}`
        : 'https://via.placeholder.com/500x500?text=No+Banner';

      const col = document.createElement('div');
      col.className = 'col-6 col-sm-4 col-md-4 col-lg-3';

      const card = document.createElement('div');
      card.className = 'playset-card';
      card.style.backgroundImage = `url('${bannerUrl}')`;

      card.innerHTML = `
        <h6 class="mb-1">${p.Name}</h6>
        <div class="small">${p.entry_count} entrées</div>
      `;

      card.addEventListener('click', () => {
        window.location.href = `playset.html?id=${p.ID}`;
      });

      col.appendChild(card);
      container.appendChild(col);
    });

  } catch (e) {
    console.error("Erreur lors du chargement des playsets :", e);
  }
}

loadPlaysets();

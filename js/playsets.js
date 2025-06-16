async function loadPlaysets() {
  const userId = window.currentUserId ?? null; // injecté côté PHP
  if (!userId) {
    console.error('User ID introuvable : assurez‑vous que window.currentUserId est défini.');
    return;
  }

  try {
    const res = await fetch(`./php/playset_bdd_access.php?action=list&user_id=${userId}`);
    const data = await res.json();
    console.log('DATA FETCHED:', data);

    // Sécurise la structure reçue :
    const playsets = Array.isArray(data.playsets) ? data.playsets : [];
    if (playsets.length === 0) {
      console.warn('Aucun playset trouvé ou structure invalide :', data);
    }

    // DOM container
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

    playsets.forEach(p => {
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
        window.location.href = `playset.php?id=${p.ID}`;
      });

      col.appendChild(card);
      container.appendChild(col);
    });

  } catch (e) {
    console.error("Erreur lors du chargement des playsets :", e);
  }
}

loadPlaysets();

function openCreatePlaysetModal() {
  const modal = new bootstrap.Modal(document.getElementById('createPlaysetModal'));
  modal.show();
}

document.getElementById('createPlaysetForm').addEventListener('submit', async function(e) {
  e.preventDefault();

  const name = document.getElementById('createPlaysetName').value.trim();
  const desc = document.getElementById('createPlaysetDesc').value.trim();

  if (!name) return;

  const userId = window.currentUserId ?? null;
  if (!userId) {
    alert("Utilisateur non identifié ; impossible de créer le playset.");
    return;
  }
  const res = await fetch('./php/playset_bdd_access.php?action=create', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ user_id: userId, name, description: desc })
  });
  const data = await res.json();

  if (data.success) {
    // Ferme le modal et rafraîchit la liste des playsets
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('createPlaysetModal'));
    modal.hide();
    document.getElementById('createPlaysetForm').reset();
    if (typeof loadPlaysets === 'function') loadPlaysets(); // recharge la liste principale
    if (typeof loadUserPlaysets === 'function') loadUserPlaysets(); // recharge aussi la liste dans la modal d'ajout si besoin
  } else {
    alert(data.error || "Erreur lors de la création du playset.");
  }
});
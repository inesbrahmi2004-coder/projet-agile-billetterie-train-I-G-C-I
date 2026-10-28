document.getElementById('searchForm').addEventListener('submit', function(e) {
  e.preventDefault(); // Empêche la page de se recharger

  const departure = document.getElementById('departure').value;
  const arrival = document.getElementById('arrival').value;
  const date = document.getElementById('date').value;

  // Affichage d'une alerte simple pour valider la recherche
  alert(`Recherche en cours de ${departure} vers ${arrival} le ${date} !`);
});
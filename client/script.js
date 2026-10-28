document.getElementById('searchForm').addEventListener('submit', function(e) {
  e.preventDefault(); // Empêche la page de se recharger

  const departure = document.getElementById('departure').value;
  const arrival = document.getElementById('arrival').value;
  const date = document.getElementById('date').value;

  // Création d'une zone de résultats s'il n'existe pas encore
  let resultsDiv = document.getElementById('resultsContainer');
  if (!resultsDiv) {
    resultsDiv = document.createElement('div');
    resultsDiv.id = 'resultsContainer';
    resultsDiv.style.marginTop = '20px';
    document.querySelector('.search-box').appendChild(resultsDiv);
  }

  // Simulation de trains trouvés
  resultsDiv.innerHTML = `
    <h3>Trains disponibles de ${departure} vers ${arrival} le ${date} :</h3>
    <ul style="list-style-type: none; padding: 0;">
      <li style="background: #e8f8f5; margin: 10px 0; padding: 10px; border-radius: 5px;">
        🚄 <strong>Train 1042</strong> - Départ : 08:30 - Arrivée : 11:15 - <strong>45 €</strong>
      </li>
      <li style="background: #e8f8f5; margin: 10px 0; padding: 10px; border-radius: 5px;">
        🚄 <strong>Train 1088</strong> - Départ : 12:15 - Arrivée : 15:00 - <strong>60 €</strong>
      </li>
    </ul>
  `;
});
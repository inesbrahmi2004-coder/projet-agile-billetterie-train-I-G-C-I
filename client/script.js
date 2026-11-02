// Billetterie Train - logique client (US01 à US05)
const TRAINS = [
  { name: 'TGV 1042', dep: '08:30', arr: '11:15', price: 45 },
  { name: 'TGV 1088', dep: '12:15', arr: '15:00', price: 60 }
];
const FIRST_CLASS_SUPPLEMENT = 20;
let search = null;   // { departure, arrival, date }
let selected = null; // train choisi

const esc = (t) => String(t).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const frDate = (iso) => new Date(iso + 'T00:00').toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

// US01 + US02 : recherche et affichage des résultats
document.getElementById('searchForm').addEventListener('submit', function (e) {
  e.preventDefault();
  search = {
    departure: document.getElementById('departure').value.trim(),
    arrival: document.getElementById('arrival').value.trim(),
    date: document.getElementById('date').value
  };
  if (search.departure.toLowerCase() === search.arrival.toLowerCase()) {
    alert("La gare d'arrivée doit être différente de la gare de départ.");
    return;
  }

  const cards = TRAINS.map((t, i) => `
      <div style="background: #e8f8f5; padding: 12px; border-radius: 6px; border-left: 4px solid #27ae60;">
        <strong>${t.name}</strong> — ${t.dep} ➔ ${t.arr} (${t.price} €)
        <br>
        <button type="button" onclick="selectTrain(${i})" style="margin-top: 8px; width: auto; font-size: 14px; padding: 5px 10px;">Choisir ce train</button>
      </div>`).join('');

  document.getElementById('resultsContainer').innerHTML = `
    <h3 style="font-size: 18px; color: #2c3e50; margin-top: 20px;">Trains disponibles (${esc(search.departure)} ➔ ${esc(search.arrival)}, ${frDate(search.date)}) :</h3>
    <div style="display: flex; flex-direction: column; gap: 10px;">${cards}</div>
    <div id="bookingSection" style="margin-top: 15px;"></div>`;
});

// US03 : sélection du train et de la classe
function selectTrain(i) {
  selected = TRAINS[i];
  document.getElementById('bookingSection').innerHTML = `
    <div style="background: #fff3cd; padding: 15px; border-radius: 6px; border: 1px solid #ffeeba;">
      <h4>Configuration du voyage : ${selected.name}</h4>
      <div class="form-group">
        <label for="classSelect">Classe de voyage :</label>
        <select id="classSelect" onchange="updatePrice()">
          <option value="2">2nde classe (${selected.price} €)</option>
          <option value="1">1ère classe (${selected.price + FIRST_CLASS_SUPPLEMENT} €)</option>
        </select>
      </div>
      <p id="totalPrice"><strong>Prix total : ${selected.price} €</strong></p>
      <hr style="border: 0; border-top: 1px solid #ffeeba; margin: 15px 0;">
      <h4>Coordonnées du passager</h4>
      <div class="form-group">
        <label for="passengerName">Nom et prénom :</label>
        <input type="text" id="passengerName" placeholder="Ex : Ines Brahmi">
      </div>
      <button type="button" style="background: #e67e22;" onclick="processPayment()">Simuler le paiement et valider</button>
    </div>`;
}

function currentPrice() {
  return document.getElementById('classSelect').value === '1' ? selected.price + FIRST_CLASS_SUPPLEMENT : selected.price;
}

function updatePrice() {
  document.getElementById('totalPrice').innerHTML = `<strong>Prix total : ${currentPrice()} €</strong>`;
}

// US04 + US05 : paiement simulé et confirmation
function processPayment() {
  const passengerName = document.getElementById('passengerName').value.trim();
  if (!passengerName) {
    alert('Veuillez saisir votre nom et prénom avant de valider.');
    return;
  }
  const selectedClass = document.getElementById('classSelect').value === '1' ? '1ère classe' : '2nde classe';
  const bookingRef = 'BILLET-' + Math.floor(100000 + Math.random() * 900000);

  document.getElementById('bookingSection').innerHTML = `
    <div style="background: #d4edda; padding: 15px; border-radius: 6px; border: 1px solid #c3e6cb; color: #155724;">
      <h3>✅ Réservation confirmée !</h3>
      <p><strong>Référence :</strong> ${bookingRef}</p>
      <p><strong>Passager :</strong> ${esc(passengerName)}</p>
      <p><strong>Trajet :</strong> ${selected.name} (${esc(search.departure)} ➔ ${esc(search.arrival)})</p>
      <p><strong>Date :</strong> ${frDate(search.date)} | <strong>Classe :</strong> ${selectedClass}</p>
      <p><strong>Montant payé :</strong> ${currentPrice()} €</p>
      <p style="font-size: 13px;">Un e-mail de confirmation virtuel a été envoyé.</p>
    </div>`;
}

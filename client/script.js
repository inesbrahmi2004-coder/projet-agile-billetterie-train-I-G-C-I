document.getElementById('searchForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const departure = document.getElementById('departure').value;
  const arrival = document.getElementById('arrival').value;
  const date = document.getElementById('date').value;

  let resultsDiv = document.getElementById('resultsContainer');
  if (!resultsDiv) {
    resultsDiv = document.createElement('div');
    resultsDiv.id = 'resultsContainer';
    resultsDiv.style.marginTop = '20px';
    document.querySelector('.search-box').appendChild(resultsDiv);
  }

  resultsDiv.innerHTML = `
    <h3>Trains disponibles (${departure} ➔ ${arrival} le ${date}) :</h3>
    <div style="display: flex; flex-direction: column; gap: 10px;">
      <div style="background: #e8f8f5; padding: 12px; border-radius: 6px; border-left: 4px solid #27ae60;">
        <strong>TGV 1042</strong> — 08:30 ➔ 11:15 (45 €)
        <br>
        <button onclick="selectTrain('TGV 1042', 45, '${departure}', '${arrival}', '${date}')" style="margin-top: 8px; width: auto; font-size: 14px; padding: 5px 10px;">Choisir ce train</button>
      </div>

      <div style="background: #e8f8f5; padding: 12px; border-radius: 6px; border-left: 4px solid #27ae60;">
        <strong>TGV 1088</strong> — 12:15 ➔ 15:00 (60 €)
        <br>
        <button onclick="selectTrain('TGV 1088', 60, '${departure}', '${arrival}', '${date}')" style="margin-top: 8px; width: auto; font-size: 14px; padding: 5px 10px;">Choisir ce train</button>
      </div>
    </div>
    <div id="bookingSection" style="margin-top: 15px;"></div>
  `;
});

function selectTrain(trainName, basePrice, departure, arrival, date) {
  const bookingDiv = document.getElementById('bookingSection');
  bookingDiv.innerHTML = `
    <div style="background: #fff3cd; padding: 15px; border-radius: 6px; border: 1px solid #ffeeba;">
      <h4>Configuration du voyage : ${trainName}</h4>
      <div class="form-group">
        <label for="classSelect">Classe de voyage :</label>
        <select id="classSelect" onchange="updatePrice(${basePrice})">
          <option value="2">2nde Classe (${basePrice} €)</option>
          <option value="1">1ère Classe (+20 €)</option>
        </select>
      </div>
      <p id="totalPrice"><strong>Prix total : ${basePrice} €</strong></p>
      
      <hr style="border: 0; border-top: 1px solid #ffeeba; margin: 15px 0;">
      
      <h4>Coordonnées du passager (US04)</h4>
      <div class="form-group">
        <label for="passengerName">Nom & Prénom :</label>
        <input type="text" id="passengerName" placeholder="Ex: Ines Brahmi" required>
      </div>
      <button style="background: #e67e22;" onclick="processPayment('${trainName}', '${departure}', '${arrival}', '${date}')">Simuler le paiement & Valider</button>
    </div>
  `;
}

function updatePrice(basePrice) {
  const selectedClass = document.getElementById('classSelect').value;
  const finalPrice = selectedClass === '1' ? basePrice + 20 : basePrice;
  document.getElementById('totalPrice').innerHTML = `<strong>Prix total : ${finalPrice} €</strong>`;
}

function processPayment(trainName, departure, arrival, date) {
  const passengerName = document.getElementById('passengerName').value;
  if (!passengerName) {
    alert("Veuillez saisir votre nom et prénom avant de valider.");
    return;
  }

  const selectedClass = document.getElementById('classSelect').value === '1' ? '1ère classe' : '2nde classe';
  const bookingRef = 'BILLET-' + Math.floor(100000 + Math.random() * 900000);

  const bookingDiv = document.getElementById('bookingSection');
  bookingDiv.innerHTML = `
    <div style="background: #d4edda; padding: 15px; border-radius: 6px; border: 1px solid #c3e6cb; color: #155724;">
      <h3>✅ Réservation confirmée !</h3>
      <p><strong>Référence :</strong> ${bookingRef}</p>
      <p><strong>Passager :</strong> ${passengerName}</p>
      <p><strong>Trajet :</strong> ${trainName} (${departure} ➔ ${arrival})</p>
      <p><strong>Date :</strong> ${date} | <strong>Classe :</strong> ${selectedClass}</p>
      <p style="font-size: 13px; color: #155724;">Un e-mail de confirmation virtuel a été envoyé.</p>
    </div>
  `;
}
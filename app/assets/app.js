/**
 * app.js - Petits comportements côté navigateur.
 * Rien d'essentiel n'en dépend : tous les contrôles sont refaits en PHP.
 */

// Accueil : afficher/masquer la date de retour selon « Aller-retour / Aller simple »
document.querySelectorAll('input[name="type"]').forEach(function (radio) {
    radio.addEventListener('change', majRetour);
});
function majRetour() {
    var bloc = document.getElementById('bloc-retour');
    var choix = document.querySelector('input[name="type"]:checked');
    if (!bloc || !choix) return;
    var ar = choix.value === 'ar';
    bloc.style.visibility = ar ? 'visible' : 'hidden';
    document.getElementById('retour').required = ar;
}
majRetour();

// Accueil : bouton pour inverser la gare de départ et la gare d'arrivée
var inverser = document.getElementById('inverser');
if (inverser) {
    inverser.addEventListener('click', function () {
        var dep = document.getElementById('dep'), arr = document.getElementById('arr');
        var tmp = dep.value; dep.value = arr.value; arr.value = tmp;
    });
}

// Accueil : la date de retour ne peut pas être avant la date d'aller
var aller = document.getElementById('aller');
if (aller) {
    aller.addEventListener('change', function () {
        var retour = document.getElementById('retour');
        retour.min = aller.value;
        if (retour.value && retour.value < aller.value) retour.value = aller.value;
    });
}

// Options : total mis à jour en direct quand on coche / décoche
var totalOptions = document.getElementById('total-options');
if (totalOptions) {
    var majTotal = function () {
        var total = parseFloat(totalOptions.dataset.base);
        document.querySelectorAll('input[name="options[]"]:checked').forEach(function (c) {
            total += parseFloat(c.dataset.prix);
        });
        totalOptions.textContent = total.toFixed(2).replace('.', ',') + ' €';
    };
    document.querySelectorAll('input[name="options[]"]').forEach(function (c) {
        c.addEventListener('change', majTotal);
    });
}

// TS3 : « l'œil » affiche le mot de passe en clair, puis le masque à nouveau
document.querySelectorAll('[data-oeil]').forEach(function (bouton) {
    bouton.addEventListener('click', function () {
        var champ = document.getElementById(bouton.dataset.oeil);
        var visible = champ.type === 'password';
        champ.type = visible ? 'text' : 'password';
        bouton.classList.toggle('visible', visible);
        bouton.setAttribute('aria-label', visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    });
});

// Demande de confirmation avant une action irréversible (vider le panier, supprimer un compte)
document.querySelectorAll('[data-confirmer]').forEach(function (bouton) {
    bouton.addEventListener('click', function (e) {
        if (!confirm(bouton.dataset.confirmer)) e.preventDefault();
    });
});

// Paiement : numéro de carte groupé par 4 chiffres, expiration au format MM/AA
var cb = document.getElementById('numero_cb');
if (cb) {
    cb.addEventListener('input', function () {
        cb.value = cb.value.replace(/\D/g, '').slice(0, 19).replace(/(\d{4})(?=\d)/g, '$1 ');
    });
}
var exp = document.getElementById('expiration');
if (exp) {
    exp.addEventListener('input', function () {
        var v = exp.value.replace(/\D/g, '').slice(0, 4);
        exp.value = v.length > 2 ? v.slice(0, 2) + '/' + v.slice(2) : v;
    });
}

// Calendrier des prix sur mobile : on fait défiler pour que le jour choisi soit visible au centre
document.querySelectorAll('.calendrier .choisi').forEach(function (jour) {
    var cal = jour.parentElement;
    if (cal.scrollWidth > cal.clientWidth) {
        cal.scrollLeft = jour.offsetLeft - cal.offsetLeft - (cal.clientWidth - jour.clientWidth) / 2;
    }
});

// Accueil : les photos de chaque ville défilent en fondu, avec un décalage entre les cartes
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.querySelectorAll('.destination').forEach(function (carte, numero) {
        var photos = carte.querySelectorAll('.destination-photo');
        if (photos.length < 2) return;
        var courante = 0;
        setTimeout(function () {
            setInterval(function () {
                photos[courante].classList.remove('visible');
                courante = (courante + 1) % photos.length;
                photos[courante].classList.add('visible');
            }, 4500);
        }, numero * 1100);
    });
}

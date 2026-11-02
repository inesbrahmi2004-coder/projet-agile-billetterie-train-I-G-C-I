<?php
/**
 * confirmation.php - Réservation confirmée, numéros de réservation et de billets.
 * Epic 4 - US2 : impression des billets aller/retour au format PDF (lien vers billet.php).
 */
require_once __DIR__ . '/includes/fonctions.php';
require_once __DIR__ . '/includes/recap.php';

$r = charger_reservation($_GET['ref'] ?? '');
if (!$r) {
    flash('Réservation introuvable.', 'erreur');
    rediriger('index.php');
}

$titre = 'Réservation confirmée';
require __DIR__ . '/includes/header.php';
?>
<section class="carte confirmation">
    <div class="coche" aria-hidden="true">✓</div>
    <h1>Merci <?= h($r['prenom']) ?>, votre réservation est confirmée !</h1>
    <p>Numéro de réservation : <b class="reference"><?= h($r['reference']) ?></b></p>
    <p class="sous-titre">Un e-mail de confirmation (simulé) a été envoyé à <?= h($r['email']) ?>.
        Paiement par carte se terminant par <?= h($r['cb_4_derniers']) ?>.</p>

    <div class="billets">
        <?php foreach ($r['billets'] as $b): ?>
        <div class="billet-mini">
            <span class="badge"><?= $b['sens'] ?></span>
            <p><b><?= h($b['gare_depart']) ?> → <?= h($b['gare_arrivee']) ?></b></p>
            <p><?= date_fr($b['date_depart']) ?>, <?= heure($b['heure_depart']) ?></p>
            <p>TGV <?= h($b['numero_train']) ?>, voiture <?= $b['voiture'] ?>, place <?= $b['place'] ?></p>
            <p class="numero">Billet n° <?= h($b['numero_billet']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <a class="bouton bouton-plein" href="billet.php?ref=<?= urlencode($r['reference']) ?>" target="_blank">Télécharger mes billets (PDF)</a>
</section>

<section class="carte">
    <h2>Détail du paiement</h2>
    <?php afficher_recap($r); ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

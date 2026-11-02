<?php
/**
 * recapitulatif.php - Récapitulatif après validation du panier.
 * Epic 2 - US2 : valider le panier et voir le récapitulatif de la réservation.
 */
require_once __DIR__ . '/includes/fonctions.php';
require_once __DIR__ . '/includes/recap.php';
exiger_panier();

$c = calculer_panier();

$titre = 'Récapitulatif';
$etape = 3;
require __DIR__ . '/includes/header.php';
?>
<h1 class="titre-page">Récapitulatif de votre réservation</h1>
<p class="sous-titre">Vérifiez vos trajets et vos options avant de renseigner vos informations.</p>

<section class="carte">
    <?php afficher_recap($c); ?>
    <div class="actions">
        <a class="bouton" href="panier.php"> Modifier le panier</a>
        <a class="bouton bouton-plein" href="voyageur.php">Saisir mes informations</a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
/**
 * options.php - Choix des options payantes.
 * Epic 1 - US5 : place tranquille, prise électrique, bagage volumineux,
 * information SMS, garantie annulation et trajet perturbé.
 */
require_once __DIR__ . '/includes/fonctions.php';
exiger_panier();

$options = liste_options();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();
    // On ne garde que des identifiants d'options qui existent vraiment en base
    $ids = array_map('intval', $_POST['options'] ?? []);
    $_SESSION['panier']['options'] = array_values(array_intersect($ids, array_map('intval', array_column($options, 'id_option'))));
    rediriger('panier.php');
}

$choisies = panier()['options'] ?? [];
$c = calculer_panier();

$titre = 'Options';
$etape = 2;
require __DIR__ . '/includes/header.php';
?>
<h1 class="titre-page">Personnalisez votre voyage</h1>
<p class="sous-titre">Les options s'appliquent à toute la réservation<?= count($c['billets']) > 1 ? ', aller et retour' : '' ?>.</p>

<div class="deux-colonnes">
    <form method="post" class="carte">
        <?= csrf_champ() ?>
        <div class="liste-options">
            <?php foreach ($options as $o): ?>
                <label class="option">
                    <input type="checkbox" name="options[]" value="<?= $o['id_option'] ?>" data-prix="<?= $o['prix'] ?>"
                        <?= in_array((int)$o['id_option'], $choisies, true) ? 'checked' : '' ?>>
                    <span class="libelle"><?= h($o['libelle']) ?></span>
                    <span class="prix-option">+ <?= prix((float)$o['prix']) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="actions">
            <a class="bouton" href="javascript:history.back()"> Retour</a>
            <button class="bouton bouton-plein" type="submit">Voir mon panier</button>
        </div>
    </form>

    <aside class="carte resume">
        <h2>Vos billets</h2>
        <?php foreach ($c['billets'] as $b): ?>
            <div class="resume-billet">
                <span class="badge"><?= $b['sens'] ?></span>
                <p><b><?= h($b['gare_depart']) ?> → <?= h($b['gare_arrivee']) ?></b><br>
                   <?= date_fr($b['date_depart']) ?>, <?= heure($b['heure_depart']) ?></p>
                <span><?= prix((float)$b['prix']) ?></span>
            </div>
        <?php endforeach; ?>
        <p class="total">Total estimé <b id="total-options" data-base="<?= array_sum(array_column($c['billets'], 'prix')) ?>"><?= prix($c['brut']) ?></b></p>
    </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

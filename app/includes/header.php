<?php
/**
 * header.php - En-tête commun : <head>, barre de navigation, ligne d'étapes.
 *
 * Variables attendues avant l'include :
 *   $titre   : titre de la page
 *   $etape   : numéro de l'étape de réservation (0 = pas de ligne d'étapes)
 *   $racine  : chemin vers le dossier app/ ('' ou '../' pour l'admin)
 *   $accueil : true sur la page d'accueil (pleine largeur, barre transparente)
 */
$titre  = $titre  ?? NOM_SITE;
$etape  = $etape  ?? 0;
$racine = $racine ?? '';
$nbArticles = count(array_filter([panier()['aller'] ?? null, panier()['retour'] ?? null]));
// Les étapes de réservation sont dessinées comme les gares d'une ligne de train
$etapes = [1 => 'Trains', 2 => 'Options', 3 => 'Panier', 4 => 'Voyageur', 5 => 'Paiement'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($titre) ?> | <?= NOM_SITE ?></title>
    <link rel="preload" href="<?= $racine ?>assets/fonts/bricolage.woff2" as="font" type="font/woff2" crossorigin>
    <!-- ?v=date du fichier : force le navigateur à recharger le CSS après chaque modification -->
    <link rel="stylesheet" href="<?= $racine ?>assets/style.css?v=<?= filemtime(__DIR__ . '/../assets/style.css') ?>">
</head>
<body class="<?= !empty($accueil) ? 'page-accueil' : '' ?>">
<header class="barre">
    <a class="logo" href="<?= $racine ?>index.php" aria-label="europe.transports, accueil">
        <svg viewBox="0 0 40 40" aria-hidden="true">
            <circle cx="20" cy="20" r="19" fill="#FFC83D"/>
            <path d="M8 25c0-6 6-11 16-11 5 0 8 2 8 5v6z" fill="#0E1B3D"/>
            <path d="M13 19h5v3h-5zM20 18h5v4h-5z" fill="#FFC83D"/>
            <path d="M6 29h28" stroke="#0E1B3D" stroke-width="2"/>
        </svg>
        <span>europe.transports</span>
    </a>
    <nav aria-label="Navigation principale">
        <a href="<?= $racine ?>index.php">Réserver</a>
        <a href="<?= $racine ?>panier.php" class="lien-panier">Panier<?php if ($nbArticles): ?> <b><?= $nbArticles ?></b><?php endif; ?></a>
        <a href="<?= $racine ?>admin/index.php" class="discret">Espace admin</a>
    </nav>
</header>

<?php if ($etape > 0): ?>
<nav class="ligne-etapes" aria-label="Étapes de la réservation">
    <ol>
        <?php foreach ($etapes as $n => $nom): ?>
            <li class="<?= $n < $etape ? 'fait' : ($n === $etape ? 'actif' : '') ?>" <?= $n === $etape ? 'aria-current="step"' : '' ?>>
                <span class="gare"></span><?= $nom ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
<?php endif; ?>

<main class="<?= empty($accueil) ? 'contenu' : '' ?>">
<?php if ($msgFlash = flash()): ?>
    <div class="alerte alerte-<?= h($msgFlash['type']) ?>" role="status"><?= h($msgFlash['texte']) ?></div>
<?php endif; ?>

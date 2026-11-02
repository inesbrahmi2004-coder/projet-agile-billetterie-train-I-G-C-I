<?php
/**
 * recherche.php - Calendrier des prix et choix des horaires.
 * Epic 1 - US3 (calendrier des prix) et US4 (choix du prix, des dates et des heures).
 *
 * Paramètres GET : dep, arr, type (ar|as), aller, retour
 *                  ja / jr = jour choisi dans le calendrier aller / retour
 */
require_once __DIR__ . '/includes/fonctions.php';

const DATE_MIN = '2026-11-01';   // période couverte par les données de démonstration
const DATE_MAX = '2027-01-31';

/** Vérifie qu'une chaîne est une date AAAA-MM-JJ valide dans la période. */
function date_valide(?string $d): ?string
{
    $dt = DateTime::createFromFormat('!Y-m-d', (string)$d);
    if (!$dt || $dt->format('Y-m-d') !== $d) return null;
    return max(DATE_MIN, min(DATE_MAX, $d));
}

/* ---- 1. Lecture et contrôle des critères de recherche ---- */
$dep = (int)($_GET['dep'] ?? 0);
$arr = (int)($_GET['arr'] ?? 0);
$allerRetour = ($_GET['type'] ?? 'ar') === 'ar';
$dateAller  = date_valide($_GET['aller'] ?? '');
$dateRetour = $allerRetour ? date_valide($_GET['retour'] ?? '') : null;

$erreur = null;
if (!nom_gare($dep) || !nom_gare($arr)) $erreur = 'Choisissez une gare de départ et une gare d\'arrivée.';
elseif ($dep === $arr)                   $erreur = 'La gare d\'arrivée doit être différente de la gare de départ.';
elseif (!$dateAller)                     $erreur = 'La date d\'aller est invalide.';
elseif ($allerRetour && !$dateRetour)    $erreur = 'Indiquez une date de retour.';
elseif ($allerRetour && $dateRetour < $dateAller) $erreur = 'Le retour doit être après l\'aller.';
if ($erreur) {
    flash($erreur, 'erreur');
    rediriger('index.php');
}
$_SESSION['recherche'] = ['dep' => $dep, 'arr' => $arr, 'aller' => $dateAller, 'retour' => $dateRetour];

// Jour affiché dans chaque calendrier (par défaut : la date demandée)
$jourAller  = date_valide($_GET['ja'] ?? '') ?? $dateAller;
$jourRetour = $allerRetour ? (date_valide($_GET['jr'] ?? '') ?? $dateRetour) : null;

/* ---- 2. Validation du choix des trains (formulaire POST) ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();
    $va = voyage((int)($_POST['voyage_aller'] ?? 0));
    $vr = $allerRetour ? voyage((int)($_POST['voyage_retour'] ?? 0)) : null;

    if (!$va || $va['id_gare_depart'] != $dep || $va['id_gare_arrivee'] != $arr) {
        flash('Sélectionnez un train pour l\'aller.', 'erreur');
    } elseif ($allerRetour && (!$vr || $vr['id_gare_depart'] != $arr || $vr['id_gare_arrivee'] != $dep)) {
        flash('Sélectionnez un train pour le retour.', 'erreur');
    } elseif ($vr && $vr['date_depart'] . $vr['heure_depart'] <= $va['date_depart'] . $va['heure_arrivee']) {
        flash('Le train retour doit partir après l\'arrivée du train aller.', 'erreur');
    } else {
        // Nouveau choix de trains : on garde les options déjà cochées
        $_SESSION['panier'] = [
            'aller'   => (int)$va['id_voyage'],
            'retour'  => $vr ? (int)$vr['id_voyage'] : null,
            'options' => panier()['options'] ?? [],
        ];
        rediriger('options.php');
    }
    rediriger($_SERVER['REQUEST_URI']);
}

/* ---- 3. Données des calendriers ---- */

/** Prix minimum par jour sur 7 jours centrés sur $jour. */
function calendrier(int $dep, int $arr, string $jour): array
{
    $debut = (new DateTime($jour))->modify('-3 days');
    $fin = (new DateTime($jour))->modify('+3 days');
    $st = db()->prepare(
        'SELECT date_depart, MIN(prix) AS prix_min
           FROM voyage
          WHERE id_gare_depart = ? AND id_gare_arrivee = ? AND date_depart BETWEEN ? AND ?
            AND places_dispo > 0
          GROUP BY date_depart');
    $st->execute([$dep, $arr, $debut->format('Y-m-d'), $fin->format('Y-m-d')]);
    $prix = array_column($st->fetchAll(), 'prix_min', 'date_depart');

    $jours = [];
    for ($d = clone $debut; $d <= $fin; $d->modify('+1 day')) {
        $k = $d->format('Y-m-d');
        $jours[$k] = isset($prix[$k]) ? (float)$prix[$k] : null;
    }
    return $jours;
}

/** Trains d'un jour donné, triés par heure. */
function trains_du_jour(int $dep, int $arr, string $jour): array
{
    $st = db()->prepare(
        'SELECT * FROM voyage
          WHERE id_gare_depart = ? AND id_gare_arrivee = ? AND date_depart = ? AND places_dispo > 0
          ORDER BY heure_depart');
    $st->execute([$dep, $arr, $jour]);
    return $st->fetchAll();
}

/** URL de cette page en modifiant un paramètre (ja ou jr). */
function url_jour(string $cle, string $jour): string
{
    $q = $_GET;
    $q[$cle] = $jour;
    return 'recherche.php?' . http_build_query($q);
}

/** Affiche un bloc « calendrier + horaires » pour un sens de trajet. */
function bloc_trajet(string $sens, int $de, int $a, string $jour, string $cle, ?int $choixActuel): void
{
    $cal = calendrier($de, $a, $jour);
    $prixConnus = array_filter($cal, fn($p) => $p !== null);
    $min = $prixConnus ? min($prixConnus) : null;
    $trains = trains_du_jour($de, $a, $jour);
    $minJour = $trains ? min(array_column($trains, 'prix')) : null;
    // Si le train déjà choisi n'est pas ce jour-là, on présélectionne le moins cher du jour
    if (!in_array($choixActuel, array_map('intval', array_column($trains, 'id_voyage')), true)) {
        $choixActuel = null;
    }
    $prec = (new DateTime($jour))->modify('-7 days')->format('Y-m-d');
    $suiv = (new DateTime($jour))->modify('+7 days')->format('Y-m-d');
    $nomChamp = $sens === 'ALLER' ? 'voyage_aller' : 'voyage_retour';
    ?>
    <section class="carte trajet">
        <header class="trajet-titre">
            <span class="badge"><?= $sens ?></span>
            <h2><?= h(nom_gare($de)) ?> <span class="fleche">→</span> <?= h(nom_gare($a)) ?></h2>
        </header>

        <h3>Calendrier des prix</h3>
        <div class="calendrier">
            <a class="nav-semaine" href="<?= h(url_jour($cle, $prec)) ?>#<?= $cle ?>" title="Semaine précédente">‹</a>
            <?php foreach ($cal as $d => $p): ?>
                <?php $classes = ($d === $jour ? 'choisi ' : '') . ($p !== null && $p == $min ? 'moins-cher' : '') . ($p === null ? ' vide' : ''); ?>
                <a class="jour <?= trim($classes) ?>" href="<?= $p !== null ? h(url_jour($cle, $d)) . '#' . $cle : '#' ?>" id="<?= $d === $jour ? $cle : '' ?>">
                    <span class="nom-jour"><?= date_fr($d, true) ?></span>
                    <span class="prix-jour"><?= $p !== null ? '<small class="des">dès </small>' . prix($p) : '—' ?></span>
                    <?php if ($p !== null && $p == $min): ?><span class="etiquette">Meilleur prix</span><?php endif; ?>
                </a>
            <?php endforeach; ?>
            <a class="nav-semaine" href="<?= h(url_jour($cle, $suiv)) ?>#<?= $cle ?>" title="Semaine suivante">›</a>
        </div>

        <h3>Horaires du <?= date_fr($jour) ?></h3>
        <?php if (!$trains): ?>
            <p class="vide">Aucun train disponible ce jour-là.</p>
        <?php else: ?>
        <div class="horaires">
            <?php foreach ($trains as $t): ?>
                <?php $coche = $choixActuel === (int)$t['id_voyage'] || (!$choixActuel && $t['prix'] == $minJour); ?>
                <label class="horaire <?= $t['prix'] == $minJour ? 'moins-cher' : '' ?>">
                    <input type="radio" name="<?= $nomChamp ?>" value="<?= $t['id_voyage'] ?>" <?= $coche ? 'checked' : '' ?> required>
                    <span class="heures"><b><?= heure($t['heure_depart']) ?></b> → <?= heure($t['heure_arrivee']) ?></span>
                    <span class="train">TGV <?= h($t['numero_train']) ?></span>
                    <span class="prix-train"><?= prix((float)$t['prix']) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php
}

$titre = 'Calendrier des prix';
$etape = 1;
require __DIR__ . '/includes/header.php';
?>
<h1 class="titre-page">Choisissez vos trains</h1>
<p class="sous-titre">Cliquez sur un jour du calendrier pour voir ses horaires. Le meilleur prix de la semaine est en vert.</p>

<form method="post" action="<?= h($_SERVER['REQUEST_URI']) ?>">
    <?= csrf_champ() ?>
    <?php bloc_trajet('ALLER', $dep, $arr, $jourAller, 'ja', panier()['aller'] ?? null); ?>
    <?php if ($allerRetour) bloc_trajet('RETOUR', $arr, $dep, $jourRetour, 'jr', panier()['retour'] ?? null); ?>

    <div class="actions">
        <a class="bouton" href="index.php"> Modifier la recherche</a>
        <button class="bouton bouton-plein" type="submit">Continuer vers les options</button>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>

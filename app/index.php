<?php
/**
 * index.php - Page d'accueil et formulaire de recherche.
 * Epic 1 - US1 (site accessible) et US2 (aller/retour entre deux gares).
 *
 * La page affiche aussi un « tableau des départs » : pour chaque ligne au
 * départ de Paris, le train le moins cher des 30 prochains jours.
 */
require_once __DIR__ . '/includes/fonctions.php';
require_once __DIR__ . '/includes/illustrations.php';

$gares = liste_gares();
$r = $_SESSION['recherche'] ?? [];          // pré-remplissage avec la dernière recherche
$dep = (int)($r['dep'] ?? 1);               // Paris Gare de Lyon par défaut
$arr = (int)($r['arr'] ?? 2);               // Lyon Part-Dieu par défaut
$aller = $r['aller'] ?? '2026-12-07';
$retour = $r['retour'] ?? '2026-12-15';
$allerRetour = !array_key_exists('retour', $r) || $r['retour'] !== null;

/* ---- Tableau des départs : meilleur prix par ligne au départ de Paris ---- */
$debut = max(date('Y-m-d'), '2026-11-01');                 // début des données de démonstration
$fin = date('Y-m-d', strtotime($debut . ' +30 days'));
$st = db()->prepare(
    "SELECT v.*, gd.nom AS gare_depart, ga.nom AS gare_arrivee, ga.ville AS ville_arrivee,
            TIMESTAMPDIFF(MINUTE, v.heure_depart, v.heure_arrivee) AS duree
       FROM voyage v
       JOIN gare gd ON gd.id_gare = v.id_gare_depart
       JOIN gare ga ON ga.id_gare = v.id_gare_arrivee
       JOIN (SELECT id_gare_depart, id_gare_arrivee, MIN(prix) AS prix_min
               FROM voyage WHERE date_depart BETWEEN ? AND ? GROUP BY id_gare_depart, id_gare_arrivee) m
         ON m.id_gare_depart = v.id_gare_depart AND m.id_gare_arrivee = v.id_gare_arrivee AND m.prix_min = v.prix
      WHERE gd.ville = 'Paris' AND v.date_depart BETWEEN ? AND ?
      ORDER BY v.prix, v.date_depart, v.heure_depart");
$st->execute([$debut, $fin, $debut, $fin]);
$departs = [];
foreach ($st->fetchAll() as $v) {                         // un seul train (le 1er) par ligne
    $departs[$v['id_gare_depart'] . '-' . $v['id_gare_arrivee']] ??= $v;
}

// Cadrage de chaque photo dans la carte (garde le monument visible quand la photo est recadrée)
const CADRAGE_PHOTOS = [
    'lyon-1' => '60% 50%', 'lyon-2' => '72% 50%', 'lyon-3' => '78% 50%',
    'bordeaux-1' => '22% 50%', 'bordeaux-2' => '50% 50%', 'bordeaux-4' => '45% 50%',
    'marseille-1' => '30% 50%', 'marseille-2' => '58% 50%', 'marseille-3' => '62% 50%',
];

$titre = 'Réservez votre TGV';
$accueil = true;
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <!-- Illustration : un TGV traverse un viaduc au coucher du soleil -->
    <svg class="scene" viewBox="0 0 1600 720" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
        <defs>
            <linearGradient id="ciel" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#0E1B3D"/><stop offset=".42" stop-color="#2E2C6A"/>
                <stop offset=".66" stop-color="#9C4C7E"/><stop offset=".82" stop-color="#EE8650"/>
                <stop offset="1" stop-color="#FFC27A"/>
            </linearGradient>
            <radialGradient id="halo"><stop offset="0" stop-color="#FFE9B0" stop-opacity=".9"/><stop offset="1" stop-color="#FFB45E" stop-opacity="0"/></radialGradient>
            <mask id="arches">
                <rect width="1600" height="720" fill="#fff"/>
                <?php for ($x = 10; $x < 1600; $x += 150): ?>
                    <path d="M<?= $x ?> 720V650a62 62 0 0 1 124 0V720z" fill="#000"/>
                <?php endfor; ?>
            </mask>
        </defs>
        <rect width="1600" height="720" fill="url(#ciel)"/>
        <g fill="#fff" opacity=".7">
            <circle cx="140" cy="70" r="1.6"/><circle cx="420" cy="40" r="1.2"/><circle cx="610" cy="110" r="1.4"/>
            <circle cx="930" cy="60" r="1.8"/><circle cx="1240" cy="95" r="1.3"/><circle cx="1460" cy="45" r="1.6"/>
            <circle cx="780" cy="190" r="1"/><circle cx="1350" cy="210" r="1.1"/><circle cx="260" cy="200" r="1"/>
        </g>
        <circle cx="1170" cy="520" r="240" fill="url(#halo)"/>
        <circle cx="1170" cy="520" r="72" fill="#FFE6A6"/>
        <path d="M0 520 120 452 230 492 360 404 470 470 600 424 720 482 860 392 1000 470 1120 432 1260 498 1400 418 1520 468 1600 440V720H0z" fill="#6E4A88" opacity=".55"/>
        <path d="M0 585c150-62 260-44 380-84 140-46 260 40 400 10s260 50 420 20 260 10 400-16V720H0z" fill="#3A2B62"/>
        <!-- Caténaire : poteaux et fil -->
        <g stroke="#0E1B3D" stroke-width="3" fill="none">
            <?php for ($x = 60; $x < 1600; $x += 210): ?>
                <path d="M<?= $x ?> 556V470h26"/>
            <?php endfor; ?>
            <path d="M0 482Q105 490 210 482T420 482T630 482T840 482T1050 482T1260 482T1470 482T1680 482" stroke-width="1.5"/>
        </g>
        <!-- TGV -->
        <g class="tgv">
            <g transform="translate(330 0)">
                <?php foreach ([-60, 110, 280, 450, 620] as $x): ?>
                    <rect x="<?= $x ?>" y="512" width="164" height="40" rx="7" fill="#F4F6FB"/>
                    <rect x="<?= $x + 8 ?>" y="520" width="148" height="11" rx="3" fill="#1C2A57"/>
                    <rect x="<?= $x ?>" y="538" width="164" height="4" fill="#FFC83D"/>
                <?php endforeach; ?>
                <path d="M-150 552c12-24 40-40 90-40h10v40z" fill="#F4F6FB"/>
                <path d="M-128 534c10-10 28-16 56-16h10v13h-58z" fill="#1C2A57"/>
                <rect x="-150" y="538" width="100" height="4" fill="#FFC83D"/>
                <path d="M784 512h70c40 0 68 18 82 40H784z" fill="#F4F6FB"/>
                <path d="M792 518h62c24 0 42 6 54 16h-116z" fill="#1C2A57"/>
                <rect x="784" y="538" width="150" height="4" fill="#FFC83D"/>
                <path d="M820 512l14-16h-14l20-14" stroke="#0E1B3D" stroke-width="2.5" fill="none"/>
            </g>
        </g>
        <!-- Viaduc -->
        <g mask="url(#arches)">
            <rect y="556" width="1600" height="164" fill="#1A1F48"/>
        </g>
        <rect y="552" width="1600" height="8" fill="#0E1B3D"/>
        <path d="M0 690c40-26 70-30 110-12 30-28 70-30 100-8 40-24 90-20 120 6 30-14 60-14 90 4V720H0zM1240 700c30-24 70-26 100-6 30-22 70-20 100 2 40-20 80-18 120 6 20-8 30-8 40-4V720h-360z" fill="#0E1B3D"/>
    </svg>

    <div class="hero-texte">
        <h1>Prenez le TGV au prix le plus bas de la semaine.</h1>
        <p>Comparez les tarifs jour par jour, choisissez votre horaire et imprimez vos billets en PDF.</p>
    </div>

    <form class="billet-recherche" action="recherche.php" method="get">
        <fieldset class="type-trajet">
            <legend class="visuellement-cache">Type de trajet</legend>
            <label><input type="radio" name="type" value="ar" <?= $allerRetour ? 'checked' : '' ?>><span>Aller-retour</span></label>
            <label><input type="radio" name="type" value="as" <?= $allerRetour ? '' : 'checked' ?>><span>Aller simple</span></label>
        </fieldset>

        <div class="champs">
            <div class="champ gare-champ">
                <label for="dep">Départ</label>
                <select id="dep" name="dep" required>
                    <?php foreach ($gares as $g): ?>
                        <option value="<?= $g['id_gare'] ?>" <?= $g['id_gare'] == $dep ? 'selected' : '' ?>><?= h($g['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" class="inverser" id="inverser" aria-label="Inverser départ et arrivée">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4 3 8l4 4M3 8h14M17 20l4-4-4-4M21 16H7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="champ gare-champ">
                <label for="arr">Arrivée</label>
                <select id="arr" name="arr" required>
                    <?php foreach ($gares as $g): ?>
                        <option value="<?= $g['id_gare'] ?>" <?= $g['id_gare'] == $arr ? 'selected' : '' ?>><?= h($g['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="champ">
                <label for="aller">Aller le</label>
                <input type="date" id="aller" name="aller" value="<?= h($aller) ?>" min="2026-11-01" max="2027-01-31" required>
            </div>
            <div class="champ" id="bloc-retour">
                <label for="retour">Retour le</label>
                <input type="date" id="retour" name="retour" value="<?= h($retour) ?>" min="2026-11-01" max="2027-01-31">
            </div>
        </div>

        <div class="talon">
            <button class="bouton bouton-plein" type="submit">Voir les prix</button>
        </div>
    </form>
</section>

<section class="tableau-departs" aria-labelledby="titre-departs">
    <div class="entete-departs">
        <h2 id="titre-departs">Prochains départs depuis Paris</h2>
        <p>Le meilleur prix des 30 prochains jours sur chaque ligne.</p>
    </div>
    <div class="panneau">
        <div class="panneau-ligne panneau-titres" aria-hidden="true">
            <span>Départ</span><span>Destination</span><span>Durée</span><span>Dès</span><span></span>
        </div>
        <?php foreach ($departs as $v): ?>
            <?php $retourPropose = min(date('Y-m-d', strtotime($v['date_depart'] . ' +3 days')), '2027-01-31'); ?>
            <a class="panneau-ligne" href="recherche.php?<?= h(http_build_query(['type' => 'ar', 'dep' => $v['id_gare_depart'], 'arr' => $v['id_gare_arrivee'], 'aller' => $v['date_depart'], 'retour' => $retourPropose])) ?>">
                <span class="quand"><b><?= heure($v['heure_depart']) ?></b><small><?= date_fr($v['date_depart'], true) ?></small></span>
                <span class="ou"><b><?= h($v['ville_arrivee']) ?></b><small>de <?= h($v['gare_depart']) ?></small></span>
                <span class="duree"><?= intdiv((int)$v['duree'], 60) ?> h <?= sprintf('%02d', $v['duree'] % 60) ?></span>
                <span class="tarif"><?= prix((float)$v['prix']) ?></span>
                <span class="voir">Réserver</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="destinations" aria-labelledby="titre-destinations">
    <h2 id="titre-destinations">Où partir en TGV ?</h2>
    <div class="grille-destinations">
        <?php foreach ($departs as $v): ?>
            <?php
            // Nom de la ville sans accent ni majuscule (ex. « lyon »)
            $slug = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $v['ville_arrivee']));
            // Photos de la ville : assets/img/<ville>-1.jpg, <ville>-2.jpg... (elles défilent)
            $photos = glob(__DIR__ . "/assets/img/$slug-*.jpg") ?: [];
            natsort($photos);
            $retourPropose = min(date('Y-m-d', strtotime($v['date_depart'] . ' +3 days')), '2027-01-31');
            ?>
            <a class="destination destination-<?= h($slug) ?>"
               href="recherche.php?<?= h(http_build_query(['type' => 'ar', 'dep' => $v['id_gare_depart'], 'arr' => $v['id_gare_arrivee'], 'aller' => $v['date_depart'], 'retour' => $retourPropose])) ?>">
                <?php if ($photos): ?>
                    <?php $n = 0; foreach ($photos as $fichier): $nom = basename($fichier, '.jpg'); ?>
                        <img class="destination-photo <?= $n++ === 0 ? 'visible' : '' ?>" src="assets/img/<?= h(basename($fichier)) ?>"
                             alt="" loading="lazy" style="object-position: <?= CADRAGE_PHOTOS[$nom] ?? '50% 50%' ?>">
                    <?php endforeach; ?>
                <?php else: ?>
                    <?= illustration_ville($slug) ?>
                <?php endif; ?>
                <span class="destination-texte">
                    <b><?= h($v['ville_arrivee']) ?></b>
                    <span><?= intdiv((int)$v['duree'], 60) ?> h <?= sprintf('%02d', $v['duree'] % 60) ?> depuis Paris</span>
                </span>
                <span class="destination-prix">dès <?= prix((float)$v['prix']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="adherent" aria-labelledby="titre-adherent">
    <div class="adherent-texte">
        <h2 id="titre-adherent">Adhérent ? 10 € de moins.</h2>
        <p>Au paiement, saisissez votre numéro de carte d'abonnement et votre code promo. La réduction s'applique à toute la réservation.</p>
    </div>
    <!-- Carte d'abonnement dessinée, dans les couleurs du ciel de l'accueil -->
    <div class="carte-abonnement" aria-hidden="true">
        <svg class="carte-ligne" viewBox="0 0 300 100" preserveAspectRatio="none"><path d="M0 70C60 40 110 90 170 60S260 20 300 40" fill="none" stroke="rgba(255,255,255,.25)" stroke-width="2"/><path d="M0 85C70 60 120 100 190 75S270 45 300 58" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="2"/></svg>
        <div class="carte-haut">
            <svg viewBox="0 0 40 40" class="carte-logo" width="38" height="38"><circle cx="20" cy="20" r="19" fill="#FFC83D"/><path d="M8 25c0-6 6-11 16-11 5 0 8 2 8 5v6z" fill="#0E1B3D"/><path d="M13 19h5v3h-5zM20 18h5v4h-5z" fill="#FFC83D"/><path d="M6 29h28" stroke="#0E1B3D" stroke-width="2"/></svg>
            <span class="carte-reduc">−10 €</span>
        </div>
        <span class="carte-titre">Carte Adhérent</span>
        <span class="carte-numero">EU-2026-0001</span>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

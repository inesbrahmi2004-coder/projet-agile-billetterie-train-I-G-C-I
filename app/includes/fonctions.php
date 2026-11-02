<?php
/**
 * fonctions.php - Fonctions communes à toutes les pages.
 *
 * Le panier est conservé dans la session PHP ($_SESSION['panier']).
 * On n'y stocke que des identifiants : les prix sont TOUJOURS relus
 * dans la base au moment du calcul, pour qu'un utilisateur ne puisse
 * pas modifier un prix depuis son navigateur.
 */
require_once __DIR__ . '/db.php';

// Session sécurisée : cookie inaccessible au JavaScript, envoyé seulement par notre site
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/* ---------------------------------------------------------------------
 *  Affichage
 * ------------------------------------------------------------------- */

/** Échappe un texte avant de l'afficher dans le HTML (protection XSS). */
function h(?string $texte): string
{
    return htmlspecialchars($texte ?? '', ENT_QUOTES, 'UTF-8');
}

/** 45.5 -> "45,50 €" */
function prix(float $montant): string
{
    return number_format($montant, 2, ',', ' ') . ' €';
}

/** "2026-12-07" -> "lundi 7 décembre 2026" */
function date_fr(string $dateIso, bool $court = false): string
{
    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $mois  = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet',
              'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $d = new DateTime($dateIso);
    if ($court) {
        // Abréviations usuelles en français : « lun. 7 déc. »
        $moisCourts = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
        return mb_substr($jours[(int)$d->format('w')], 0, 3) . '. ' . $d->format('j') . ' ' . $moisCourts[(int)$d->format('n')];
    }
    return $jours[(int)$d->format('w')] . ' ' . $d->format('j') . ' ' . $mois[(int)$d->format('n')] . ' ' . $d->format('Y');
}

/** "08:00:00" -> "08h00" */
function heure(string $heure): string
{
    return substr($heure, 0, 2) . 'h' . substr($heure, 3, 2);
}

/** Redirige vers une autre page et arrête le script. */
function rediriger(string $page): void
{
    header('Location: ' . $page);
    exit;
}

/** Message affiché une seule fois sur la page suivante. */
function flash(?string $message = null, string $type = 'info'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['texte' => $message, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---------------------------------------------------------------------
 *  Protection CSRF : chaque formulaire POST contient un jeton secret
 *  lié à la session. Un site pirate ne peut pas le deviner.
 * ------------------------------------------------------------------- */
function csrf_jeton(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_champ(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_jeton() . '">';
}

function csrf_verifier(): void
{
    if (!hash_equals(csrf_jeton(), $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Requête invalide (jeton de sécurité manquant). Rechargez la page.');
    }
}

/* ---------------------------------------------------------------------
 *  Accès aux données
 * ------------------------------------------------------------------- */

function liste_gares(): array
{
    return db()->query('SELECT id_gare, nom, ville FROM gare ORDER BY ville, nom')->fetchAll();
}

function nom_gare(int $id): string
{
    $st = db()->prepare('SELECT nom FROM gare WHERE id_gare = ?');
    $st->execute([$id]);
    return (string)$st->fetchColumn();
}

/** Un voyage avec le nom de ses gares. */
function voyage(int $id): ?array
{
    $st = db()->prepare(
        'SELECT v.*, gd.nom AS gare_depart, ga.nom AS gare_arrivee
           FROM voyage v
           JOIN gare gd ON gd.id_gare = v.id_gare_depart
           JOIN gare ga ON ga.id_gare = v.id_gare_arrivee
          WHERE v.id_voyage = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function liste_options(): array
{
    return db()->query('SELECT * FROM option_voyage ORDER BY id_option')->fetchAll();
}

/* ---------------------------------------------------------------------
 *  Panier (session)
 * ------------------------------------------------------------------- */

function panier(): array
{
    return $_SESSION['panier'] ?? [];
}

/** Bloque l'accès à une étape si l'étape précédente n'est pas faite. */
function exiger_panier(bool $voyageur = false): void
{
    $p = panier();
    if (empty($p['aller'])) {
        flash('Commencez par choisir vos trains.', 'erreur');
        rediriger('index.php');
    }
    if ($voyageur && empty($p['voyageur'])) {
        flash('Renseignez d\'abord vos informations.', 'erreur');
        rediriger('voyageur.php');
    }
}

/**
 * Calcule le contenu et le montant du panier à partir de la base.
 * Retourne : billets, options, brut, remise, total.
 */
function calculer_panier(): array
{
    $p = panier();
    $billets = [];
    foreach (['aller' => 'ALLER', 'retour' => 'RETOUR'] as $cle => $sens) {
        if (!empty($p[$cle]) && ($v = voyage((int)$p[$cle]))) {
            $v['sens'] = $sens;
            $billets[] = $v;
        }
    }

    $options = [];
    if (!empty($p['options'])) {
        $marques = implode(',', array_fill(0, count($p['options']), '?'));
        $st = db()->prepare("SELECT * FROM option_voyage WHERE id_option IN ($marques) ORDER BY id_option");
        $st->execute(array_values($p['options']));
        $options = $st->fetchAll();
    }

    $brut = array_sum(array_column($billets, 'prix')) + array_sum(array_column($options, 'prix'));
    $remise = (float)($p['reduction']['montant'] ?? 0);
    $remise = min($remise, $brut);       // le total ne peut pas être négatif

    return [
        'billets' => $billets,
        'options' => $options,
        'brut'    => round($brut, 2),
        'remise'  => round($remise, 2),
        'total'   => round($brut - $remise, 2),
    ];
}

/**
 * Charge une réservation payée (billets, options, client).
 * Accès limité : la session qui l'a payée, ou un administrateur.
 */
function charger_reservation(string $reference): ?array
{
    $autorise = in_array($reference, $_SESSION['mes_reservations'] ?? [], true)
             || (($_SESSION['admin']['role'] ?? '') === 'admin');
    if (!$autorise) return null;

    $st = db()->prepare(
        'SELECT r.*, c.civilite, c.nom, c.prenom, c.telephone, c.email, u.numero_carte
           FROM reservation r
           JOIN client c ON c.id_client = r.id_client
           LEFT JOIN utilisateur u ON u.id_utilisateur = r.id_utilisateur
          WHERE r.reference = ?');
    $st->execute([$reference]);
    $r = $st->fetch();
    if (!$r) return null;

    $st = db()->prepare(
        "SELECT b.*, v.numero_train, v.date_depart, v.heure_depart, v.heure_arrivee,
                gd.nom AS gare_depart, ga.nom AS gare_arrivee
           FROM billet b
           JOIN voyage v ON v.id_voyage = b.id_voyage
           JOIN gare gd ON gd.id_gare = v.id_gare_depart
           JOIN gare ga ON ga.id_gare = v.id_gare_arrivee
          WHERE b.id_reservation = ?
          ORDER BY FIELD(b.sens, 'ALLER', 'RETOUR')");
    $st->execute([$r['id_reservation']]);
    $r['billets'] = $st->fetchAll();

    $st = db()->prepare(
        'SELECT o.libelle, ro.prix_applique AS prix
           FROM reservation_option ro JOIN option_voyage o ON o.id_option = ro.id_option
          WHERE ro.id_reservation = ? ORDER BY o.id_option');
    $st->execute([$r['id_reservation']]);
    $r['options'] = $st->fetchAll();

    $r['brut'] = (float)$r['montant_brut'];
    $r['remise'] = (float)$r['remise'];
    $r['total'] = (float)$r['montant_total'];
    return $r;
}

/* ---------------------------------------------------------------------
 *  Administration
 * ------------------------------------------------------------------- */

function exiger_admin(): void
{
    if (($_SESSION['admin']['role'] ?? '') !== 'admin') {
        rediriger('index.php');
    }
}

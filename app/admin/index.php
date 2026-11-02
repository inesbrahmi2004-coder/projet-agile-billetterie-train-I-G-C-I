<?php
/**
 * admin/index.php - Connexion de l'administrateur système.
 * Epic 5 - TS1 : l'administrateur se connecte à son espace.
 * Epic 5 - TS2 : après 2 tentatives ratées, message sur fond rouge
 *                « ATTENTION : C'est votre dernier essai... ».
 *                Au 3e échec, le compte est bloqué 5 minutes.
 * Epic 5 - TS3 : « l'œil » pour afficher le mot de passe en clair (voir app.js).
 */
require_once __DIR__ . '/../includes/fonctions.php';

const MAX_ESSAIS = 3;          // nombre d'essais avant blocage
const DUREE_BLOCAGE = 5;       // minutes

if (($_SESSION['admin']['role'] ?? '') === 'admin') rediriger('tableau.php');

$erreur = null;
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();
    $login = trim($_POST['login'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    $st = db()->prepare('SELECT * FROM utilisateur WHERE login = ?');
    $st->execute([$login]);
    $u = $st->fetch();

    if ($u && $u['bloque_jusqu'] && strtotime($u['bloque_jusqu']) > time()) {
        $minutes = (int)ceil((strtotime($u['bloque_jusqu']) - time()) / 60);
        $erreur = "Compte bloqué après " . MAX_ESSAIS . " essais. Réessayez dans $minutes min.";
        $_SESSION['essais_affiches'] = 0;
    } elseif ($u && $u['role'] === 'admin' && password_verify($motDePasse, $u['mot_de_passe'])) {
        // Connexion réussie : on remet le compteur à zéro
        db()->prepare('UPDATE utilisateur SET nb_echecs = 0, bloque_jusqu = NULL WHERE id_utilisateur = ?')->execute([$u['id_utilisateur']]);
        session_regenerate_id(true);      // nouvel identifiant de session (contre le vol de session)
        unset($_SESSION['echecs_inconnus'], $_SESSION['essais_affiches']);
        $_SESSION['admin'] = ['id' => (int)$u['id_utilisateur'], 'role' => 'admin', 'nom' => $u['prenom'] . ' ' . $u['nom']];
        rediriger('tableau.php');
    } else {
        // Échec : on compte l'essai (en base pour un compte existant, en session sinon)
        if ($u) {
            $echecs = (int)$u['nb_echecs'] + 1;
            $blocage = $echecs >= MAX_ESSAIS ? date('Y-m-d H:i:s', time() + DUREE_BLOCAGE * 60) : null;
            db()->prepare('UPDATE utilisateur SET nb_echecs = ?, bloque_jusqu = ? WHERE id_utilisateur = ?')
                ->execute([$blocage ? 0 : $echecs, $blocage, $u['id_utilisateur']]);
        } else {
            $echecs = ($_SESSION['echecs_inconnus'] ?? 0) + 1;
            $_SESSION['echecs_inconnus'] = $echecs >= MAX_ESSAIS ? 0 : $echecs;
        }
        // Même message que le login existe ou non : on ne révèle pas les comptes existants
        $erreur = $echecs >= MAX_ESSAIS
            ? "Compte bloqué après " . MAX_ESSAIS . " essais. Réessayez dans " . DUREE_BLOCAGE . " min."
            : 'Identifiant ou mot de passe incorrect.';
        $_SESSION['essais_affiches'] = $echecs >= MAX_ESSAIS ? 0 : $echecs;
    }
}

// TS2 : avertissement après 2 tentatives ratées
$dernierEssai = ($_SESSION['essais_affiches'] ?? 0) === MAX_ESSAIS - 1;

$titre = 'Espace administrateur';
$racine = '../';
require __DIR__ . '/../includes/header.php';
?>
<section class="carte connexion">
    <h1>Espace administrateur</h1>
    <p class="sous-titre">Accès réservé à l'administrateur système.</p>

    <?php if ($dernierEssai): ?>
        <div class="alerte alerte-danger">ATTENTION : C'est votre dernier essai... Au prochain échec, le compte sera bloqué <?= DUREE_BLOCAGE ?> minutes.</div>
    <?php elseif ($erreur): ?>
        <div class="alerte alerte-erreur"><?= h($erreur) ?></div>
    <?php endif; ?>

    <form method="post" class="formulaire">
        <?= csrf_champ() ?>
        <div class="champ"><label for="login">Identifiant</label>
            <input id="login" name="login" value="<?= h($login) ?>" autocomplete="username" required autofocus></div>
        <div class="champ"><label for="mot_de_passe">Mot de passe</label>
            <div class="mdp">
                <input id="mot_de_passe" name="mot_de_passe" type="password" autocomplete="current-password" required>
                <button type="button" class="oeil" data-oeil="mot_de_passe" aria-label="Afficher le mot de passe"><svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/></svg></button>
            </div>
        </div>
        <button class="bouton bouton-plein large" type="submit">Se connecter</button>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

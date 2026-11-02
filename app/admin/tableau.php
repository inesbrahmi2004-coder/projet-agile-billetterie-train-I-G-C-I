<?php
/**
 * admin/tableau.php - Gestion des comptes adhérents par l'administrateur.
 * Epic 5 - TS4 : créer un compte utilisateur sur demande du voyageur.
 * Epic 5 - TS5 : supprimer un compte utilisateur s'il n'est plus adhérent.
 * En plus : consultation des dernières réservations.
 */
require_once __DIR__ . '/../includes/fonctions.php';
exiger_admin();

$erreurs = [];
$f = ['civilite' => 'M', 'nom' => '', 'prenom' => '', 'email' => '', 'login' => '', 'date_fin_carte' => date('Y-m-d', strtotime('+1 year'))];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();

    /* ---------- TS4 : création d'un compte adhérent ---------- */
    if (($_POST['action'] ?? '') === 'creer') {
        $f = [
            'civilite'       => $_POST['civilite'] ?? '',
            'nom'            => trim($_POST['nom'] ?? ''),
            'prenom'         => trim($_POST['prenom'] ?? ''),
            'email'          => trim(strtolower($_POST['email'] ?? '')),
            'login'          => trim(strtolower($_POST['login'] ?? '')),
            'date_fin_carte' => $_POST['date_fin_carte'] ?? '',
        ];
        $mdp = $_POST['mot_de_passe'] ?? '';

        if (!in_array($f['civilite'], ['M', 'Mme'], true)) $erreurs[] = 'Civilité invalide.';
        if ($f['nom'] === '' || $f['prenom'] === '') $erreurs[] = 'Nom et prénom obligatoires.';
        if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $erreurs[] = 'E-mail invalide.';
        if (!preg_match('/^[a-z0-9._-]{3,50}$/', $f['login'])) $erreurs[] = 'Identifiant : 3 à 50 caractères (lettres, chiffres, . _ -).';
        // Politique de mot de passe : 8 caractères minimum, au moins une lettre et un chiffre
        if (strlen($mdp) < 8 || !preg_match('/[A-Za-z]/', $mdp) || !preg_match('/\d/', $mdp)) $erreurs[] = 'Mot de passe : 8 caractères minimum, avec au moins une lettre et un chiffre.';
        if ($f['date_fin_carte'] < date('Y-m-d')) $erreurs[] = 'La date de fin de carte doit être dans le futur.';

        $st = db()->prepare('SELECT COUNT(*) FROM utilisateur WHERE login = ?');
        $st->execute([$f['login']]);
        if ($st->fetchColumn() > 0) $erreurs[] = 'Cet identifiant est déjà utilisé.';

        if (!$erreurs) {
            // Numéro de carte : EU-<année>-<numéro sur 4 chiffres>
            $prochain = (int)db()->query('SELECT COALESCE(MAX(id_utilisateur), 0) + 1 FROM utilisateur')->fetchColumn();
            $carte = sprintf('EU-%s-%04d', date('Y'), $prochain);
            db()->prepare("INSERT INTO utilisateur (login, mot_de_passe, role, civilite, nom, prenom, email, numero_carte, date_fin_carte)
                           VALUES (?, ?, 'adherent', ?, ?, ?, ?, ?, ?)")
                ->execute([$f['login'], password_hash($mdp, PASSWORD_DEFAULT), $f['civilite'], $f['nom'], $f['prenom'], $f['email'], $carte, $f['date_fin_carte']]);
            flash("Compte créé pour {$f['prenom']} {$f['nom']} : carte d'abonnement $carte.", 'succes');
            rediriger('tableau.php');
        }
    }

    /* ---------- TS5 : suppression d'un compte adhérent ---------- */
    if (($_POST['action'] ?? '') === 'supprimer') {
        // On ne peut supprimer que des adhérents (jamais un administrateur)
        $st = db()->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ? AND role = 'adherent'");
        $st->execute([(int)($_POST['id'] ?? 0)]);
        flash($st->rowCount() ? 'Compte adhérent supprimé. Ses réservations passées sont conservées.' : 'Compte introuvable.', $st->rowCount() ? 'succes' : 'erreur');
        rediriger('tableau.php');
    }
}

$adherents = db()->query("SELECT * FROM utilisateur WHERE role = 'adherent' ORDER BY nom, prenom")->fetchAll();
$reservations = db()->query(
    'SELECT r.reference, r.date_reservation, r.montant_total, r.code_promo, c.prenom, c.nom,
            (SELECT COUNT(*) FROM billet b WHERE b.id_reservation = r.id_reservation) AS nb_billets
       FROM reservation r JOIN client c ON c.id_client = r.id_client
      ORDER BY r.date_reservation DESC LIMIT 10')->fetchAll();

$titre = 'Administration';
$racine = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="entete-admin">
    <h1 class="titre-page">Administration</h1>
    <p>Connecté : <b><?= h($_SESSION['admin']['nom']) ?></b> · <a class="lien" href="deconnexion.php">Se déconnecter</a></p>
</div>

<div class="deux-colonnes admin">
    <section class="carte">
        <h2>Comptes adhérents (<?= count($adherents) ?>)</h2>
        <table class="tableau">
            <thead><tr><th>Adhérent</th><th>Identifiant</th><th>Carte</th><th>Fin de validité</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($adherents as $a): ?>
                <tr>
                    <td><b><?= h($a['civilite'] . ' ' . $a['prenom'] . ' ' . $a['nom']) ?></b><br><small><?= h($a['email']) ?></small></td>
                    <td><?= h($a['login']) ?></td>
                    <td class="nowrap"><?= h($a['numero_carte']) ?></td>
                    <td class="<?= $a['date_fin_carte'] < date('Y-m-d') ? 'rouge' : '' ?>"><?= date('d/m/Y', strtotime($a['date_fin_carte'])) ?></td>
                    <td class="action">
                        <form method="post"><?= csrf_champ() ?>
                            <input type="hidden" name="id" value="<?= $a['id_utilisateur'] ?>">
                            <button class="bouton-texte rouge" name="action" value="supprimer"
                                data-confirmer="Supprimer le compte de <?= h($a['prenom'] . ' ' . $a['nom']) ?> ?">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$adherents): ?><tr><td colspan="5" class="vide">Aucun adhérent.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="carte">
        <h2>Créer un compte adhérent</h2>
        <?php foreach ($erreurs as $e): ?><div class="alerte alerte-erreur"><?= h($e) ?></div><?php endforeach; ?>
        <form method="post" class="formulaire">
            <?= csrf_champ() ?>
            <div class="radios">
                <label><input type="radio" name="civilite" value="M" <?= $f['civilite'] === 'M' ? 'checked' : '' ?>> M.</label>
                <label><input type="radio" name="civilite" value="Mme" <?= $f['civilite'] === 'Mme' ? 'checked' : '' ?>> Mme</label>
            </div>
            <div class="ligne">
                <div class="champ"><label for="nom">Nom</label><input id="nom" name="nom" value="<?= h($f['nom']) ?>" required></div>
                <div class="champ"><label for="prenom">Prénom</label><input id="prenom" name="prenom" value="<?= h($f['prenom']) ?>" required></div>
            </div>
            <div class="champ"><label for="email">E-mail</label><input id="email" name="email" type="email" value="<?= h($f['email']) ?>" required></div>
            <div class="ligne">
                <div class="champ"><label for="login">Identifiant</label><input id="login" name="login" value="<?= h($f['login']) ?>" required></div>
                <div class="champ"><label for="nouveau_mdp">Mot de passe</label>
                    <div class="mdp">
                        <input id="nouveau_mdp" name="mot_de_passe" type="password" autocomplete="new-password" required>
                        <button type="button" class="oeil" data-oeil="nouveau_mdp" aria-label="Afficher le mot de passe"><svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/></svg></button>
                    </div>
                </div>
            </div>
            <div class="champ"><label for="date_fin_carte">Fin de validité de la carte</label>
                <input id="date_fin_carte" name="date_fin_carte" type="date" value="<?= h($f['date_fin_carte']) ?>" required></div>
            <button class="bouton bouton-plein large" name="action" value="creer">Créer le compte</button>
        </form>
    </section>
</div>

<section class="carte">
    <h2>Dernières réservations</h2>
    <table class="tableau">
        <thead><tr><th>Référence</th><th>Date</th><th>Client</th><th>Billets</th><th>Code promo</th><th class="montant">Montant</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($reservations as $r): ?>
            <tr>
                <td><b><?= h($r['reference']) ?></b></td>
                <td><?= date('d/m/Y H:i', strtotime($r['date_reservation'])) ?></td>
                <td><?= h($r['prenom'] . ' ' . $r['nom']) ?></td>
                <td><?= $r['nb_billets'] ?></td>
                <td><?= h($r['code_promo'] ?? '—') ?></td>
                <td class="montant"><?= prix((float)$r['montant_total']) ?></td>
                <td class="action"><a class="lien" href="../billet.php?ref=<?= urlencode($r['reference']) ?>" target="_blank">PDF</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$reservations): ?><tr><td colspan="7" class="vide">Aucune réservation pour le moment.</td></tr><?php endif; ?>
        </tbody>
    </table>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

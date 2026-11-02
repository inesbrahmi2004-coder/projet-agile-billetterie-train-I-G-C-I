<?php
/**
 * voyageur.php - Informations du voyageur.
 * Epic 3 - US1 : M/Mme, Nom, Prénom, Tél mobile, E-mail, Confirmation d'e-mail.
 * Tous les contrôles sont refaits côté serveur (le navigateur peut être contourné).
 */
require_once __DIR__ . '/includes/fonctions.php';
exiger_panier();

$v = panier()['voyageur'] ?? ['civilite' => '', 'nom' => '', 'prenom' => '', 'telephone' => '', 'email' => ''];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();
    $v = [
        'civilite'  => $_POST['civilite'] ?? '',
        'nom'       => trim($_POST['nom'] ?? ''),
        'prenom'    => trim($_POST['prenom'] ?? ''),
        'telephone' => preg_replace('/[\s.-]/', '', $_POST['telephone'] ?? ''),   // "06 12-34.56 78" -> "0612345678"
        'email'     => trim(strtolower($_POST['email'] ?? '')),
    ];
    $confirmation = trim(strtolower($_POST['email_confirmation'] ?? ''));

    if (!in_array($v['civilite'], ['M', 'Mme'], true)) $erreurs['civilite'] = 'Choisissez M. ou Mme.';
    $nomValide = '/^[\p{L}][\p{L} \'-]{0,59}$/u';                    // lettres, espaces, apostrophes, tirets
    if (!preg_match($nomValide, $v['nom']))    $erreurs['nom'] = 'Nom invalide.';
    if (!preg_match($nomValide, $v['prenom'])) $erreurs['prenom'] = 'Prénom invalide.';
    if (!preg_match('/^(0[67]\d{8}|\+33[67]\d{8})$/', $v['telephone'])) $erreurs['telephone'] = 'Numéro de mobile invalide (ex : 06 12 34 56 78).';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $erreurs['email'] = 'Adresse e-mail invalide.';
    elseif ($confirmation !== $v['email'])               $erreurs['email_confirmation'] = 'Les deux adresses e-mail sont différentes.';

    if (!$erreurs) {
        $_SESSION['panier']['voyageur'] = $v;
        rediriger('paiement.php');
    }
}

/** Affiche le message d'erreur d'un champ s'il y en a un. */
function err(array $erreurs, string $champ): string
{
    return isset($erreurs[$champ]) ? '<span class="erreur-champ">' . h($erreurs[$champ]) . '</span>' : '';
}

$titre = 'Vos informations';
$etape = 4;
require __DIR__ . '/includes/header.php';
?>
<h1 class="titre-page">Vos informations</h1>
<p class="sous-titre">Elles figureront sur vos billets. Votre e-mail sert à recevoir la confirmation.</p>

<form method="post" class="carte formulaire" novalidate>
    <?= csrf_champ() ?>
    <fieldset class="champ">
        <legend>Civilité</legend>
        <div class="radios">
            <label><input type="radio" name="civilite" value="M" <?= $v['civilite'] === 'M' ? 'checked' : '' ?> required> M.</label>
            <label><input type="radio" name="civilite" value="Mme" <?= $v['civilite'] === 'Mme' ? 'checked' : '' ?>> Mme</label>
        </div>
        <?= err($erreurs, 'civilite') ?>
    </fieldset>

    <div class="ligne">
        <div class="champ"><label for="nom">Nom</label>
            <input id="nom" name="nom" value="<?= h($v['nom']) ?>" autocomplete="family-name" required><?= err($erreurs, 'nom') ?></div>
        <div class="champ"><label for="prenom">Prénom</label>
            <input id="prenom" name="prenom" value="<?= h($v['prenom']) ?>" autocomplete="given-name" required><?= err($erreurs, 'prenom') ?></div>
    </div>

    <div class="champ"><label for="telephone">Téléphone mobile</label>
        <input id="telephone" name="telephone" type="tel" value="<?= h($v['telephone']) ?>" placeholder="06 12 34 56 78" autocomplete="tel" required><?= err($erreurs, 'telephone') ?></div>

    <div class="ligne">
        <div class="champ"><label for="email">E-mail</label>
            <input id="email" name="email" type="email" value="<?= h($v['email']) ?>" autocomplete="email" required><?= err($erreurs, 'email') ?></div>
        <div class="champ"><label for="email_confirmation">Confirmation de l'e-mail</label>
            <input id="email_confirmation" name="email_confirmation" type="email" value="<?= h($_POST['email_confirmation'] ?? ($v['email'] ?? '')) ?>" autocomplete="off" onpaste="return false" required><?= err($erreurs, 'email_confirmation') ?></div>
    </div>

    <div class="actions">
        <a class="bouton" href="recapitulatif.php"> Récapitulatif</a>
        <button class="bouton bouton-plein" type="submit">Passer au paiement</button>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>

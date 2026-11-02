<?php
/**
 * paiement.php - Réduction adhérent et paiement par carte bancaire.
 * Epic 3 - US2 : saisir ses coordonnées bancaires.
 * Epic 4 - US1 : carte d'abonnement + code promo (-10 €).
 * Epic 4 - US2 : paiement par carte bancaire -> enregistrement de la réservation.
 *
 * Le paiement est SIMULÉ (aucune banque n'est contactée). On vérifie quand
 * même le format de la carte (algorithme de Luhn), la date d'expiration et
 * le cryptogramme. Seuls les 4 derniers chiffres sont enregistrés.
 */
require_once __DIR__ . '/includes/fonctions.php';
require_once __DIR__ . '/includes/recap.php';
exiger_panier(true);

/** Algorithme de Luhn : contrôle de validité d'un numéro de carte bancaire. */
function luhn_valide(string $numero): bool
{
    $somme = 0;
    $double = false;
    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $chiffre = (int)$numero[$i];
        if ($double) {
            $chiffre *= 2;
            if ($chiffre > 9) $chiffre -= 9;
        }
        $somme += $chiffre;
        $double = !$double;
    }
    return $somme % 10 === 0;
}

/** Génère un identifiant aléatoire lisible (sans 0/O ni 1/I pour éviter les confusions). */
function code_aleatoire(int $longueur): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $longueur; $i++) $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    return $code;
}

$erreurs = [];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();

    /* ---------- Epic 4 - US1 : réduction adhérent ---------- */
    if ($action === 'reduction') {
        $carte = strtoupper(trim($_POST['numero_carte'] ?? ''));
        $code  = strtoupper(trim($_POST['code_promo'] ?? ''));

        $st = db()->prepare("SELECT id_utilisateur, prenom FROM utilisateur
                              WHERE role = 'adherent' AND numero_carte = ? AND date_fin_carte >= CURDATE()");
        $st->execute([$carte]);
        $adherent = $st->fetch();

        $st = db()->prepare('SELECT * FROM code_promo WHERE code = ? AND actif = TRUE AND date_fin >= CURDATE()');
        $st->execute([$code]);
        $promo = $st->fetch();

        if (!$adherent) {
            flash('Carte d\'abonnement inconnue ou expirée.', 'erreur');
        } elseif (!$promo) {
            flash('Code promo invalide ou expiré.', 'erreur');
        } else {
            $_SESSION['panier']['reduction'] = [
                'id_utilisateur' => (int)$adherent['id_utilisateur'],
                'carte'          => $carte,
                'code'           => $promo['code'],
                'montant'        => (float)$promo['montant_reduction'],
            ];
            flash('Réduction de ' . prix((float)$promo['montant_reduction']) . ' appliquée. Bonjour ' . $adherent['prenom'] . ' !', 'succes');
        }
        rediriger('paiement.php');
    }

    if ($action === 'retirer_reduction') {
        unset($_SESSION['panier']['reduction']);
        rediriger('paiement.php');
    }

    /* ---------- Epic 4 - US2 : paiement par carte bancaire ---------- */
    if ($action === 'payer') {
        $titulaire = trim($_POST['titulaire'] ?? '');
        $numero    = preg_replace('/\D/', '', $_POST['numero_cb'] ?? '');      // on ne garde que les chiffres
        $expiration = trim($_POST['expiration'] ?? '');
        $cvv       = trim($_POST['cvv'] ?? '');

        if (mb_strlen($titulaire) < 3) $erreurs['titulaire'] = 'Indiquez le nom du titulaire.';
        if (!preg_match('/^\d{13,19}$/', $numero) || !luhn_valide($numero)) $erreurs['numero_cb'] = 'Numéro de carte invalide.';
        if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiration, $m)) {
            $erreurs['expiration'] = 'Format attendu : MM/AA.';
        } elseif (sprintf('20%s-%s', $m[2], $m[1]) < date('Y-m')) {
            $erreurs['expiration'] = 'Cette carte est expirée.';
        }
        if (!preg_match('/^\d{3}$/', $cvv)) $erreurs['cvv'] = 'Le cryptogramme comporte 3 chiffres.';

        if (!$erreurs) {
            $c = calculer_panier();
            $p = panier();
            $pdo = db();
            try {
                // Transaction : soit tout est enregistré, soit rien (pas de réservation à moitié créée)
                $pdo->beginTransaction();

                // Verrouille les voyages et vérifie qu'il reste des places
                foreach ($c['billets'] as $b) {
                    $st = $pdo->prepare('SELECT places_dispo FROM voyage WHERE id_voyage = ? FOR UPDATE');
                    $st->execute([$b['id_voyage']]);
                    if ((int)$st->fetchColumn() < 1) throw new RuntimeException('Plus de place disponible sur le TGV ' . $b['numero_train'] . '.');
                }

                $v = $p['voyageur'];
                $pdo->prepare('INSERT INTO client (civilite, nom, prenom, telephone, email) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$v['civilite'], $v['nom'], $v['prenom'], $v['telephone'], $v['email']]);
                $idClient = (int)$pdo->lastInsertId();

                $reference = 'ET' . code_aleatoire(6);
                $pdo->prepare('INSERT INTO reservation (reference, id_client, id_utilisateur, code_promo, montant_brut, remise, montant_total, cb_4_derniers)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$reference, $idClient, $p['reduction']['id_utilisateur'] ?? null, $p['reduction']['code'] ?? null,
                               $c['brut'], $c['remise'], $c['total'], substr($numero, -4)]);
                $idResa = (int)$pdo->lastInsertId();

                foreach ($c['billets'] as $b) {
                    $pdo->prepare('INSERT INTO billet (numero_billet, id_reservation, id_voyage, sens, prix, voiture, place) VALUES (?, ?, ?, ?, ?, ?, ?)')
                        ->execute([$reference . '-' . substr($b['sens'], 0, 1) . code_aleatoire(3), $idResa, $b['id_voyage'], $b['sens'],
                                   $b['prix'], random_int(1, 16), random_int(1, 96)]);
                    $pdo->prepare('UPDATE voyage SET places_dispo = places_dispo - 1 WHERE id_voyage = ?')->execute([$b['id_voyage']]);
                }

                foreach ($c['options'] as $o) {
                    $pdo->prepare('INSERT INTO reservation_option (id_reservation, id_option, prix_applique) VALUES (?, ?, ?)')
                        ->execute([$idResa, $o['id_option'], $o['prix']]);
                }

                $pdo->commit();

                // La réservation payée est consultable par cette session uniquement
                $_SESSION['mes_reservations'][] = $reference;
                unset($_SESSION['panier']);
                rediriger('confirmation.php?ref=' . urlencode($reference));
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash($e instanceof RuntimeException ? $e->getMessage() : 'Le paiement n\'a pas pu être enregistré. Réessayez.', 'erreur');
                rediriger('paiement.php');
            }
        }
    }
}

$c = calculer_panier();
$reduc = panier()['reduction'] ?? null;

function err(array $erreurs, string $champ): string
{
    return isset($erreurs[$champ]) ? '<span class="erreur-champ">' . h($erreurs[$champ]) . '</span>' : '';
}

$titre = 'Paiement';
$etape = 5;
require __DIR__ . '/includes/header.php';
?>
<h1 class="titre-page">Paiement</h1>

<div class="deux-colonnes">
    <div>
        <!-- Epic 4 - US1 : carte d'abonnement + code promo -->
        <section class="carte">
            <h2>Vous êtes adhérent ?</h2>
            <?php if ($reduc): ?>
                <p class="alerte alerte-succes">Carte <b><?= h($reduc['carte']) ?></b> · code <b><?= h($reduc['code']) ?></b> : − <?= prix($reduc['montant']) ?></p>
                <form method="post"><?= csrf_champ() ?><button class="bouton-texte rouge" name="action" value="retirer_reduction">Retirer la réduction</button></form>
            <?php else: ?>
                <p class="sous-titre">Saisissez votre numéro de carte d'abonnement puis votre code de réduction.</p>
                <form method="post" class="ligne">
                    <?= csrf_champ() ?>
                    <div class="champ"><label for="numero_carte">N° de carte d'abonnement</label>
                        <input id="numero_carte" name="numero_carte" placeholder="EU-2026-0001" required></div>
                    <div class="champ"><label for="code_promo">Code promo</label>
                        <input id="code_promo" name="code_promo" placeholder="PROMO10" required></div>
                    <div class="champ bas"><button class="bouton" name="action" value="reduction">Appliquer</button></div>
                </form>
            <?php endif; ?>
        </section>

        <!-- Epic 3 - US2 + Epic 4 - US2 : coordonnées bancaires -->
        <form method="post" class="carte formulaire" novalidate>
            <?= csrf_champ() ?>
            <h2>Carte bancaire</h2>
            <p class="info-securite"><svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="2"/></svg><span>Paiement simulé. Le numéro complet et le cryptogramme ne sont jamais enregistrés.</span></p>
            <div class="champ"><label for="titulaire">Titulaire de la carte</label>
                <input id="titulaire" name="titulaire" value="<?= h($_POST['titulaire'] ?? ((panier()['voyageur']['prenom'] ?? '') . ' ' . (panier()['voyageur']['nom'] ?? ''))) ?>" autocomplete="cc-name" required><?= err($erreurs, 'titulaire') ?></div>
            <div class="champ"><label for="numero_cb">Numéro de carte</label>
                <input id="numero_cb" name="numero_cb" inputmode="numeric" placeholder="4970 1012 3456 7890" maxlength="23" autocomplete="cc-number" required><?= err($erreurs, 'numero_cb') ?></div>
            <div class="ligne">
                <div class="champ"><label for="expiration">Expiration</label>
                    <input id="expiration" name="expiration" placeholder="MM/AA" maxlength="5" value="<?= h($_POST['expiration'] ?? '') ?>" autocomplete="cc-exp" required><?= err($erreurs, 'expiration') ?></div>
                <div class="champ"><label for="cvv">Cryptogramme</label>
                    <input id="cvv" name="cvv" inputmode="numeric" placeholder="123" maxlength="3" autocomplete="cc-csc" required><?= err($erreurs, 'cvv') ?></div>
            </div>
            <div class="actions">
                <a class="bouton" href="voyageur.php"> Mes informations</a>
                <button class="bouton bouton-plein" name="action" value="payer">Payer <?= prix($c['total']) ?></button>
            </div>
        </form>
    </div>

    <aside class="carte resume">
        <h2>Récapitulatif</h2>
        <?php afficher_recap($c); ?>
        <?php $v = panier()['voyageur']; ?>
        <p class="voyageur"><b><?= h($v['civilite'] . ' ' . $v['prenom'] . ' ' . $v['nom']) ?></b><br><?= h($v['email']) ?> · <?= h($v['telephone']) ?></p>
    </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

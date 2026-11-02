<?php
/**
 * panier.php - Panier d'achat.
 * Epic 2 - US1 : visualiser le panier, retirer ou ajouter une ou plusieurs options.
 */
require_once __DIR__ . '/includes/fonctions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();
    $id = (int)($_POST['id_option'] ?? 0);
    $opts = $_SESSION['panier']['options'] ?? [];

    switch ($_POST['action'] ?? '') {
        case 'retirer':                         // retire une option
            $opts = array_values(array_diff($opts, [$id]));
            flash('Option retirée du panier.', 'succes');
            break;
        case 'ajouter':                         // ajoute une option existante
            $existe = in_array($id, array_map('intval', array_column(liste_options(), 'id_option')), true);
            if ($existe && !in_array($id, $opts, true)) $opts[] = $id;
            flash('Option ajoutée au panier.', 'succes');
            break;
        case 'vider':                           // vide tout le panier
            unset($_SESSION['panier']);
            flash('Votre panier a été vidé.', 'info');
            rediriger('index.php');
    }
    sort($opts);
    $_SESSION['panier']['options'] = $opts;
    rediriger('panier.php');
}

$vide = empty(panier()['aller']);
$c = $vide ? null : calculer_panier();
$idsChoisis = $vide ? [] : array_map('intval', array_column($c['options'], 'id_option'));
$disponibles = array_filter(liste_options(), fn($o) => !in_array((int)$o['id_option'], $idsChoisis, true));

$titre = 'Mon panier';
$etape = $vide ? 0 : 3;
require __DIR__ . '/includes/header.php';
?>
<h1 class="titre-page">Mon panier</h1>

<?php if ($vide): ?>
    <div class="carte centre">
        <p>Votre panier est vide.</p>
        <a class="bouton bouton-plein" href="index.php">Rechercher un train</a>
    </div>
<?php else: ?>
<div class="deux-colonnes">
    <div>
        <section class="carte">
            <h2>Billets</h2>
            <table class="tableau">
                <?php foreach ($c['billets'] as $b): ?>
                <tr>
                    <td><span class="badge"><?= $b['sens'] ?></span></td>
                    <td><b><?= h($b['gare_depart']) ?> → <?= h($b['gare_arrivee']) ?></b><br>
                        <small><?= date_fr($b['date_depart']) ?>, <?= heure($b['heure_depart']) ?> → <?= heure($b['heure_arrivee']) ?>, TGV <?= h($b['numero_train']) ?></small></td>
                    <td class="montant"><?= prix((float)$b['prix']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <a class="lien" href="recherche.php?<?= http_build_query(['dep' => $_SESSION['recherche']['dep'], 'arr' => $_SESSION['recherche']['arr'], 'type' => $_SESSION['recherche']['retour'] ? 'ar' : 'as', 'aller' => $_SESSION['recherche']['aller'], 'retour' => $_SESSION['recherche']['retour']]) ?>">Changer de train</a>
        </section>

        <section class="carte">
            <h2>Options choisies</h2>
            <?php if (!$c['options']): ?><p class="vide">Aucune option.</p><?php endif; ?>
            <table class="tableau">
                <?php foreach ($c['options'] as $o): ?>
                <tr>
                    <td><?= h($o['libelle']) ?></td>
                    <td class="montant"><?= prix((float)$o['prix']) ?></td>
                    <td class="action">
                        <form method="post"><?= csrf_champ() ?>
                            <input type="hidden" name="id_option" value="<?= $o['id_option'] ?>">
                            <button class="bouton-texte rouge" name="action" value="retirer">Retirer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <?php if ($disponibles): ?>
            <h3>Ajouter une option</h3>
            <table class="tableau">
                <?php foreach ($disponibles as $o): ?>
                <tr>
                    <td><?= h($o['libelle']) ?></td>
                    <td class="montant">+ <?= prix((float)$o['prix']) ?></td>
                    <td class="action">
                        <form method="post"><?= csrf_champ() ?>
                            <input type="hidden" name="id_option" value="<?= $o['id_option'] ?>">
                            <button class="bouton-texte" name="action" value="ajouter">Ajouter</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </section>
    </div>

    <aside class="carte resume">
        <h2>Total</h2>
        <p class="ligne-total"><span>Billets</span><span><?= prix(array_sum(array_column($c['billets'], 'prix'))) ?></span></p>
        <p class="ligne-total"><span>Options</span><span><?= prix(array_sum(array_column($c['options'], 'prix'))) ?></span></p>
        <p class="total">Total <b><?= prix($c['brut']) ?></b></p>
        <a class="bouton bouton-plein large" href="recapitulatif.php">Valider mon panier</a>
        <form method="post"><?= csrf_champ() ?>
            <button class="bouton-texte rouge" name="action" value="vider" data-confirmer="Vider tout le panier ?">Vider le panier</button>
        </form>
    </aside>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

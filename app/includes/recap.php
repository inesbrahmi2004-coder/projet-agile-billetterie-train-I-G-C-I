<?php
/**
 * recap.php - Bloc « récapitulatif de la réservation », réutilisé sur
 * les pages récapitulatif, paiement et confirmation.
 *
 * @param array $c résultat de calculer_panier() (ou d'une réservation payée)
 */
function afficher_recap(array $c): void
{
    ?>
    <table class="tableau recap">
        <thead><tr><th>Détail</th><th class="montant">Prix</th></tr></thead>
        <tbody>
        <?php foreach ($c['billets'] as $b): ?>
            <tr>
                <td><span class="badge"><?= $b['sens'] ?></span>
                    <b><?= h($b['gare_depart']) ?> → <?= h($b['gare_arrivee']) ?></b><br>
                    <small><?= date_fr($b['date_depart']) ?>, départ <?= heure($b['heure_depart']) ?>, arrivée <?= heure($b['heure_arrivee']) ?>, TGV <?= h($b['numero_train']) ?></small></td>
                <td class="montant"><?= prix((float)$b['prix']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php foreach ($c['options'] as $o): ?>
            <tr><td>Option : <?= h($o['libelle']) ?></td><td class="montant"><?= prix((float)$o['prix']) ?></td></tr>
        <?php endforeach; ?>
        <?php if ($c['remise'] > 0): ?>
            <tr class="remise"><td>Réduction adhérent</td><td class="montant">− <?= prix($c['remise']) ?></td></tr>
        <?php endif; ?>
        </tbody>
        <tfoot><tr><th>Total à payer</th><th class="montant"><?= prix($c['total']) ?></th></tr></tfoot>
    </table>
    <?php
}

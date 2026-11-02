<?php
/**
 * billet.php - Génère les billets aller/retour au format PDF (une page par billet).
 * Epic 4 - US2 : « J'imprime mes billets aller/retour avec les numéros de réservation au format PDF. »
 */
require_once __DIR__ . '/includes/fonctions.php';
require_once __DIR__ . '/includes/pdf.php';

$r = charger_reservation($_GET['ref'] ?? '');
if (!$r) {
    http_response_code(404);
    exit('Billet introuvable.');
}

$bleu = '#0b3d91';
$vert = '#16a34a';
$pdf = new MiniPdf();

foreach ($r['billets'] as $b) {
    $pdf->ajouterPage();

    // Bandeau
    $pdf->rectangle(0, 0, 595, 90, $bleu);
    $pdf->texte(40, 50, 'europe.transports', 24, true, '#ffffff');
    $pdf->texte(40, 72, 'E-billet TGV - à présenter à bord', 11, false, '#dbeafe');
    $pdf->texte(430, 50, $b['sens'], 22, true, '#ffffff');

    // Trajet
    $pdf->texte(40, 140, date_fr($b['date_depart']), 13, true, $bleu);
    $pdf->texte(40, 185, heure($b['heure_depart']), 26, true);
    $pdf->texte(140, 185, $b['gare_depart'], 16, true);
    $pdf->ligne(70, 198, 70, 222, '#9ca3af');
    $pdf->texte(40, 250, heure($b['heure_arrivee']), 26, true);
    $pdf->texte(140, 250, $b['gare_arrivee'], 16, true);

    // Cadre d'informations
    $pdf->rectangle(40, 285, 515, 120, '#f3f4f6', '#e5e7eb');
    $infos = [
        ['Train', 'TGV ' . $b['numero_train']],
        ['Voiture', (string)$b['voiture']],
        ['Place', (string)$b['place']],
        ['Classe', '2de'],
    ];
    foreach ($infos as $i => [$libelle, $valeur]) {
        $x = 60 + $i * 125;
        $pdf->texte($x, 315, $libelle, 9, false, '#6b7280');
        $pdf->texte($x, 340, $valeur, 16, true);
    }
    $pdf->texte(60, 380, 'Voyageur : ' . $r['civilite'] . ' ' . $r['prenom'] . ' ' . mb_strtoupper($r['nom']), 11, true);

    // Numéros
    $pdf->texte(40, 445, 'N° de réservation', 9, false, '#6b7280');
    $pdf->texte(40, 465, $r['reference'], 18, true, $bleu);
    $pdf->texte(250, 445, 'N° de billet', 9, false, '#6b7280');
    $pdf->texte(250, 465, $b['numero_billet'], 18, true, $bleu);

    // Code de contrôle (simulé) : motif 21x21 calculé à partir du n° de billet
    $bits = '';
    foreach (str_split(hash('sha256', $b['numero_billet'] . $r['reference']), 1) as $hex) {
        $bits .= str_pad(base_convert($hex, 16, 2), 4, '0', STR_PAD_LEFT);
    }
    $bits = str_repeat($bits, 2);
    $cote = 5;
    for ($l = 0; $l < 21; $l++) {
        for ($c = 0; $c < 21; $c++) {
            $repere = ($l < 7 && $c < 7) || ($l < 7 && $c > 13) || ($l > 13 && $c < 7);   // 3 coins de repérage
            $plein = $repere
                ? ($l % 6 === 0 || $c % 6 === 0 || $l === 20 || $c === 20 || $c === 14 || $l === 14 || (($l % 7) >= 2 && ($l % 7) <= 4 && ($c % 7) >= 2 && ($c % 7) <= 4))
                : $bits[$l * 21 + $c] === '1';
            if ($plein) $pdf->rectangle(450 + $c * $cote, 430 + $l * $cote, $cote, $cote, '#111827');
        }
    }

    // Prix et options
    $pdf->ligne(40, 560, 555, 560, '#d1d5db', true);
    $pdf->texte(40, 590, 'Prix du billet', 11);
    $pdf->texte(470, 590, prix((float)$b['prix']), 11, true);
    $y = 612;
    if ($b['sens'] === 'ALLER') {
        foreach ($r['options'] as $o) {
            $pdf->texte(40, $y, 'Option : ' . $o['libelle'], 10, false, '#374151');
            $pdf->texte(470, $y, prix((float)$o['prix']), 10);
            $y += 20;
        }
        if ($r['remise'] > 0) {
            $pdf->texte(40, $y, 'Réduction adhérent (carte ' . $r['numero_carte'] . ')', 10, false, $vert);
            $pdf->texte(470, $y, '- ' . prix($r['remise']), 10, true, $vert);
            $y += 20;
        }
        $pdf->texte(40, $y + 10, 'Total payé pour la réservation', 12, true);
        $pdf->texte(470, $y + 10, prix($r['total']), 12, true, $bleu);
    } else {
        $pdf->texte(40, $y, 'Options et total : voir le billet ALLER de la réservation ' . $r['reference'] . '.', 10, false, '#6b7280');
    }

    // Pied de page
    $pdf->ligne(40, 780, 555, 780, '#e5e7eb');
    $pdf->texte(40, 800, 'Billet nominatif. Pièce d\'identité demandée à bord. Réservé le ' . date('d/m/Y à H:i', strtotime($r['date_reservation'])) . '.', 8, false, '#6b7280');
    $pdf->texte(40, 815, 'europe.transports est un opérateur fictif - Projet Agile 3AE ECAM-EPMI.', 8, false, '#6b7280');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="billets_' . $r['reference'] . '.pdf"');
echo $pdf->generer();

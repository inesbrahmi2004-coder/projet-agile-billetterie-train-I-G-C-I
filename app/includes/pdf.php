<?php
/**
 * pdf.php - Petit générateur de PDF écrit pour le projet (aucune librairie
 * externe à installer). Il sait dessiner du texte, des rectangles et des
 * lignes : c'est suffisant pour un billet de train.
 *
 * Repère : (0,0) en haut à gauche, unités en points (A4 = 595 x 842).
 */
class MiniPdf
{
    private array $pages = [];      // contenu (opérateurs PDF) de chaque page
    private int $courante = -1;
    private const HAUTEUR = 842;
    private const LARGEUR = 595;

    public function ajouterPage(): void
    {
        $this->pages[] = '';
        $this->courante++;
    }

    private function ecrire(string $op): void
    {
        $this->pages[$this->courante] .= $op . "\n";
    }

    /** Convertit l'UTF-8 en Windows-1252 (encodage des polices standard du PDF). */
    private function encoder(string $texte): string
    {
        $t = iconv('UTF-8', 'Windows-1252//TRANSLIT', $texte);
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $t === false ? '' : $t);
    }

    private static function couleur(string $hex): string
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');
        return sprintf('%.3f %.3f %.3f', $r / 255, $g / 255, $b / 255);
    }

    /** Écrit un texte. $gras : Helvetica-Bold ; $couleur : "#RRGGBB". */
    public function texte(float $x, float $y, string $texte, float $taille = 11, bool $gras = false, string $couleur = '#1f2937'): void
    {
        $police = $gras ? 'F2' : 'F1';
        $this->ecrire(sprintf('BT %s rg /%s %.1f Tf %.2f %.2f Td (%s) Tj ET',
            self::couleur($couleur), $police, $taille, $x, self::HAUTEUR - $y, $this->encoder($texte)));
    }

    /** Rectangle plein (couleur de fond) et/ou contour. */
    public function rectangle(float $x, float $y, float $l, float $h, ?string $fond = null, ?string $contour = null): void
    {
        $op = $fond && $contour ? 'B' : ($fond ? 'f' : 'S');
        $this->ecrire(sprintf('%s%s %.2f %.2f %.2f %.2f re %s',
            $fond ? self::couleur($fond) . ' rg ' : '',
            $contour ? self::couleur($contour) . ' RG 0.8 w ' : '',
            $x, self::HAUTEUR - $y - $h, $l, $h, $op));
    }

    /** Ligne (pointillée si $pointilles). */
    public function ligne(float $x1, float $y1, float $x2, float $y2, string $couleur = '#9ca3af', bool $pointilles = false): void
    {
        $this->ecrire(sprintf('%s RG 0.8 w %s %.2f %.2f m %.2f %.2f l S [] 0 d',
            self::couleur($couleur), $pointilles ? '[3 3] 0 d' : '',
            $x1, self::HAUTEUR - $y1, $x2, self::HAUTEUR - $y2));
    }

    /** Assemble le fichier PDF (objets, table xref, trailer). */
    public function generer(): string
    {
        $objets = [];
        $objets[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objets[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objets[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $kids = [];
        $n = 5;
        foreach ($this->pages as $contenu) {
            $objets[$n] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                self::LARGEUR, self::HAUTEUR, $n + 1);
            $objets[$n + 1] = "<< /Length " . strlen($contenu) . " >>\nstream\n" . $contenu . "endstream";
            $kids[] = "$n 0 R";
            $n += 2;
        }
        $objets[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objets);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $positions = [];
        foreach ($objets as $num => $obj) {
            $positions[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n$obj\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objets) + 1) . "\n0000000000 65535 f \n";
        foreach ($positions as $pos) $pdf .= sprintf("%010d 00000 n \n", $pos);
        $pdf .= "trailer\n<< /Size " . (count($objets) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        return $pdf;
    }
}

<?php
/**
 * illustrations.php - Illustrations des villes pour les cartes « Où partir en TGV ? ».
 * Même palette que la scène de l'accueil : ciel au coucher du soleil (bleu nuit,
 * violet, rose, orangé) et monuments en silhouette bleu nuit.
 *
 * illustration_ville('lyon') renvoie le code SVG de la carte de Lyon.
 */
function illustration_ville(string $ville): string
{
    $id = 'ciel-' . $ville;
    // Ciel identique à la scène de l'accueil
    $ciel = <<<SVG
<defs>
  <linearGradient id="$id" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#0E1B3D"/><stop offset=".38" stop-color="#2E2C6A"/>
    <stop offset=".62" stop-color="#9C4C7E"/><stop offset=".8" stop-color="#EE8650"/>
    <stop offset="1" stop-color="#FFC27A"/>
  </linearGradient>
  <radialGradient id="halo-$ville"><stop offset="0" stop-color="#FFE9B0" stop-opacity=".85"/><stop offset="1" stop-color="#FFB45E" stop-opacity="0"/></radialGradient>
</defs>
<rect width="300" height="400" fill="url(#$id)"/>
<g fill="#fff" opacity=".7"><circle cx="40" cy="40" r="1.3"/><circle cx="120" cy="22" r="1"/><circle cx="210" cy="55" r="1.4"/><circle cx="270" cy="30" r="1"/><circle cx="80" cy="95" r=".9"/></g>
SVG;

    // Monuments en silhouette (bleu nuit), dessinés à la main en formes simples
    $monuments = [
        // Lyon : colline de Fourvière, basilique, tour métallique, la Saône
        'lyon' => <<<SVG
<circle cx="90" cy="250" r="110" fill="url(#halo-lyon)"/><circle cx="90" cy="250" r="34" fill="#FFE6A6"/>
<path d="M0 205 70 170 130 196 210 150 300 190V400H0z" fill="#6E4A88" opacity=".55"/>
<path d="M0 300c50-60 120-92 190-84 50 6 85 34 110 58V400H0z" fill="#3A2B62"/>
<g fill="#1A1F48">
  <rect x="150" y="190" width="56" height="34"/><rect x="144" y="176" width="11" height="48"/><rect x="201" y="176" width="11" height="48"/>
  <path d="M144 176h11l-5.5-8zM201 176h11l-5.5-8zM166 190v-10h24v10z"/><rect x="175" y="166" width="6" height="14"/>
  <path d="M244 230l8-92 8 92z"/><rect x="249" y="128" width="6" height="12"/>
</g>
<rect y="318" width="300" height="16" fill="#9C4C7E" opacity=".45"/>
<path d="M0 334h300V400H0z" fill="#0E1B3D"/>
<path d="M0 334V306h22v-12h20v12h16v-20h24v20h18v-14h22v14h20v-24h26v24h18v-10h24v10h20v-18h22v18h24v-12h24v40z" fill="#0E1B3D"/>
SVG,
        // Marseille : Notre-Dame de la Garde sur sa colline, la mer, des voiliers
        'marseille' => <<<SVG
<circle cx="220" cy="270" r="120" fill="url(#halo-marseille)"/><circle cx="220" cy="272" r="38" fill="#FFE6A6"/>
<rect y="292" width="300" height="108" fill="#2E2C6A"/>
<g stroke="#FFE6A6" stroke-width="2" opacity=".55"><path d="M196 304h48M204 316h32M210 328h20"/></g>
<path d="M0 292V230c40-50 90-62 140-34 30 18 50 52 66 96z" fill="#3A2B62"/>
<g fill="#1A1F48">
  <rect x="58" y="176" width="58" height="34"/><rect x="78" y="118" width="18" height="60"/>
  <path d="M78 118l9-20 9 20zM84 98h6v-12h-6z"/><circle cx="87" cy="83" r="4"/>
  <path d="M58 176l29-14 29 14z"/>
</g>
<g fill="#0E1B3D">
  <path d="M40 360l22-40v40zM66 360l14-26v26z"/><path d="M30 360h56l-8 10H38z"/>
  <path d="M150 352l16-30v30zM170 352l10-20v20z"/><path d="M142 352h44l-6 8h-32z"/>
</g>
<path d="M0 372h300V400H0z" fill="#0E1B3D"/>
SVG,
        // Lille : le beffroi et les maisons flamandes à pignons
        'lille' => <<<SVG
<circle cx="230" cy="250" r="110" fill="url(#halo-lille)"/><circle cx="230" cy="252" r="32" fill="#FFE6A6"/>
<path d="M0 240 80 214 150 236 230 206 300 228V400H0z" fill="#6E4A88" opacity=".5"/>
<g fill="#1A1F48">
  <rect x="126" y="130" width="34" height="210"/><rect x="120" y="190" width="46" height="10"/>
  <path d="M126 130c0-24 34-24 34 0zM138 108h10v-10h-10zM141 98l2-26 2 26z"/>
  <path d="M120 130h8l-4-14zM158 130h8l-4-14z"/>
</g>
<g fill="#0E1B3D">
  <path d="M0 340V266h8v-10h8v-10h8v10h8v10h8v74z"/><path d="M44 340V280h10v-12h10v-12h10v12h10v12h10v60z"/>
  <path d="M170 340V274h10v-12h10v-12h10v12h10v12h10v66z"/><path d="M226 340V262h8v-10h8v-10h8v10h8v10h8v78z"/>
  <path d="M270 340V284h10v-12h10v12h10v56z"/>
</g>
<g fill="#FFC83D" opacity=".75"><rect x="12" y="290" width="6" height="10"/><rect x="58" y="300" width="6" height="10"/><rect x="186" y="292" width="6" height="10"/><rect x="240" y="282" width="6" height="10"/></g>
<path d="M0 340h300V400H0z" fill="#0E1B3D"/>
SVG,
        // Bordeaux : la place de la Bourse et son reflet dans le miroir d'eau
        'bordeaux' => <<<SVG
<circle cx="150" cy="200" r="120" fill="url(#halo-bordeaux)"/><circle cx="150" cy="204" r="34" fill="#FFE6A6"/>
<g id="bourse" fill="#1A1F48">
  <rect x="10" y="228" width="280" height="54"/><path d="M10 228l12-14h256l12 14z"/>
  <rect x="112" y="210" width="76" height="72"/><path d="M112 210l38-20 38 20z"/>
</g>
<g fill="#FFC83D" opacity=".7">
  <rect x="24" y="246" width="8" height="14"/><rect x="44" y="246" width="8" height="14"/><rect x="64" y="246" width="8" height="14"/><rect x="84" y="246" width="8" height="14"/>
  <rect x="124" y="230" width="8" height="14"/><rect x="146" y="230" width="8" height="14"/><rect x="168" y="230" width="8" height="14"/>
  <rect x="208" y="246" width="8" height="14"/><rect x="228" y="246" width="8" height="14"/><rect x="248" y="246" width="8" height="14"/><rect x="268" y="246" width="8" height="14"/>
</g>
<rect y="282" width="300" height="118" fill="#2E2C6A"/>
<use href="#bourse" transform="matrix(1 0 0 -1 0 564)" opacity=".35"/>
<g stroke="#FFE6A6" stroke-width="1.5" opacity=".35"><path d="M30 300h60M160 312h90M60 330h70"/></g>
<path d="M0 352h300V400H0z" fill="#0E1B3D"/>
SVG,
    ];

    $dessin = $monuments[$ville] ?? '';
    return '<svg class="destination-scene" viewBox="0 0 300 400" preserveAspectRatio="xMidYMax slice" aria-hidden="true">' . $ciel . $dessin . '</svg>';
}

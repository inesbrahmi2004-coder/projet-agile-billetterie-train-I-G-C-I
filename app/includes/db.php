<?php
/**
 * db.php - Connexion unique à MySQL avec PDO.
 *
 * Toutes les requêtes du site passent par des requêtes préparées
 * (prepare + execute) : les valeurs saisies par l'utilisateur ne sont
 * jamais concaténées dans le SQL, ce qui empêche les injections SQL.
 *
 * Installation automatique : si la base europe_transports n'existe pas
 * encore, elle est créée au premier affichage du site à partir de
 * database/schema.sql puis database/seed.sql.
 */
require_once __DIR__ . '/config.php';

/** Ouvre une connexion PDO (avec ou sans base sélectionnée). */
function connexion_pdo(string $port, bool $avecBase): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . $port . ($avecBase ? ';dbname=' . DB_NAME : '') . ';charset=utf8mb4';
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // erreurs SQL -> exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // résultats en tableaux associatifs
        PDO::ATTR_EMULATE_PREPARES   => false,                  // vraies requêtes préparées
    ]);
}

/** Crée la base et la remplit avec les fichiers SQL du dossier database/. */
function installer_base(string $port): void
{
    $pdo = connexion_pdo($port, false);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);      // permet plusieurs requêtes d'un coup
    foreach (['schema.sql', 'seed.sql'] as $fichier) {
        $pdo->exec(file_get_contents(__DIR__ . '/../../database/' . $fichier));
    }
}

function db(): PDO
{
    static $pdo = null;              // une seule connexion par page
    if ($pdo !== null) return $pdo;

    // Port configuré, sinon on essaie celui de MAMP (8889) puis le port MySQL standard (3306)
    $ports = DB_PORT !== '' ? [DB_PORT] : ['8889', '3306'];
    foreach ($ports as $port) {
        try {
            try {
                $pdo = connexion_pdo($port, true);
            } catch (PDOException $e) {
                if ((int)($e->errorInfo[1] ?? 0) !== 1049) throw $e;   // 1049 = base inconnue
                installer_base($port);                                 // première utilisation
                $pdo = connexion_pdo($port, true);
            }
            return $pdo;
        } catch (PDOException $e) {
            $derniereErreur = $e->getMessage();
        }
    }

    http_response_code(500);
    exit('<p style="font-family:sans-serif;padding:2rem">Connexion à la base impossible. '
       . 'Vérifiez que MySQL est démarré (MAMP : bouton Start) et les paramètres de <code>app/includes/config.php</code>.</p>');
}

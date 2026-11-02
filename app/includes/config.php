<?php
/**
 * config.php - Paramètres de connexion à la base MySQL.
 *
 * Valeurs par défaut prévues pour MAMP sur Mac (root/root). Le port est détecté
 * automatiquement : 8889 (MAMP), sinon 3306.
 *  - XAMPP / WAMP : port 3306, utilisateur root, mot de passe vide ''.
 * On peut aussi les surcharger par variables d'environnement (DB_HOST...).
 */
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '');           // vide = essaie 8889 (MAMP) puis 3306
define('DB_NAME', getenv('DB_NAME') ?: 'europe_transports');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root');

define('NOM_SITE', 'europe.transports');

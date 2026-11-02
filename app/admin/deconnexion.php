<?php
/** admin/deconnexion.php - Fermeture de la session administrateur. */
require_once __DIR__ . '/../includes/fonctions.php';

unset($_SESSION['admin']);
session_regenerate_id(true);
flash('Vous êtes déconnecté.', 'info');
rediriger('index.php');

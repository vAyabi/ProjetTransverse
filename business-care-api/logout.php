<?php
session_start();

// détruire toutes les variables de session
$_SESSION = array();

// détruire la session
session_destroy();

// rediriger vers la page de connexion
header('Location: /business-care-api/login.php');
exit();
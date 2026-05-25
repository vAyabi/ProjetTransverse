<?php

session_start();

if(isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en', 'es', 'de'])) {
    $_SESSION['lang'] = $_GET['lang'];
    
    // Stocker dans un cookie pour la persistance
    setcookie('user_lang', $_GET['lang'], time() + (365 * 24 * 60 * 60), '/');
    
    echo json_encode(['success' => true, 'lang' => $_GET['lang']]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid language']);
}
?>
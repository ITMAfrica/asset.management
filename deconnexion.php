<?php
// Démarrer la session
session_start();

// Détruire toutes les variables de session
$_SESSION = array();

// Si vous utilisez aussi des cookies de session, détruisez-les.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire la session
session_destroy();

// --- CORRECTION AJOUTÉE ---
// Supprimer le cookie "Se souvenir de moi" en lui donnant une date d'expiration passée.
if (isset($_COOKIE['remember_me'])) {
    unset($_COOKIE['remember_me']);
    setcookie('remember_me', '', time() - 3600, '/'); // Le '/' est important pour le chemin
}

// Rediriger l'utilisateur vers la page de connexion
header("Location: index");
exit;
?>
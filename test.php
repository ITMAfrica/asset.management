<?php
session_start();
// Assurez-vous que 'db_connexion.php' utilise bien la connexion PDO à PostgreSQL
require_once 'db_connexion.php'; 

// Redirection si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: menu.php'); 
    exit();
}

$message = '';

// --- 1. Traitement du formulaire (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $identifiant = trim($_POST['identifiant'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($identifiant) || empty($password)) {
        $message = "<div class='alert alert-warning'>Veuillez saisir votre identifiant et votre mot de passe.</div>";
    } else {
        try {
            // Requête PostgreSQL pour trouver l'utilisateur par email OU telephone
            $sql = "SELECT id, nom_complet, email, password, profil FROM t_users WHERE email = ? OR telephone = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$identifiant, $identifiant]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            // Vérification du mot de passe haché
            if ($utilisateur && password_verify($password, $utilisateur['password'])) {
                
                // Connexion Réussie
                $_SESSION['user_id'] = $utilisateur['id'];
                $_SESSION['user_nom'] = $utilisateur['nom_complet'];
                $_SESSION['user_profil'] = $utilisateur['profil'];
                
                // Redirection vers menu.php
                header('Location: menu.php'); 
                exit();
                
            } else {
                $message = "<div class='alert alert-danger'>Identifiant ou mot de passe incorrect.</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Erreur de base de données : Connexion impossible.</div>";
        }
    }
}

$current_theme = $_COOKIE['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="<?php echo htmlspecialchars($current_theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion au Projet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* --- Styles de Base (Centrage et Responsive) --- */
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
            transition: background-color 0.5s, color 0.5s;
            /* Flexbox pour centrer le contenu */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Hauteur minimale de la fenêtre */
            margin: 0;
            padding: 20px; /* Ajoute un padding pour les petits écrans */
        }
        .login-container {
            max-width: 400px;
            width: 100%; /* S'assure qu'il est responsive */
            padding: 30px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: background-color 0.5s, box-shadow 0.5s;
            position: relative; 
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
            color: #007bff;
        }
        .theme-toggle-icon {
            position: absolute;
            top: 15px;
            right: 15px;
            cursor: pointer;
            font-size: 1.5rem;
            color: #6c757d;
            transition: color 0.3s, transform 0.3s;
        }
        .password-toggle-group {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 60%; 
            transform: translateY(-50%);
            cursor: pointer;
            background: none;
            border: none;
            color: #6c757d;
        }

        /* --- Styles Thème Bleu de Nuit (Dark) --- */
        [data-bs-theme="dark"] body {
            background-color: #0b1c31; 
            color: #e9ecef;
            font-family: 'Consolas', 'Courier New', monospace; 
        }
        [data-bs-theme="dark"] .login-container {
            background-color: #1a2b40; 
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
        }
        [data-bs-theme="dark"] .form-label,
        [data-bs-theme="dark"] .form-check-label {
            color: #adb5bd;
        }
        [data-bs-theme="dark"] .form-control {
            background-color: #263851;
            border-color: #495057;
            color: #e9ecef;
        }
        [data-bs-theme="dark"] .forgot-password-link,
        [data-bs-theme="dark"] .toggle-password {
            color: #adb5bd;
        }
    </style>
</head>
<body>

<i class="theme-toggle-icon bi bi-<?php echo ($current_theme === 'dark' ? 'sun-fill' : 'moon-fill'); ?>" id="theme-toggle"></i>

<div class="login-container">
    <div class="login-header">
        <i class="bi bi-person-circle fs-1"></i>
        <h2>Authentification</h2>
    </div>

    <?= $message ?>

    <form method="POST">
        
        <div class="mb-3">
            <label for="identifiant" class="form-label">Email ou Téléphone</label>
            <input 
                type="text" 
                class="form-control" 
                id="identifiant" 
                name="identifiant" 
                value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>" 
                required
            >
        </div>

        <div class="mb-3 password-toggle-group">
            <label for="password" class="form-label">Mot de Passe</label>
            <input 
                type="password" 
                class="form-control" 
                id="password" 
                name="password" 
                required
            >
            <button type="button" class="toggle-password" id="toggle-password-btn">afficher</button>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember_me" id="remember_me">
                <label class="form-check-label" for="remember_me">
                    Se souvenir de moi
                </label>
            </div>
            <a href="mot_de_passe_oublie.php" class="forgot-password-link">Mot de passe oublié ?</a>
        </div>
        
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Se connecter</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePasswordButton = document.getElementById('toggle-password-btn');
        const passwordInput = document.getElementById('password');
        const themeToggleIcon = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;

        // --- 1. Fonctionnalité Afficher/Masquer Mot de Passe ---
        if (togglePasswordButton && passwordInput) {
            togglePasswordButton.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                this.textContent = (type === 'text') ? 'masquer' : 'afficher';
            });
        }

        // --- 2. Fonctionnalité Bascule Thème ---
        if (themeToggleIcon) {
            themeToggleIcon.addEventListener('click', function() {
                const currentTheme = htmlElement.getAttribute('data-bs-theme');
                const newTheme = (currentTheme === 'dark') ? 'light' : 'dark';
                
                htmlElement.setAttribute('data-bs-theme', newTheme);
                
                themeToggleIcon.className = 'theme-toggle-icon bi bi-' + (newTheme === 'dark' ? 'sun-fill' : 'moon-fill');
                
                document.cookie = `theme=${newTheme}; max-age=${30 * 24 * 60 * 60}; path=/; samesite=Lax`;
            });
        }
    });
</script>
</body>
</html>
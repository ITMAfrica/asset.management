<?php
// --- Partie PHP de Traitement du Formulaire ---
$error_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer et sécuriser les entrées
    $username = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Logique de connexion (EXEMPLE SIMPLIFIÉ - À NE PAS UTILISER EN PRODUCTION)
    if (empty($username) || empty($password)) {
        $error_message = "Veuillez entrer un nom d'utilisateur et un mot de passe.";
    } elseif ($username === 'admin' && $password === 'admin123') {
        // En cas de succès : Redirection vers la page du tableau de bord
        // header("Location: dashboard.php");
        // exit();
        $error_message = "Connexion réussie (Redirection simulée) !";
    } else {
        $error_message = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}
// ---------------------------------------------
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SalesFlow - Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* --- VARIABLES DE THÈME --- */
        /* Thème CLAIR (Défaut) */
        :root {
            --bg-color: #f7f9fc; 
            --card-bg: #ffffff; 
            --input-bg: #ffffff; 
            --primary-color: #7d33ff; 
            --secondary-color: #337dff; 
            --text-primary: #333333; 
            --text-secondary: #888888; 
            --demo-role-text: #555555; 
            --demo-user-text: #a8a8a8; 
            --shadow-color: rgba(0, 0, 0, 0.1); 
            --icon-color: #555555; 
            font-family: Arial, sans-serif; /* Police claire par défaut */
        }

        /* Thème SOMBRE (Bleu de Nuit) */
        .theme-dark {
            --bg-color: #0d1a2b; 
            --card-bg: #1c2e42; 
            --input-bg: #293f55; 
            --text-primary: #f0f0f0; 
            --text-secondary: #a0a0a0; 
            --demo-role-text: #b0c2d6; 
            --demo-user-text: #7f90a8; 
            --shadow-color: rgba(0, 0, 0, 0.4); 
            --icon-color: #ffffff; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Changement de police pour le thème sombre */
        }

        /* --- STYLES GÉNÉRAUX --- */
        body {
            background-color: var(--bg-color);
            transition: background-color 0.5s, color 0.5s;
            font-family: var(--font-family); /* Utiliser la variable pour la police */
        }

        .main-container {
            min-height: 100vh;
        }

        /* CARTE DE CONNEXION */
        .login-card {
            background-color: var(--card-bg);
            border-radius: 1.5rem; 
            box-shadow: 0 10px 40px var(--shadow-color); 
            max-width: 450px;
            width: 100%;
            position: relative;
        }

        /* EN-TÊTE ET LOGO */
        .header {
            position: relative;
            padding-bottom: 20px;
        }

        .logo-area {
            flex-grow: 1; 
        }

        .salesflow-icon {
            width: 35px;
            height: 35px;
            stroke: var(--primary-color);
            fill: none;
            transform: rotate(20deg);
        }

        .app-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .connect-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: -5px;
        }

        /* BOUTON DE THÈME */
        .btn-theme-toggle {
            position: absolute;
            top: 0;
            right: 0;
            background: transparent;
            cursor: pointer;
            color: var(--icon-color);
        }

        #toggle-icon {
            width: 24px;
            height: 24px;
            stroke: var(--icon-color);
        }

        /* FORMULAIRE ET INPUTS */
        .login-input-box {
            background-color: var(--input-bg);
            border: 1px solid var(--shadow-color);
            box-shadow: none !important;
        }

        .input-label {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .custom-input {
            background: transparent !important;
            font-size: 1rem;
            color: var(--text-primary);
            padding: 0 !important; 
            box-shadow: none !important;
        }

        .custom-input::placeholder {
            color: var(--text-secondary);
            opacity: 0.6;
        }

        /* Bouton Se connecter */
        .btn-salesflow {
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            font-weight: 600;
            color: white;
            border-radius: 0.8rem;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 15px rgba(51, 125, 255, 0.4);
        }

        .btn-salesflow:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(51, 125, 255, 0.6);
        }

        /* COMPTES DE DÉMONSTRATION */
        .demo-accounts, .client-accounts {
            font-size: 0.9rem;
        }

        .demo-header {
            font-weight: 600;
            color: var(--text-primary);
        }

        .demo-role {
            font-weight: 600;
            color: var(--demo-role-text);
            margin-bottom: 0.1rem;
        }

        .demo-user {
            font-family: monospace; 
            font-size: 0.85rem;
            color: var(--demo-user-text);
            margin-bottom: 0.5rem;
        }

        .hr-divider {
            border-top: 1px solid var(--text-secondary);
            opacity: 0.2;
        }
        
        /* Ajustements pour le scrollbar dans la liste de démo */
        .demo-list-scroll {
            max-height: 140px; /* Limite de hauteur */
            overflow-y: auto; /* Ajout d'une barre de défilement */
            padding-right: 15px; /* Espace pour la barre de défilement */
        }
        .demo-list-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .demo-list-scroll::-webkit-scrollbar-thumb {
            background-color: var(--text-secondary);
            border-radius: 10px;
        }
    </style>
</head>
<body class="theme-light">
    <div class="main-container d-flex justify-content-center align-items-center vh-100">
        <div class="login-card p-4 p-md-5">
            
            <div class="header d-flex justify-content-between align-items-center mb-4">
                <div class="logo-area text-center mx-auto">
                    <svg class="salesflow-icon" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                    <h2 class="app-name mt-2">SalesFlow</h2>
                    <p class="connect-text">Connectez-vous à votre compte</p>
                </div>
                <button id="theme-toggle" class="btn-theme-toggle border-0 p-0" aria-label="Changer de thème">
                    <svg id="toggle-icon" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                </button>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= $error_message ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                
                <div class="login-input-box p-3 mb-3 shadow-sm rounded-3">
                    <label for="username" class="form-label visually-hidden">Nom d'utilisateur</label>
                    <p class="input-label mb-1">Nom d'utilisateur</p>
                    <input type="text" class="form-control border-0 p-0 custom-input" id="username" name="username" placeholder="Entrez votre nom d'utilisateur">
                </div>

                <div class="login-input-box p-3 mb-4 shadow-sm rounded-3">
                    <label for="password" class="form-label visually-hidden">Mot de passe</label>
                    <p class="input-label mb-1">Mot de passe</p>
                    <input type="password" class="form-control border-0 p-0 custom-input" id="password" name="password" placeholder="Entrez votre mot de passe">
                    </div>

                <button type="submit" class="btn-salesflow w-100 mb-4 py-2">
                    Se connecter
                </button>
            </form>
            
            <hr class="my-3 hr-divider">
        
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleButton = document.getElementById('theme-toggle');
            const body = document.body;

            // Fonction pour appliquer le thème
            function applyTheme(theme) {
                if (theme === 'dark') {
                    body.classList.remove('theme-light');
                    body.classList.add('theme-dark');
                    // Optionnel: Mettre à jour l'icône de la bascule si besoin
                } else {
                    body.classList.remove('theme-dark');
                    body.classList.add('theme-light');
                }
            }

            // 1. Charger le thème sauvegardé
            const savedTheme = localStorage.getItem('salesflow-theme') || 'light';
            applyTheme(savedTheme);

            // 2. Gérer le clic sur le bouton de bascule
            toggleButton.addEventListener('click', () => {
                const currentTheme = body.classList.contains('theme-dark') ? 'dark' : 'light';
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';

                // Appliquer et sauvegarder le nouveau thème
                applyTheme(newTheme);
                localStorage.setItem('salesflow-theme', newTheme);
            });
        });
    </script>
</body>
</html>
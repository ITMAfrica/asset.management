<?php
session_start();
require_once 'db_connexion.php'; 

// --- GESTION DE LA LANGUE : Dictionnaire et Sélection ---
// Définir la langue par défaut si elle n'est pas déjà définie sur cette page
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'fr'; // Français par défaut
}

// Changer la langue si un formulaire ou un paramètre a été soumis
if (isset($_GET['lang'])) {
    $new_lang = $_GET['lang'] === 'en' ? 'en' : 'fr';
    $_SESSION['lang'] = $new_lang;
    // Redirection pour nettoyer l'URL
    header("Location: index");
    exit();
}

$current_lang = $_SESSION['lang'];

// Dictionnaire de traduction pour index.php
$texts = [
    'fr' => [
        'title' => 'Authentification',
        'auth_title' => 'Authentification',
        'id_label' => 'Email ou Téléphone',
        'password_label' => 'Mot de Passe',
        'show_password' => 'afficher',
        'hide_password' => 'masquer',
        'remember_me' => 'Se souvenir de moi',
        'forgot_password' => 'Mot de passe oublié ?',
        'login_button' => 'Se connecter',
        // Messages d'erreur
        'error_empty_fields' => 'Veuillez saisir votre identifiant et votre mot de passe.',
        'error_incorrect_credentials' => 'Identifiant ou mot de passe incorrect.',
        'error_db_connection' => 'Erreur de base de données : Connexion impossible.',
        // NOUVEAU MESSAGE D'ERREUR
        'error_account_blocked' => 'Votre compte a été bloqué, prière de contacter votre Administrateur IT.', 
    ],
    'en' => [
        'title' => 'Authentication',
        'auth_title' => 'Authentication',
        'id_label' => 'Email or Phone Number',
        'password_label' => 'Password',
        'show_password' => 'show',
        'hide_password' => 'hide',
        'remember_me' => 'Remember me',
        'forgot_password' => 'Forgot password?',
        'login_button' => 'Log in',
        // Error messages
        'error_empty_fields' => 'Please enter your username and password.',
        'error_incorrect_credentials' => 'Incorrect username or password.',
        'error_db_connection' => 'Database error: Connection impossible.',
        // NOUVEAU MESSAGE D'ERREUR
        'error_account_blocked' => 'Your account has been blocked, please contact your IT Administrator.',
    ]
];

// Sélectionner les textes dans la langue actuelle
$T = $texts[$current_lang];


// --- NOUVEAU : Inclure PHPMailer pour les OTP ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

// --- NOUVEAU : Fonction pour envoyer l'email OTP ---
function sendOtpEmail($email, $otp, $nom_complet) {
    // ... (Votre fonction sendOtpEmail reste inchangée) ...
    $mail = new PHPMailer(true);
    try {
        // Configuration du serveur SMTP (à adapter)
        $mail->isSMTP();
        $mail->Host      = 'mail.pag-tech.net';
        $mail->SMTPAuth  = true;
        $mail->Username  = 'it.services@pag-tech.net';
        $mail->Password  = 'IT_service@2024';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port      = 587;
        $mail->CharSet     = 'UTF-8';

        $mail->setFrom('it.services@pag-tech.net', 'Sécurité du Compte');
        $mail->addAddress($email, $nom_complet);

        $mail->isHTML(true);
        $mail->Subject = 'Votre code de vérification unique';
        $mail->Body      = "Bonjour " . htmlspecialchars($nom_complet) . ",<br><br>Votre code de vérification est : <b>$otp</b><br>Ce code expirera dans 10 minutes.<br><br>Si vous n'avez pas demandé ce code, veuillez ignorer cet email.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// --- NOUVEAU : Fonction pour gérer le début de session et l'envoi d'OTP ---
function start_otp_verification_process($pdo, $utilisateur) {
    // 1. Générer un OTP sécurisé
    $otp = random_int(100000, 999999);

    // 2. Stocker l'OTP et sa date d'expiration dans la session
    $_SESSION['otp_code'] = password_hash((string)$otp, PASSWORD_DEFAULT); // Stocker le hash de l'OTP
    $_SESSION['otp_expires'] = time() + 600; // Expiration dans 10 minutes

    // 3. Stocker temporairement les infos utilisateur nécessaires pour la vérification
    $_SESSION['user_id_pending_otp'] = $utilisateur['id'];
    $_SESSION['user_nom_pending_otp'] = $utilisateur['nom_complet'];
    $_SESSION['user_profil_pending_otp'] = $utilisateur['profil'];

    // 4. Envoyer l'email
    sendOtpEmail($utilisateur['email'], $otp, $utilisateur['nom_complet']);

    // 5. Rediriger vers la page de vérification
    header('Location: verification_otp');
    exit();
}

// Redirection si l'utilisateur est entièrement authentifié (session + OTP)
if (isset($_SESSION['user_id']) && isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true) {
    header('Location: menu'); 
    exit();
}

// --- NOUVEAU : Vérification du cookie "Se souvenir de moi" (Ajusté pour le statut) ---
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    list($selector, $validator) = explode(':', $_COOKIE['remember_me'], 2);

    if ($selector && $validator) {
        $sql = "SELECT * FROM t_auth_tokens WHERE selector = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$selector]);
        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($token_data && hash_equals($token_data['hashed_validator'], hash('sha256', $validator))) {
            if (strtotime($token_data['expires']) > time()) {
                // Le jeton est valide, récupérer les infos de l'utilisateur y compris le statut
                $user_stmt = $pdo->prepare("SELECT id, nom_complet, email, profil, statut_compte FROM t_users WHERE id = ?"); // AJOUT DE STATUT_COMPTE
                $user_stmt->execute([$token_data['user_id']]);
                $utilisateur = $user_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($utilisateur) {
                    // VÉRIFICATION DU STATUT DE COMPTE
                    if ($utilisateur['statut_compte'] === 'Actif') {
                        // Démarrer le processus OTP
                        start_otp_verification_process($pdo, $utilisateur);
                    } else {
                        // Si le compte est inactif, supprimer le cookie pour forcer la connexion manuelle
                        setcookie('remember_me', '', time() - 3600, '/', '', false, true);
                        $message = "<div class='alert alert-danger'>{$T['error_account_blocked']}</div>";
                    }
                }
            }
        }
    }
}


$message = '';
// --- 1. Traitement du formulaire (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifiant = trim($_POST['identifiant'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($identifiant) || empty($password)) {
        // Traduire le message d'erreur
        $message = "<div class='alert alert-warning'>{$T['error_empty_fields']}</div>";
    } else {
        try {
            // AJOUT DE statut_compte dans la requête
            $sql = "SELECT id, nom_complet, email, password, profil, statut_compte FROM t_users WHERE email = ? OR telephone = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$identifiant, $identifiant]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($utilisateur && password_verify($password, $utilisateur['password'])) {
                
                // === VÉRIFICATION DU STATUT DE COMPTE (NOUVEAU) ===
                if ($utilisateur['statut_compte'] === 'Inactif') {
                    // Le mot de passe est correct mais le compte est bloqué.
                    $message = "<div class='alert alert-danger'>{$T['error_account_blocked']}</div>";
                } else {
                    // MOT DE PASSE CORRECT ET COMPTE ACTIF
                    
                    // ... (Logique "Se souvenir de moi" inchangée) ...
                    if (isset($_POST['remember_me'])) {
                        $selector = bin2hex(random_bytes(12));
                        $validator = bin2hex(random_bytes(32));
                        $hashed_validator = hash('sha256', $validator);
                        $expires = time() + (86400 * 30); // 30 jours

                        $sql_insert_token = "INSERT INTO t_auth_tokens (selector, hashed_validator, user_id, expires) VALUES (?, ?, ?, ?)";
                        $stmt_token = $pdo->prepare($sql_insert_token);
                        $stmt_token->execute([$selector, $hashed_validator, $utilisateur['id'], date('Y-m-d H:i:s', $expires)]);
                        
                        setcookie('remember_me', "$selector:$validator", $expires, '/', '', false, true);
                    }
                    
                    // Connexion réussie, lancer la vérification OTP
                    start_otp_verification_process($pdo, $utilisateur);
                }
                
            } else {
                // Traduire le message d'erreur (Identifiant ou mot de passe incorrect)
                $message = "<div class='alert alert-danger'>{$T['error_incorrect_credentials']}</div>";
            }
        } catch (PDOException $e) {
            // Traduire le message d'erreur
            $message = "<div class='alert alert-danger'>{$T['error_db_connection']}</div>";
        }
    }
}

$current_theme = $_COOKIE['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" data-bs-theme="<?php echo htmlspecialchars($current_theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $T['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* --- Styles de Base (Centrage et Responsive) --- */
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
            transition: background-color 0.5s, color 0.5s;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
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
        .lang-switcher {
            position: absolute;
            top: 15px;
            left: 15px;
        }
        .lang-switcher a {
            color: #6c757d;
            text-decoration: none;
            font-weight: bold;
            padding: 5px;
        }
        .lang-switcher a.active {
            color: #007bff;
            border-bottom: 2px solid #007bff;
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
        [data-bs-theme="dark"] .toggle-password,
        [data-bs-theme="dark"] .lang-switcher a {
            color: #adb5bd;
        }
        [data-bs-theme="dark"] .lang-switcher a.active {
            color: #4da6ff;
            border-bottom-color: #4da6ff;
        }
    </style>
</head>
<body>

<div class="lang-switcher">
    <a href="?lang=fr" class="<?php echo ($current_lang === 'fr' ? 'active' : ''); ?>">FR</a>
    |
    <a href="?lang=en" class="<?php echo ($current_lang === 'en' ? 'active' : ''); ?>">EN</a>
</div>
<i class="theme-toggle-icon bi bi-<?php echo ($current_theme === 'dark' ? 'sun-fill' : 'moon-fill'); ?>" id="theme-toggle"></i>

<div class="login-container">
    <div class="login-header">
        <i class="bi bi-person-circle fs-1"></i>
        <h2><?php echo $T['auth_title']; ?></h2>
    </div>

    <?= $message ?>

    <form method="POST">
        
        <div class="mb-3">
            <label for="identifiant" class="form-label"><?php echo $T['id_label']; ?></label>
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
            <label for="password" class="form-label"><?php echo $T['password_label']; ?></label>
            <input 
                type="password" 
                class="form-control" 
                id="password" 
                name="password" 
                required
            >
            <button type="button" class="toggle-password" id="toggle-password-btn"><?php echo $T['show_password']; ?></button>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember_me" id="remember_me">
                <label class="form-check-label" for="remember_me">
                    <?php echo $T['remember_me']; ?>
                </label>
            </div>
            <a href="mot_de_passe_oublie.php" class="forgot-password-link"><?php echo $T['forgot_password']; ?></a>
        </div>
        
        <div class="d-grid">
            <button type="submit" class="btn btn-primary"><?php echo $T['login_button']; ?></button>
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

        // Mise à jour de l'affichage du mot de passe
        if (togglePasswordButton && passwordInput) {
            // Récupérer les textes de la langue actuelle depuis le PHP pour le JS
            const showText = "<?php echo $T['show_password']; ?>";
            const hideText = "<?php echo $T['hide_password']; ?>";

            togglePasswordButton.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                // Utiliser les variables traduites
                this.textContent = (type === 'text') ? hideText : showText;
            });
            // Assurez-vous que le texte initial est correct (par défaut 'afficher')
            if (passwordInput.getAttribute('type') === 'password') {
                togglePasswordButton.textContent = showText;
            } else {
                togglePasswordButton.textContent = hideText;
            }
        }

        // Logique du thème sombre/clair (inchangée)
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
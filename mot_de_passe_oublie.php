<?php
session_start();

require_once 'db_connexion.php';

// --- GESTION DE LA LANGUE : Dictionnaire et Sélection ---
// La langue a été définie sur index.php, on la récupère.
$current_lang = $_SESSION['lang'] ?? 'fr';

// Dictionnaire de traduction pour les 3 étapes
$texts = [
    'fr' => [
        'title' => 'Récupération de Mot de Passe',
        'header' => 'Mot de Passe Oublié',
        'id_label' => 'Email ou Téléphone',
        'code_label' => 'Code de Vérification',
        'back_link' => 'Retour à la page de connexion',
        
        // Étape 1: Demande
        'step1_instruction' => 'Veuillez saisir votre adresse e-mail ou votre numéro de téléphone pour commencer le processus de récupération.',
        'step1_button' => 'Envoyer le code de vérification',
        'step1_success' => 'Un code a été envoyé à l\'adresse e-mail associée à ce compte.',
        'step1_error_send' => 'Impossible d\'envoyer l\'e-mail de vérification. Veuillez réessayer.',
        'step1_error_not_found' => 'Aucun compte trouvé avec cet identifiant.',
        
        // Étape 2: Vérification OTP
        'step2_instruction' => 'Un code à 6 chiffres a été envoyé à votre adresse e-mail. Veuillez le saisir ci-dessous.',
        'step2_button' => 'Vérifier et Réinitialiser',
        'step2_error_expired' => 'La demande a expiré. Veuillez recommencer.',
        'step2_error_incorrect' => 'Code de vérification incorrect.',
        'step2_error_send_new_pass' => 'Erreur lors de l\'envoi du nouveau mot de passe. Veuillez contacter le support.',

        // Étape 3: Succès
        'step3_title' => 'Réinitialisation Réussie !',
        'step3_body_line1' => 'Un nouveau mot de passe temporaire a été envoyé à votre adresse e-mail.',
        'step3_body_line2' => 'Veuillez l\'utiliser pour vous connecter, puis changez-le immédiatement pour sécuriser votre compte.',
    ],
    'en' => [
        'title' => 'Password Recovery',
        'header' => 'Forgot Password',
        'id_label' => 'Email or Phone Number',
        'code_label' => 'Verification Code',
        'back_link' => 'Back to login page',

        // Step 1: Request
        'step1_instruction' => 'Please enter your email address or phone number to start the recovery process.',
        'step1_button' => 'Send verification code',
        'step1_success' => 'A code has been sent to the email address associated with this account.',
        'step1_error_send' => 'Could not send the verification email. Please try again.',
        'step1_error_not_found' => 'No account found with this identifier.',

        // Step 2: OTP Verification
        'step2_instruction' => 'A 6-digit code has been sent to your email address. Please enter it below.',
        'step2_button' => 'Verify and Reset',
        'step2_error_expired' => 'The request has expired. Please try again.',
        'step2_error_incorrect' => 'Incorrect verification code.',
        'step2_error_send_new_pass' => 'Error sending the new password. Please contact support.',
        
        // Step 3: Success
        'step3_title' => 'Reset Successful!',
        'step3_body_line1' => 'A new temporary password has been sent to your email address.',
        'step3_body_line2' => 'Please use it to log in, then change it immediately to secure your account.',
    ]
];

// Sélectionner les textes dans la langue actuelle
$T = $texts[$current_lang];


// --- Inclure PHPMailer ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

$message = '';
$etape = 1; // 1: Demande, 2: Vérification OTP, 3: Succès

// Réinitialiser l'étape si des variables de session existent
if (isset($_SESSION['reset_user_id']) && !isset($_POST['otp_code'])) {
    $etape = 2;
}

// --- Fonction pour envoyer l'email OTP ---
function envoyerEmailOTP($email, $otp, $nom_complet) {
    // ... (Logique inchangée pour le corps de l'email) ...
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host      = 'mail.pag-tech.net';
        $mail->SMTPAuth  = true;
        $mail->Username  = 'it.services@pag-tech.net';
        $mail->Password  = 'IT_service@2024';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port      = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('it.services@pag-tech.net', 'Réinitialisation de Mot de Passe');
        $mail->addAddress($email, $nom_complet);

        $mail->isHTML(true);
        $mail->Subject = 'Votre code de réinitialisation de mot de passe';
        $mail->Body     = "Bonjour " . htmlspecialchars($nom_complet) . ",<br><br>Votre code de vérification est : <b>$otp</b><br>Ce code expirera dans 10 minutes.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error (OTP): {$mail->ErrorInfo}");
        return false;
    }
}

// --- Fonction pour envoyer le nouveau mot de passe ---
function envoyerNouveauMotDePasse($email, $nouveau_mdp, $nom_complet) {
    // ... (Logique inchangée pour le corps de l'email) ...
    $mail = new PHPMailer(true);
    try {
        // Mêmes paramètres SMTP
        $mail->isSMTP();
        $mail->Host      = 'mail.pag-tech.net';
        $mail->SMTPAuth  = true;
        $mail->Username  = 'it.services@pag-tech.net';
        $mail->Password  = 'IT_service@2024';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port      = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('it.services@pag-tech.net', 'Votre Nouveau Mot de Passe');
        $mail->addAddress($email, $nom_complet);

        $mail->isHTML(true);
        $mail->Subject = 'Votre mot de passe a été réinitialisé';
        $mail->Body     = "Bonjour " . htmlspecialchars($nom_complet) . ",<br><br>Votre mot de passe a été réinitialisé avec succès. Voici votre nouveau mot de passe temporaire : <b>$nouveau_mdp</b><br><br>Pour des raisons de sécurité, nous vous recommandons vivement de le changer après vous être connecté.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error (New Pass): {$mail->ErrorInfo}");
        return false;
    }
}


// --- GESTION DES SOUMISSIONS DE FORMULAIRE (Traductions Appliquées) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ÉTAPE 1 : L'utilisateur soumet son email/téléphone
    if (isset($_POST['identifiant'])) {
        $identifiant = trim($_POST['identifiant']);
        
        $sql = "SELECT id, nom_complet, email FROM t_users WHERE email = ? OR telephone = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$identifiant, $identifiant]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur) {
            $otp = random_int(100000, 999999);
            $_SESSION['reset_otp'] = password_hash((string)$otp, PASSWORD_DEFAULT);
            $_SESSION['reset_otp_expires'] = time() + 600; // 10 minutes
            $_SESSION['reset_user_id'] = $utilisateur['id'];
            $_SESSION['reset_user_email'] = $utilisateur['email'];
            $_SESSION['reset_user_nom'] = $utilisateur['nom_complet'];

            if (envoyerEmailOTP($utilisateur['email'], $otp, $utilisateur['nom_complet'])) {
                $etape = 2; // Passer à l'étape de vérification OTP
                // Traduction du message de succès
                $message = "<div class='alert alert-success'>{$T['step1_success']}</div>";
            } else {
                // Traduction du message d'erreur
                $message = "<div class='alert alert-danger'>{$T['step1_error_send']}</div>";
            }
        } else {
            // Traduction du message d'erreur
            $message = "<div class='alert alert-danger'>{$T['step1_error_not_found']}</div>";
        }
    }

    // ÉTAPE 2 : L'utilisateur soumet le code OTP
    elseif (isset($_POST['otp_code'])) {
        if (empty($_SESSION['reset_user_id']) || time() > $_SESSION['reset_otp_expires']) {
            // Traduction du message d'erreur
            $message = "<div class='alert alert-danger'>{$T['step2_error_expired']}</div>";
            session_unset(); 
            $etape = 1;
        } else {
            $otp_saisi = $_POST['otp_code'];
            if (password_verify($otp_saisi, $_SESSION['reset_otp'])) {
                
                $nouveau_mdp = bin2hex(random_bytes(6)); 
                $hashed_mdp = password_hash($nouveau_mdp, PASSWORD_DEFAULT);

                $update_sql = "UPDATE t_users SET password = ? WHERE id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([$hashed_mdp, $_SESSION['reset_user_id']]);

                if (envoyerNouveauMotDePasse($_SESSION['reset_user_email'], $nouveau_mdp, $_SESSION['reset_user_nom'])) {
                    $etape = 3; 
                } else {
                    // Traduction du message d'erreur
                    $message = "<div class='alert alert-danger'>{$T['step2_error_send_new_pass']}</div>";
                    $etape = 2;
                }

                unset($_SESSION['reset_otp'], $_SESSION['reset_otp_expires'], $_SESSION['reset_user_id'], $_SESSION['reset_user_email'], $_SESSION['reset_user_nom']);
            } else {
                // Traduction du message d'erreur
                $message = "<div class='alert alert-danger'>{$T['step2_error_incorrect']}</div>";
                $etape = 2;
            }
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
        /* --- Copie exacte des styles de index.php pour la cohérence --- */
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
            max-width: 450px; /* Légèrement plus large pour les instructions */
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
        [data-bs-theme="dark"] body {
            background-color: #0b1c31; 
            color: #e9ecef;
        }
        [data-bs-theme="dark"] .login-container {
            background-color: #1a2b40; 
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
        }
        [data-bs-theme="dark"] .form-label {
            color: #adb5bd;
        }
        [data-bs-theme="dark"] .form-control {
            background-color: #263851;
            border-color: #495057;
            color: #e9ecef;
        }
    </style>
</head>
<body>

<i class="theme-toggle-icon bi bi-<?php echo ($current_theme === 'dark' ? 'sun-fill' : 'moon-fill'); ?>" id="theme-toggle"></i>

<div class="login-container">
    <div class="login-header">
        <i class="bi bi-key-fill fs-1"></i>
        <h2><?php echo $T['header']; ?></h2>
    </div>

    <?= $message ?>

    <?php if ($etape == 1): ?>
        <p class="text-center text-muted"><?php echo $T['step1_instruction']; ?></p>
        <form method="POST">
            <div class="mb-3">
                <label for="identifiant" class="form-label"><?php echo $T['id_label']; ?></label>
                <input type="text" class="form-control" id="identifiant" name="identifiant" required autofocus>
            </div>
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary"><?php echo $T['step1_button']; ?></button>
            </div>
        </form>

    <?php elseif ($etape == 2): ?>
        <p class="text-center text-muted"><?php echo $T['step2_instruction']; ?></p>
        <form method="POST">
            <div class="mb-3">
                <label for="otp_code" class="form-label"><?php echo $T['code_label']; ?></label>
                <input type="text" class="form-control text-center fs-4" id="otp_code" name="otp_code" required maxlength="6" pattern="\d{6}" autofocus>
            </div>
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary"><?php echo $T['step2_button']; ?></button>
            </div>
        </form>

    <?php elseif ($etape == 3): ?>
        <div class="alert alert-success text-center">
            <h4 class="alert-heading"><?php echo $T['step3_title']; ?></h4>
            <p><?php echo $T['step3_body_line1']; ?></p>
            <hr>
            <p class="mb-0"><?php echo $T['step3_body_line2']; ?></p>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="index" class="text-decoration-none"><?php echo $T['back_link']; ?></a>
    </div>
</div>

<script>
    // --- Script JS identique à index.php pour le changement de thème ---
    document.addEventListener('DOMContentLoaded', function() {
        const themeToggleIcon = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;

        if (themeToggleIcon) {
            themeToggleIcon.addEventListener('click', function() {
                const currentTheme = htmlElement.getAttribute('data-bs-theme');
                const newTheme = (currentTheme === 'dark') ? 'light' : 'dark';
                
                htmlElement.setAttribute('data-bs-theme', newTheme);
                themeToggleIcon.className = 'theme-toggle-icon bi bi-' + (newTheme === 'dark' ? 'sun-fill' : 'moon-fill');
                
                // Sauvegarder le thème dans un cookie
                document.cookie = `theme=${newTheme}; max-age=${30 * 24 * 60 * 60}; path=/; samesite=Lax`;
            });
        }
    });
</script>
</body>
</html>
<?php
session_start();

// --- GESTION DE LA LANGUE : Dictionnaire et Sélection ---
// La langue a été définie sur index.php, on la récupère.
$current_lang = $_SESSION['lang'] ?? 'fr';

// Dictionnaire de traduction pour verification_otp.php
$texts = [
    'fr' => [
        'title' => 'Vérification 2-Facteurs',
        'header' => 'Vérification Requise',
        'email_sent' => 'Un code a été envoyé à votre adresse e-mail. Veuillez le saisir ci-dessous.',
        'code_label' => 'Code de Vérification (6 chiffres)',
        'button' => 'Vérifier et Continuer',
        'back_link' => 'Retour à la connexion',
        // Messages d'erreur
        'error_expired' => 'Le code de vérification a expiré. Veuillez vous reconnecter.',
        'error_incorrect' => 'Code de vérification incorrect.',
    ],
    'en' => [
        'title' => '2-Factor Verification',
        'header' => 'Verification Required',
        'email_sent' => 'A code has been sent to your email address. Please enter it below.',
        'code_label' => 'Verification Code (6 digits)',
        'button' => 'Verify and Continue',
        'back_link' => 'Back to login',
        // Error messages
        'error_expired' => 'The verification code has expired. Please log in again.',
        'error_incorrect' => 'Incorrect verification code.',
    ]
];

// Sélectionner les textes dans la langue actuelle
$T = $texts[$current_lang];


// Rediriger si l'utilisateur n'est pas en attente de vérification OTP
if (!isset($_SESSION['user_id_pending_otp'])) {
    // Redirection vers index.php, la langue sera conservée grâce à la session
    header('Location: index');
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp_saisi = $_POST['otp_code'] ?? '';

    // Vérifier si l'OTP n'a pas expiré et si le code est correct
    if (isset($_SESSION['otp_expires']) && time() > $_SESSION['otp_expires']) {
        // Message d'erreur traduit
        $message = "<div class='alert alert-danger'>{$T['error_expired']}</div>";
        // Nettoyer la session (sans détruire la session elle-même pour garder la langue)
        session_unset();
        session_destroy();
    } elseif (isset($_SESSION['otp_code']) && password_verify($otp_saisi, $_SESSION['otp_code'])) {
        // OTP correct : finaliser la connexion
        $_SESSION['user_id'] = $_SESSION['user_id_pending_otp'];
        $_SESSION['user_nom'] = $_SESSION['user_nom_pending_otp'];
        $_SESSION['user_profil'] = $_SESSION['user_profil_pending_otp'];
        $_SESSION['otp_verified'] = true;

        // Nettoyer les variables temporaires
        unset($_SESSION['user_id_pending_otp'], $_SESSION['user_nom_pending_otp'], $_SESSION['user_profil_pending_otp'], $_SESSION['otp_code'], $_SESSION['otp_expires']);

        header('Location: menu');
        exit();
    } else {
        // Message d'erreur traduit
        $message = "<div class='alert alert-danger'>{$T['error_incorrect']}</div>";
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
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; background-color: #f8f9fa; }
        .login-container { max-width: 400px; width: 100%; padding: 30px; background-color: #ffffff; border-radius: 10px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); }
        .login-header { text-align: center; margin-bottom: 30px; color: #007bff; }
        [data-bs-theme="dark"] body { background-color: #0b1c31; }
        [data-bs-theme="dark"] .login-container { background-color: #1a2b40; }
        [data-bs-theme="dark"] .form-label { color: #adb5bd; }
        [data-bs-theme="dark"] .form-control { background-color: #263851; border-color: #495057; color: #e9ecef; }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <i class="bi bi-shield-lock-fill fs-1"></i>
        <h2><?php echo $T['header']; ?></h2>
    </div>

    <p class="text-center mb-4"><?php echo $T['email_sent']; ?></p>

    <?= $message ?>

    <form method="POST">
        <div class="mb-3">
            <label for="otp_code" class="form-label"><?php echo $T['code_label']; ?></label>
            <input type="text" class="form-control text-center fs-4" id="otp_code" name="otp_code" required maxlength="6" pattern="\d{6}" autofocus>
        </div>
        
        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary"><?php echo $T['button']; ?></button>
        </div>
    </form>
    <div class="text-center mt-3">
        <a href="index"><?php echo $T['back_link']; ?></a>
    </div>
</div>

</body>
</html>
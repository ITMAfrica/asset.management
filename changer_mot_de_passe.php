<?php
// Démarre la session
session_start();

// Rediriger si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_id']) || !isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    session_unset();
    session_destroy();
    header("Location: index");
    exit();
}

// Inclure le fichier de connexion à la base de données
// Assurez-vous que ce fichier existe et initialise $pdo
require_once 'db_connexion.php'; 

$message = '';
$is_success = false;

// Récupérer la langue actuelle
$current_lang = $_SESSION['lang'] ?? 'fr';

// Dictionnaire de traduction (Inchangé)
$texts = [
    'fr' => [
        'page_title' => 'Changer le Mot de Passe',
        'current_password' => 'Ancien Mot de Passe',
        'new_password' => 'Nouveau Mot de Passe',
        'confirm_password' => 'Confirmer le Nouveau Mot de Passe',
        'change_btn' => 'Changer le Mot de Passe',
        'placeholder_current' => 'Entrez l\'ancien mot de passe',
        'placeholder_new' => 'Entrez le nouveau mot de passe',
        'placeholder_confirm' => 'Confirmez le nouveau mot de passe',
        'error_empty' => 'Veuillez remplir tous les champs.',
        'error_match' => 'Le nouveau mot de passe et la confirmation ne correspondent pas.',
        'error_current_mismatch' => 'L\'ancien mot de passe est incorrect.',
        'error_update' => 'Erreur lors de la mise à jour du mot de passe.',
        'success' => 'Votre mot de passe a été mis à jour avec succès.',
        'error_same' => 'Le nouveau mot de passe doit être différent de l\'ancien.',
    ],
    'en' => [
        'page_title' => 'Change Password',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'confirm_password' => 'Confirm New Password',
        'change_btn' => 'Change Password',
        'placeholder_current' => 'Enter current password',
        'placeholder_new' => 'Enter new password',
        'placeholder_confirm' => 'Confirm new password',
        'error_empty' => 'Please fill in all fields.',
        'error_match' => 'New password and confirmation do not match.',
        'error_current_mismatch' => 'The current password is incorrect.',
        'error_update' => 'Error updating password.',
        'success' => 'Your password has been successfully updated.',
        'error_same' => 'The new password must be different from the current one.',
    ]
];

$T = $texts[$current_lang];

// --- Traitement du formulaire ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    
    // NOUVEAU: Utilisation de $_SESSION['user_id'] pour une identification fiable
    $user_id = $_SESSION['user_id']; 

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $message = $T['error_empty'];
    } elseif ($new_pass !== $confirm_pass) {
        $message = $T['error_match'];
    } else {
        try {
            // 1. Récupérer le mot de passe actuel hashé de l'utilisateur par ID
            // ASSUMPTION: La colonne d'ID est nommée 'id'. 
            // CORRECTION: La table est bien 't_users'.
            $stmt = $pdo->prepare("SELECT password FROM t_users WHERE id = :user_id");
            
            // Si l'exécution échoue, forcer l'affichage de l'erreur SQL
            if (!$stmt->execute(['user_id' => $user_id])) {
                die("Erreur de récupération de l'utilisateur: " . implode(" ", $stmt->errorInfo()));
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $hashed_current_pass = $user['password'];

                // 2. Vérifier l'ancien mot de passe (point de défaillance le plus probable)
                if (!password_verify($current_pass, $hashed_current_pass)) {
                    $message = $T['error_current_mismatch']; // Ancien mot de passe incorrect
                } 
                // 3. Vérifier que le nouveau mot de passe est différent de l'ancien
                elseif (password_verify($new_pass, $hashed_current_pass)) {
                    $message = $T['error_same'];
                }
                else {
                    // 4. Mettre à jour le mot de passe
                    $new_hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                    
                    // Mise à jour par ID
                    // ASSUMPTION: La colonne d'ID est nommée 'id'.
                    $stmt_update = $pdo->prepare("UPDATE t_users SET password = :new_password WHERE id = :user_id");
                    
                    if ($stmt_update->execute(['new_password' => $new_hashed_pass, 'user_id' => $user_id])) {
                        $message = $T['success'];
                        $is_success = true;
                    } else {
                         // Si ça échoue ici, le problème est dans l'UPDATE (permissions, connexion, etc.)
                        die("Erreur d'exécution de la mise à jour: " . implode(" ", $stmt_update->errorInfo()));
                    }
                }
            } else {
                // L'utilisateur n'est pas trouvé par son ID (cela peut arriver si $_SESSION['user_id'] est vide ou invalide)
                $message = $T['error_update'] . ". L'utilisateur n'a pas été trouvé (ID : " . htmlspecialchars($user_id) . ").";
            }
        } catch (PDOException $e) {
            // Erreur PDO non gérée (souvent problème de connexion ou de syntaxe SQL non capturé par $stmt->execute)
            error_log("Database Error: " . $e->getMessage());
            $message = $T['error_update'] . " Détails: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $T['page_title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f4f7f9; padding: 20px; }
        .card { max-width: 500px; margin: 50px auto; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .input-group-text { cursor: pointer; }
    </style>
</head>
<body>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><?php echo $T['page_title']; ?></h5>
        </div>
        <div class="card-body">
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $is_success ? 'success' : 'danger'; ?>" role="alert">
                    <?php echo nl2br(htmlspecialchars($message)); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                
                <div class="mb-3">
                    <label for="current_password" class="form-label"><?php echo $T['current_password']; ?> <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="current_password" name="current_password" placeholder="<?php echo $T['placeholder_current']; ?>" required>
                        <span class="input-group-text toggle-password" data-target="current_password">
                            <i class="bi bi-eye-slash"></i>
                        </span>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label"><?php echo $T['new_password']; ?> <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="new_password" name="new_password" placeholder="<?php echo $T['placeholder_new']; ?>" required minlength="6">
                        <span class="input-group-text toggle-password" data-target="new_password">
                            <i class="bi bi-eye-slash"></i>
                        </span>
                    </div>
                    <small class="form-text text-muted">Minimum 6 caractères.</small>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label"><?php echo $T['confirm_password']; ?> <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="<?php echo $T['placeholder_confirm']; ?>" required>
                        <span class="input-group-text toggle-password" data-target="confirm_password">
                            <i class="bi bi-eye-slash"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100"><?php echo $T['change_btn']; ?></button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Script pour afficher/masquer le mot de passe
            document.querySelectorAll('.toggle-password').forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const targetInput = document.getElementById(targetId);
                    const icon = this.querySelector('i');

                    if (targetInput.type === 'password') {
                        targetInput.type = 'text';
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    } else {
                        targetInput.type = 'password';
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    }
                });
            });

            // Script pour la validation côté client (comparaison des nouveaux mots de passe)
            const newPassInput = document.getElementById('new_password');
            const confirmPassInput = document.getElementById('confirm_password');

            function validatePasswords() {
                if (newPassInput.value !== confirmPassInput.value) {
                    confirmPassInput.setCustomValidity("Les mots de passe ne correspondent pas."); 
                } else {
                    confirmPassInput.setCustomValidity(''); 
                }
            }

            newPassInput.addEventListener('change', validatePasswords);
            confirmPassInput.addEventListener('keyup', validatePasswords);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
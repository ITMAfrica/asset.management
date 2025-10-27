<?php
session_start();

// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

// --- 1. GESTION DE LA LANGUE ET TRADUCTIONS (MISE À JOUR) ---
$current_lang = $_SESSION['lang'] ?? 'fr';

// Dictionnaire de traduction (MISE À JOUR AVEC ENTITÉ)
$lang_texts = [
    'fr' => [
        // Titres & Boutons
        'title'                      => 'Modifier Utilisateur N°',
        'h2_title'                   => 'Modification de l\'Utilisateur N°',
        'btn_back'                   => 'Retour à la Liste',
        'tab_info'                   => 'Informations Générales',
        'tab_password'               => 'Mot de Passe',
        'btn_update_info'            => 'Mettre à jour les Informations',
        'btn_change_password'        => 'Changer le Mot de Passe',
        
        // Messages d'erreurs/succès (PHP)
        'err_not_found'              => 'Utilisateur non trouvé avec l\'ID {$userId}.',
        'err_db_read'                => 'Erreur de base de données lors de la lecture:',
        'err_db_die'                 => 'Erreur fatale de base de données:', // Pour le die()
        'err_info_required'          => 'Le Nom, l\'Email, le Profil et l\'Entité sont requis.', // MIS À JOUR
        'err_invalid_profile'        => 'Profil invalide sélectionné.',
        'err_invalid_entite'         => 'Entité invalide sélectionnée.', // NOUVEAU
        'err_email_in_use'           => 'Cet email est déjà utilisé par un autre utilisateur.',
        'success_info_update'        => 'Informations générales mises à jour avec succès!',
        'err_db_update_info'         => 'Erreur de base de données lors de la mise à jour :',
        'err_password_fields_required'=> 'Les deux champs de mot de passe sont requis.',
        'err_password_min_length'    => 'Le mot de passe doit contenir au moins 6 caractères.',
        'err_password_match'         => 'La confirmation du mot de passe ne correspond pas.',
        'success_password_update'    => 'Mot de passe mis à jour avec succès!',
        'err_db_update_password'     => 'Erreur de base de données lors de la mise à jour du mot de passe :',
        
        // Labels et feedback (HTML)
        'label_name'                 => 'Nom Complet',
        'feedback_name'              => 'Veuillez entrer le nom complet.',
        'label_email'                => 'Email',
        'feedback_email'             => 'Veuillez entrer une adresse email valide.',
        'label_phone'                => 'Téléphone',
        'label_profile'              => 'Profil',
        'opt_select_profile'         => 'Sélectionner un profil...',
        'feedback_profile'           => 'Veuillez sélectionner le profil.',
        'label_entite'               => 'Entité / Bureau', // NOUVEAU
        'opt_select_entite'          => 'Sélectionner l\'entité...', // NOUVEAU
        'feedback_entite'            => 'Veuillez sélectionner l\'entité.', // NOUVEAU
        'alert_password_warning'     => '**Attention** : La modification du mot de passe est immédiate et irréversible.',
        'label_new_password'         => 'Nouveau Mot de Passe',
        'feedback_new_password'      => 'Veuillez entrer le nouveau mot de passe (min. 6 caractères).',
        'label_confirm_new_password' => 'Confirmer Nouveau Mot de Passe',
        'feedback_password_required' => 'La confirmation du mot de passe est requise.',
        
        // Messages JS
        'js_pass_mismatch'           => 'Les mots de passe ne correspondent pas.',
        'js_pass_mismatch_alert'     => '⚠️ Les mots de passe ne correspondent pas.',

    ],
    'en' => [
        // Titles & Buttons
        'title'                      => 'Edit User N°',
        'h2_title'                   => 'Editing User N°',
        'btn_back'                   => 'Back to List',
        'tab_info'                   => 'General Information',
        'tab_password'               => 'Password',
        'btn_update_info'            => 'Update Information',
        'btn_change_password'        => 'Change Password',
        
        // Error/Success Messages (PHP)
        'err_not_found'              => 'User not found with ID {$userId}.',
        'err_db_read'                => 'Database error during reading:',
        'err_db_die'                 => 'Fatal database error:',
        'err_info_required'          => 'Name, Email, Profile, and Entity are required.', // UPDATED
        'err_invalid_profile'        => 'Invalid profile selected.',
        'err_invalid_entite'         => 'Invalid entity selected.', // NEW
        'err_email_in_use'           => 'This email is already in use by another user.',
        'success_info_update'        => 'General information updated successfully!',
        'err_db_update_info'         => 'Database error during update:',
        'err_password_fields_required'=> 'Both password fields are required.',
        'err_password_min_length'    => 'The password must contain at least 6 characters.',
        'err_password_match'         => 'Password confirmation does not match.',
        'success_password_update'    => 'Password updated successfully!',
        'err_db_update_password'     => 'Database error during password update:',
        
        // Labels and feedback (HTML)
        'label_name'                 => 'Full Name',
        'feedback_name'              => 'Please enter the full name.',
        'label_email'                => 'Email',
        'feedback_email'             => 'Please enter a valid email address.',
        'label_phone'                => 'Phone',
        'label_profile'              => 'Profile',
        'opt_select_profile'         => 'Select a profile...',
        'feedback_profile'           => 'Please select the profile.',
        'label_entite'               => 'Entity / Office', // NEW
        'opt_select_entite'          => 'Select the entity...', // NEW
        'feedback_entite'            => 'Please select the entity.', // NEW
        'alert_password_warning'     => '**Warning**: Password modification is immediate and irreversible.',
        'label_new_password'         => 'New Password',
        'feedback_new_password'      => 'Please enter the new password (min. 6 characters).',
        'label_confirm_new_password' => 'Confirm New Password',
        'feedback_password_required' => 'Password confirmation is required.',
        
        // JS Messages
        'js_pass_mismatch'           => 'Passwords do not match.',
        'js_pass_mismatch_alert'     => '⚠️ Passwords do not match.',
    ]
];

// Fonction d'accès facile aux textes
function __($key) {
    global $lang_texts, $current_lang;
    return $lang_texts[$current_lang][$key] ?? $lang_texts['en'][$key] ?? 'MISSING_KEY';
}
// ---------------------------------------------

require_once 'db_connexion.php'; // Votre fichier de connexion PDO

$message = '';
$profils = ['IT', 'Administration', 'Finance'];

// LISTE DES ENTITÉS PRÉDÉFINIES (AJOUTÉE)
$entites = [
    'EMS',
    'GEO KATANGA',
    'IBS',
    'IFS',
    'ITM ANGOLA LDA',
    'ITM BENIN',
    'ITM BURUNDI',
    'ITM CAMEROUN',
    'ITM CONGO BRAZZAVILLE',
    'ITM COTE D\'IVOIRE',
    'ITM CX',
    'ITM ENVIRONNEMENT',
    'ITM GABON',
    'ITM HOLDING',
    'ITM KATOPE PTY',
    'ITM KENYA LTD',
    'ITM MAINTENANCE',
    'ITM NEXUS',
    'ITM NIGERIA',
    'ITM RWANDA LTD',
    'ITM SARL',
    'ITM SENEGAL',
    'ITM TANZANIA LTD',
    'ITM TOGO',
    'ITM UGANDA LTD',
    'ITM ZAMBIE',
    'JAMON',
    'KUVULU'
]; 


// --- 1. Vérification et Récupération de l'ID ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: liste_utilisateur.php");
    exit();
}
$userId = $_GET['id'];
$user = [];

// --- 2. Récupération des données initiales de l'utilisateur (MISE À JOUR) ---
try {
    // AJOUT DE 'entite' dans la sélection
    $stmt = $pdo->prepare("SELECT id, nom_complet, email, telephone, profil, entite FROM t_users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error_message = str_replace('{$userId}', $userId, __("err_not_found"));
        die("<div class='alert alert-danger'>{$error_message}</div>");
    }
} catch (PDOException $e) {
    die(__("err_db_die") . " " . $e->getMessage());
}


// --- 3. Traitement de la mise à jour (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 3.1 Traitement de la mise à jour des informations générales
    if (isset($_POST['action']) && $_POST['action'] === 'update_info') {
        
        $nom_complet = trim($_POST['nom_complet'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $profil = trim($_POST['profil'] ?? '');
        $entite = trim($_POST['entite'] ?? ''); // NOUVEAU

        // Validation (MISE À JOUR)
        if (empty($nom_complet) || empty($email) || empty($profil) || empty($entite)) {
            $message = "<div class='alert alert-danger'>" . __("err_info_required") . "</div>";
        } elseif (!in_array($profil, $profils)) {
            $message = "<div class='alert alert-danger'>" . __("err_invalid_profile") . "</div>";
        } elseif (!in_array($entite, $entites)) { // Validation de l'entité
            $message = "<div class='alert alert-danger'>" . __("err_invalid_entite") . "</div>";
        } else {
            try {
                // Vérifier si l'email existe déjà pour un AUTRE utilisateur
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM t_users WHERE email = ? AND id != ?");
                $stmtCheck->execute([$email, $userId]);
                
                if ($stmtCheck->fetchColumn() > 0) {
                    $message = "<div class='alert alert-warning'>" . __("err_email_in_use") . "</div>";
                } else {
                    // Mise à jour SQL (AJOUT DE 'entite')
                    $sql = "UPDATE t_users SET nom_complet = ?, email = ?, telephone = ?, profil = ?, entite = ? WHERE id = ?";
                    $stmtUpdate = $pdo->prepare($sql);
                    $stmtUpdate->execute([$nom_complet, $email, $telephone, $profil, $entite, $userId]);

                    $message = "<div class='alert alert-success'>" . __("success_info_update") . "</div>";
                    
                    // Recharger les données pour que le formulaire affiche les nouvelles valeurs
                    $user['nom_complet'] = $nom_complet;
                    $user['email'] = $email;
                    $user['telephone'] = $telephone;
                    $user['profil'] = $profil;
                    $user['entite'] = $entite; // NOUVEAU
                }

            } catch (PDOException $e) {
                $message = "<div class='alert alert-danger'>" . __("err_db_update_info") . " " . $e->getMessage() . "</div>";
            }
        }
    }
    
    // 3.2 Traitement de la mise à jour du mot de passe (pas de changement ici)
    if (isset($_POST['action']) && $_POST['action'] === 'update_password') {
        
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($password) || empty($password_confirm)) {
            $message = "<div class='alert alert-danger'>" . __("err_password_fields_required") . "</div>";
        } elseif (strlen($password) < 6) {
            $message = "<div class='alert alert-danger'>" . __("err_password_min_length") . "</div>";
        } elseif ($password !== $password_confirm) {
            $message = "<div class='alert alert-danger'>" . __("err_password_match") . "</div>";
        } else {
            try {
                // Hachage du mot de passe (INDISPENSABLE pour la sécurité)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $sql = "UPDATE t_users SET password = ? WHERE id = ?";
                $stmtUpdate = $pdo->prepare($sql);
                $stmtUpdate->execute([$hashed_password, $userId]);

                $message = "<div class='alert alert-success'>" . __("success_password_update") . "</div>";

            } catch (PDOException $e) {
                $message = "<div class='alert alert-danger'>" . __("err_db_update_password") . " " . $e->getMessage() . "</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __("title") . htmlspecialchars($userId) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .required::after { content: " *"; color: red; }
    </style>
</head>
<body>

<div class="container mt-5" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-person-fill-gear"></i> <?= __("h2_title") . htmlspecialchars($userId) ?></h2>
        <a href="liste_utilisateur.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> <?= __("btn_back") ?>
        </a>
    </div>

    <?= $message ?>

    <ul class="nav nav-tabs" id="userTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-tab-pane" type="button" role="tab" aria-controls="info-tab-pane" aria-selected="true">
                <i class="bi bi-info-circle"></i> <?= __("tab_info") ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-tab-pane" type="button" role="tab" aria-controls="password-tab-pane" aria-selected="false">
                <i class="bi bi-key"></i> <?= __("tab_password") ?>
            </button>
        </li>
    </ul>

    <div class="tab-content border border-top-0 p-3 bg-white shadow-sm">
        
        <div class="tab-pane fade show active" id="info-tab-pane" role="tabpanel" aria-labelledby="info-tab" tabindex="0">
            <form method="post" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="update_info">
                
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="nom_complet" class="form-label required"><?= __("label_name") ?></label>
                        <input type="text" class="form-control" id="nom_complet" name="nom_complet" value="<?= htmlspecialchars($user['nom_complet'] ?? '') ?>" required>
                        <div class="invalid-feedback"><?= __("feedback_name") ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="email" class="form-label required"><?= __("label_email") ?></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        <div class="invalid-feedback"><?= __("feedback_email") ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="telephone" class="form-label"><?= __("label_phone") ?></label>
                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="profil" class="form-label required"><?= __("label_profile") ?></label>
                        <select class="form-select" id="profil" name="profil" required>
                            <option value=""><?= __("opt_select_profile") ?></option>
                            <?php foreach ($profils as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= (($user['profil'] ?? '') === $p) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= __("feedback_profile") ?></div>
                    </div>

                    <div class="col-md-6"> <label for="entite" class="form-label required"><?= __("label_entite") ?></label>
                        <select class="form-select" id="entite" name="entite" required>
                            <option value=""><?= __("opt_select_entite") ?></option>
                            <?php foreach ($entites as $e): ?>
                                <option value="<?= htmlspecialchars($e) ?>" <?= (($user['entite'] ?? '') === $e) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($e) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= __("feedback_entite") ?></div>
                    </div>
                    </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> <?= __("btn_update_info") ?></button>
                </div>
            </form>
        </div>

        <div class="tab-pane fade" id="password-tab-pane" role="tabpanel" aria-labelledby="password-tab" tabindex="0">
            <div class="alert alert-warning"><i class="bi bi-shield-lock-fill"></i> <?= __("alert_password_warning") ?></div>
            <form method="post" class="needs-validation" novalidate>
                   <input type="hidden" name="action" value="update_password">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="password_upd" class="form-label required"><?= __("label_new_password") ?></label>
                        <input type="password" class="form-control" id="password_upd" name="password" required>
                        <div class="invalid-feedback"><?= __("feedback_new_password") ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="password_confirm_upd" class="form-label required"><?= __("label_confirm_new_password") ?></label>
                        <input type="password" class="form-control" id="password_confirm_upd" name="password_confirm" required>
                        <div class="invalid-feedback" id="password-match-feedback"><?= __("feedback_password_required") ?></div>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-danger"><i class="bi bi-key-fill"></i> <?= __("btn_change_password") ?></button>
                </div>
            </form>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Validation Bootstrap ---
        (function () {
          'use strict'
          const forms = document.querySelectorAll('.needs-validation')
          Array.prototype.slice.call(forms)
            .forEach(function (form) {
              form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                  event.preventDefault()
                  event.stopPropagation()
                }
                form.classList.add('was-validated')
              }, false)
            })
        })();
        
        // --- Validation de correspondance de mot de passe (Onglet Mot de Passe) ---
        const passwordField = document.getElementById('password_upd');
        const passwordConfirmField = document.getElementById('password_confirm_upd');
        const feedbackElement = document.getElementById('password-match-feedback');

        // Messages JS traduits
        const requiredMessage = "<?= htmlspecialchars(__("feedback_password_required")) ?>";
        const mismatchAlert = "<?= htmlspecialchars(__("js_pass_mismatch_alert")) ?>";
        const mismatchMessage = "<?= htmlspecialchars(__("js_pass_mismatch")) ?>";

        function validatePasswordMatch() {
            if (!passwordConfirmField.value) {
                passwordConfirmField.setCustomValidity(requiredMessage);
                feedbackElement.textContent = requiredMessage;
                return;
            }
            if (passwordConfirmField.value !== passwordField.value) {
                passwordConfirmField.setCustomValidity(mismatchMessage);
                feedbackElement.textContent = mismatchAlert;
            } else {
                passwordConfirmField.setCustomValidity(""); // Réinitialiser le message d'erreur
                // Rétablir le message par défaut si la validation est réussie, mais seulement si le champ est vide
                if (!passwordConfirmField.value) {
                       feedbackElement.textContent = requiredMessage;
                }
            }
        }

        if (passwordField && passwordConfirmField) {
            passwordField.addEventListener('input', validatePasswordMatch);
            passwordConfirmField.addEventListener('input', validatePasswordMatch);
            // Déclencher une fois au chargement pour afficher le message requis (si nécessaire)
            validatePasswordMatch();
        }
        
        // --- Gestion des onglets pour l'historique de navigation ---
        const triggerTabList = document.querySelectorAll('#userTabs button')
        triggerTabList.forEach(triggerEl => {
          const tabTrigger = new bootstrap.Tab(triggerEl)

          triggerEl.addEventListener('click', event => {
            event.preventDefault()
            tabTrigger.show()
          })
        })
    });
</script>
</body>
</html>
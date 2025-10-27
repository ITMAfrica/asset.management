<?php
session_start();
// --- 1. GESTION DE LA LANGUE ET TRADUCTIONS ---
$current_lang = $_SESSION['lang'] ?? 'fr';

// Dictionnaire de traduction (MISE À JOUR AVEC ENTITÉ)
$lang_texts = [
    'fr' => [
        'title'                       => 'Ajouter un Utilisateur',
        'h2_title'                    => 'Ajouter un Utilisateur',
        'btn_back'                    => 'Retour à la Liste',
        'err_required_fields'         => 'Tous les champs requis doivent être remplis.',
        'err_password_match'          => 'La confirmation du mot de passe ne correspond pas.',
        'err_invalid_profile'         => 'Profil invalide sélectionné.',
        'err_email_in_use'            => 'Cet email est déjà utilisé.',
        'err_invalid_entite'          => 'Entité invalide sélectionnée.', // AJOUTÉ
        'success_add'                 => 'Utilisateur **{$nom_complet}** ajouté avec succès!',
        'err_db'                      => 'Erreur de base de données :',
        'label_name'                  => 'Nom Complet',
        'feedback_name'               => 'Veuillez entrer le nom complet.',
        'label_email'                 => 'Email',
        'feedback_email'              => 'Veuillez entrer une adresse email valide.',
        'label_phone'                 => 'Téléphone',
        'label_profile'               => 'Profil',
        'opt_select_profile'          => 'Sélectionner un profil...',
        'feedback_profile'            => 'Veuillez sélectionner le profil.',
        'label_entite'                => 'Entité / Bureau', // AJOUTÉ
        'opt_select_entite'           => 'Sélectionner l\'entité...', // AJOUTÉ
        'feedback_entite'             => 'Veuillez sélectionner l\'entité.', // AJOUTÉ
        'label_password'              => 'Mot de Passe',
        'feedback_password'           => 'Veuillez entrer le mot de passe.',
        'label_confirm_password'      => 'Confirmer Mot de Passe',
        'feedback_confirm_password'   => 'Veuillez confirmer le mot de passe.',
        'btn_save'                    => 'Enregistrer l\'Utilisateur',
        'js_pass_mismatch'            => 'Les mots de passe ne correspondent pas.',
    ],
    'en' => [
        'title'                       => 'Add User',
        'h2_title'                    => 'Add User',
        'btn_back'                    => 'Back to List',
        'err_required_fields'         => 'All required fields must be filled.',
        'err_password_match'          => 'Password confirmation does not match.',
        'err_invalid_profile'         => 'Invalid profile selected.',
        'err_email_in_use'            => 'This email is already in use.',
        'err_invalid_entite'          => 'Invalid entity selected.', // ADDED
        'success_add'                 => 'User **{$nom_complet}** added successfully!',
        'err_db'                      => 'Database error:',
        'label_name'                  => 'Full Name',
        'feedback_name'               => 'Please enter the full name.',
        'label_email'                 => 'Email',
        'feedback_email'              => 'Please enter a valid email address.',
        'label_phone'                 => 'Phone',
        'label_profile'               => 'Profile',
        'opt_select_profile'          => 'Select a profile...',
        'feedback_profile'            => 'Please select the profile.',
        'label_entite'                => 'Entity / Office', // ADDED
        'opt_select_entite'           => 'Select the entity...', // ADDED
        'feedback_entite'             => 'Please select the entity.', // ADDED
        'label_password'              => 'Password',
        'feedback_password'           => 'Please enter the password.',
        'label_confirm_password'      => 'Confirm Password',
        'feedback_confirm_password'   => 'Please confirm the password.',
        'btn_save'                    => 'Save User',
        'js_pass_mismatch'            => 'Passwords do not match.',
    ]
];

// Fonction d'accès facile aux textes
function __($key) {
    global $lang_texts, $current_lang;
    return $lang_texts[$current_lang][$key] ?? $lang_texts['en'][$key] ?? 'MISSING_KEY';
}
// ---------------------------------------------

// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

require_once 'db_connexion.php'; // Votre fichier de connexion PDO

$message = '';
$profils = ['IT', 'Administration', 'Finance'];

// LISTE DES ENTITÉS PRÉDÉFINIES (VOTRE LISTE COMPLÈTE)
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom_complet = trim($_POST['nom_complet'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $profil = trim($_POST['profil'] ?? '');
    $entite = trim($_POST['entite'] ?? ''); // NOUVEAU
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // ⭐ AJOUT : Définir le statut du compte par défaut à 'Inactif'
    $compte_statut = 'Inactif';

    // --- Validation ---
    // Les champs requis sont : nom_complet, email, profil, entite, password, password_confirm
    if (empty($nom_complet) || empty($email) || empty($profil) || empty($entite) || empty($password) || empty($password_confirm)) {
        $message = "<div class='alert alert-danger'>" . __("err_required_fields") . "</div>";
    } elseif ($password !== $password_confirm) {
        $message = "<div class='alert alert-danger'>" . __("err_password_match") . "</div>";
    } elseif (!in_array($profil, $profils)) {
        $message = "<div class='alert alert-danger'>" . __("err_invalid_profile") . "</div>";
    } elseif (!in_array($entite, $entites)) { // Validation de l'entité
        $message = "<div class='alert alert-danger'>" . __("err_invalid_entite") . "</div>";
    } else {
        try {
            // Hachage du mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Vérifier si l'email existe déjà
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM t_users WHERE email = ?");
            $stmtCheck->execute([$email]);
            if ($stmtCheck->fetchColumn() > 0) {
                $message = "<div class='alert alert-warning'>" . __("err_email_in_use") . "</div>";
            } else {
                // Insertion dans la base de données (AJOUT DE 'entite' et 'compte_statut')
                $sql = "INSERT INTO t_users (nom_complet, email, password, telephone, profil, entite, compte_statut) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                // ⭐ AJOUT DE $compte_statut à la liste des valeurs à exécuter
                $stmt->execute([$nom_complet, $email, $hashed_password, $telephone, $profil, $entite, $compte_statut]);

                // Message de succès avec interpolation
                $message_raw = str_replace('{$nom_complet}', htmlspecialchars($nom_complet), __("success_add"));
                $message = "<div class='alert alert-success'>{$message_raw}</div>";
                
                // Vider les champs sauf l'email, le profil et l'entité si vous souhaitez réutiliser ces valeurs (ici on vide tout)
                $_POST = [];
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>" . __("err_db") . " " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __("title") ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .required::after { content: " *"; color: red; }
    </style>
</head>
<body>

<div class="container mt-5" style="max-width: 700px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-person-add"></i> <?= __("h2_title") ?></h2>
        <a href="liste_utilisateur.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> <?= __("btn_back") ?>
        </a>
    </div>

    <?= $message ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" class="needs-validation" novalidate>
                
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="nom_complet" class="form-label required"><?= __("label_name") ?></label>
                        <input type="text" class="form-control" id="nom_complet" name="nom_complet" value="<?= htmlspecialchars($_POST['nom_complet'] ?? '') ?>" required>
                        <div class="invalid-feedback"><?= __("feedback_name") ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="email" class="form-label required"><?= __("label_email") ?></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        <div class="invalid-feedback"><?= __("feedback_email") ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="telephone" class="form-label"><?= __("label_phone") ?></label>
                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="profil" class="form-label required"><?= __("label_profile") ?></label>
                        <select class="form-select" id="profil" name="profil" required>
                            <option value=""><?= __("opt_select_profile") ?></option>
                            <?php foreach ($profils as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= (($_POST['profil'] ?? '') === $p) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= __("feedback_profile") ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="entite" class="form-label required"><?= __("label_entite") ?></label>
                        <select class="form-select" id="entite" name="entite" required>
                            <option value=""><?= __("opt_select_entite") ?></option>
                            <?php foreach ($entites as $e): ?>
                                <option value="<?= htmlspecialchars($e) ?>" <?= (($_POST['entite'] ?? '') === $e) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($e) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= __("feedback_entite") ?></div>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label required"><?= __("label_password") ?></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback"><?= __("feedback_password") ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="password_confirm" class="form-label required"><?= __("label_confirm_password") ?></label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        <div class="invalid-feedback"><?= __("feedback_confirm_password") ?></div>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save"></i> <?= __("btn_save") ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Activer la validation Bootstrap
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
})()

document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    const passwordConfirmField = document.getElementById('password_confirm');
    
    // Récupérer le message traduit pour la non-correspondance des mots de passe
    const passwordMismatchMessage = "<?= htmlspecialchars(__("js_pass_mismatch")) ?>";

    function validatePasswordMatch() {
        if (passwordConfirmField.value !== passwordField.value) {
            // Utiliser le message traduit
            passwordConfirmField.setCustomValidity(passwordMismatchMessage);
        } else {
            passwordConfirmField.setCustomValidity(""); // Réinitialiser le message d'erreur
        }
        // Pour que l'erreur s'affiche immédiatement
        passwordConfirmField.reportValidity();
    }

    passwordField.addEventListener('input', validatePasswordMatch); 
    passwordConfirmField.addEventListener('input', validatePasswordMatch);
});
</script>
</body>
</html>
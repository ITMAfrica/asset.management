<?php
session_start();

// Vérifier si l'utilisateur est connecté, sinon le rediriger
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

// Inclure le fichier de connexion à la base de données
require_once 'db_connexion.php'; 

// --- GESTION DE LA LANGUE : Dictionnaire et Sélection ---
// Récupérer la langue depuis la session (définie sur menu.php)
$current_lang = $_SESSION['lang'] ?? 'fr';

// Dictionnaire de traduction
$texts = [
    'fr' => [
        'title' => 'Modifier un Type d\'Équipement',
        'header' => 'Modification du Type d\'Équipement',
        'back_list' => 'Retour à la Liste des Types',
        'label_description' => 'Description du Type d\'Équipement',
        'placeholder_description' => 'Saisir la nouvelle description...',
        'button_save' => 'Enregistrer les Modifications',
        'button_cancel' => 'Annuler',
        'required_feedback' => 'Veuillez saisir une description.',
        'not_found' => 'Type d\'équipement non trouvé avec l\'ID',
        'success' => 'Type d\'équipement mis à jour avec succès !',
        'error_empty' => 'La description ne peut pas être vide.',
        'error_db_update' => 'Erreur lors de la modification : ',
        'error_db_read' => 'Erreur de base de données lors de la lecture initiale : ',
    ],
    'en' => [
        'title' => 'Modify Equipment Type',
        'header' => 'Modification of Equipment Type',
        'back_list' => 'Back to Types List',
        'label_description' => 'Equipment Type Description',
        'placeholder_description' => 'Enter the new description...',
        'button_save' => 'Save Changes',
        'button_cancel' => 'Cancel',
        'required_feedback' => 'Please enter a description.',
        'not_found' => 'Equipment type not found with ID',
        'success' => 'Equipment type updated successfully!',
        'error_empty' => 'The description cannot be empty.',
        'error_db_update' => 'Error during modification: ',
        'error_db_read' => 'Initial database read error: ',
    ]
];

// Sélectionner les textes dans la langue actuelle
$T = $texts[$current_lang];

$message = '';
$deviceId = null;
$currentDescription = '';

// --- 1. Récupération de l'ID à modifier ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Rediriger vers la liste après affichage (à adapter selon votre page liste)
    header("Location: liste_actif.php"); 
    exit();
}
$deviceId = (int)$_GET['id'];

// --- 2. Récupération des données initiales (Lecture) ---
try {
    $stmt = $pdo->prepare("SELECT description FROM t_device WHERE id = ?");
    $stmt->execute([$deviceId]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$device) {
        die("<div class='alert alert-danger'>{$T['not_found']} " . htmlspecialchars($deviceId) . ".</div>");
    }
    $currentDescription = $device['description'];

} catch (PDOException $e) {
    die("<div class='alert alert-danger'>{$T['error_db_read']}" . $e->getMessage() . "</div>");
}

// --- 3. Traitement de la mise à jour (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Vérifier si la description a été soumise
    if (isset($_POST['description']) && !empty(trim($_POST['description']))) {
        
        $newDescription = trim($_POST['description']);
        
        try {
            // Préparer la requête SQL de mise à jour
            $stmt = $pdo->prepare("UPDATE t_device SET description = ? WHERE id = ?");
            
            // Exécuter la requête
            $stmt->execute([$newDescription, $deviceId]);
            
            // Mettre à jour la description affichée immédiatement
            $currentDescription = $newDescription;
            
            $message = "<div class='alert alert-success mt-3' role='alert'>{$T['success']}</div>";
            
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger mt-3' role='alert'>{$T['error_db_update']}" . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger mt-3' role='alert'>{$T['error_empty']}</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $T['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 600px; margin-top: 50px; padding: 30px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        h2 { text-align: center; color: #343a40; margin-bottom: 30px; }
        .form-label { font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><?php echo $T['header']; ?> (ID: <?= htmlspecialchars($deviceId) ?>)</h2>
        <a href="liste_actif.php" class="btn btn-secondary"><?php echo $T['back_list']; ?></a>
    </div>

    <?php echo $message; ?>

    <form action="modifier_actif.php?id=<?= htmlspecialchars($deviceId) ?>" method="post" id="form-modifier-device" class="needs-validation" novalidate>
        <div class="mb-3">
            <label for="description" class="form-label"><?php echo $T['label_description']; ?></label>
            <input type="text" class="form-control" id="description" name="description" 
                   value="<?= htmlspecialchars($currentDescription) ?>" 
                   placeholder="<?php echo $T['placeholder_description']; ?>" required>
            <div class="invalid-feedback">
                <?php echo $T['required_feedback']; ?>
            </div>
        </div>
        
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="liste_actif.php" class="btn btn-secondary"><?php echo $T['button_cancel']; ?></a>
            <button type="submit" class="btn btn-primary"><?php echo $T['button_save']; ?></button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Validation côté client avec JavaScript
    (function () {
      'use strict'
      const form = document.getElementById('form-modifier-device');
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    })();
</script>

</body>
</html>
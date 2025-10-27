<?php
session_start();

// --- GESTION DE LA LANGUE : Dictionnaire et Sélection ---
// Récupérer la langue depuis la session (définie sur menu.php)
$current_lang = $_SESSION['lang'] ?? 'fr';

// Dictionnaire de traduction
$texts = [
    'fr' => [
        'title' => 'Ajout d\'un Actif',
        'header' => 'Ajout d\'un Actif',
        'label_description' => 'Description de l\'Actif',
        'placeholder_description' => 'Saisir la description...',
        'error_empty' => 'La description ne peut pas être vide.',
        'error_validation' => 'Veuillez saisir une description.',
        'success' => 'Actif ajouté avec succès !',
        'error_db' => 'Erreur : ',
        'button_save' => 'Enregistrer',
        'button_cancel' => 'Annuler',
    ],
    'en' => [
        'title' => 'Add Asset',
        'header' => 'Add Asset',
        'label_description' => 'Asset Description',
        'placeholder_description' => 'Enter the description...',
        'error_empty' => 'The description cannot be empty.',
        'error_validation' => 'Please enter a description.',
        'success' => 'Asset added successfully!',
        'error_db' => 'Error: ',
        'button_save' => 'Save',
        'button_cancel' => 'Cancel',
    ]
];

// Sélectionner les textes dans la langue actuelle
$T = $texts[$current_lang];


// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}
// Inclure le fichier de connexion à la base de données
require_once 'db_connexion.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérifier si la description a été soumise
    if (isset($_POST['description']) && !empty(trim($_POST['description']))) {
        // Nettoyer la description
        $description = trim($_POST['description']);
        
        try {
            // Préparer la requête SQL d'insertion
            $stmt = $pdo->prepare("INSERT INTO t_device (description) VALUES (?)");
            
            // Exécuter la requête
            $stmt->execute([$description]);
            
            // Message de succès traduit
            $message = "<div class='alert alert-success mt-3' role='alert'>{$T['success']}</div>";
            
        } catch (PDOException $e) {
            // Message d'erreur DB traduit
            $message = "<div class='alert alert-danger mt-3' role='alert'>{$T['error_db']}" . $e->getMessage() . "</div>";
        }
    } else {
        // Message d'erreur de champ vide traduit
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
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin-top: 50px;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #343a40;
            margin-bottom: 30px;
        }
        .form-label {
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <h2><?php echo $T['header']; ?></h2>
    
    <?php echo $message; ?>

    <form action="" method="post" id="form-ajout-actif">
        <div class="mb-3">
            <label for="description" class="form-label"><?php echo $T['label_description']; ?></label>
            <input type="text" class="form-control" id="description" name="description" required placeholder="<?php echo $T['placeholder_description']; ?>">
            <div class="invalid-feedback">
                <?php echo $T['error_validation']; ?>
            </div>
        </div>
        
        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-primary"><?php echo $T['button_save']; ?></button>
            <a href="liste_actif.php" class="btn btn-secondary"><?php echo $T['button_cancel']; ?></a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Validation côté client avec JavaScript
    (function () {
      'use strict'
      const form = document.getElementById('form-ajout-actif');
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
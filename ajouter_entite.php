<?php

session_start();

// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

// Inclure le fichier de connexion à la base de données
require_once 'db_connexion.php'; 

$message = '';
$description = '';

// --- Traitement du formulaire (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $description = trim($_POST['description'] ?? '');

    if (empty($description)) {
        $message = "<div class='alert alert-warning mt-3' role='alert'>La description ne peut pas être vide.</div>";
    } else {
        try {
            // Requête d'insertion dans la table t_entite (colonnes en minuscule)
            $sql = 'INSERT INTO t_entite (description) VALUES (?)';
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$description]);

            $message = "<div class='alert alert-success mt-3' role='alert'>✅ Entité **\"" . htmlspecialchars($description) . "\"** ajoutée avec succès !</div>";
            $description = ''; // Vider le champ après succès

        } catch (PDOException $e) {
            // Erreur 23505 correspond à la violation de contrainte UNIQUE (description déjà existante)
            if ($e->getCode() === '23505') {
                 $message = "<div class='alert alert-danger mt-3' role='alert'>⚠️ Erreur : Cette description d'entité existe déjà. Veuillez en choisir une autre.</div>";
            } else {
                 $message = "<div class='alert alert-danger mt-3' role='alert'>❌ Erreur d'ajout : " . $e->getMessage() . "</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Entité</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .container { max-width: 600px; margin-top: 50px; }
        .required::after { content: " *"; color: red; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-plus-square-fill me-2 text-success"></i>Ajouter une Entité</h2>
        <a href="liste_entite.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour à la Liste
        </a>
    </div>
    
    <?= $message ?>

    <div class="card shadow-lg">
        <div class="card-header bg-success text-white">
            Informations sur l'Entité
        </div>
        <div class="card-body">
            <form action="ajouter_entite.php" method="post" class="needs-validation" novalidate>
                
                <div class="mb-3">
                    <label for="description" class="form-label required">Description de l'Entité</label>
                    <input type="text" class="form-control" id="description" name="description" 
                           value="<?= htmlspecialchars($description) ?>" required 
                           placeholder="Ex: Direction Générale, Département IT, etc.">
                    <div class="invalid-feedback">Veuillez entrer une description pour l'entité.</div>
                </div>

                <div class="d-grid pt-3">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-save me-2"></i>Enregistrer l'Entité
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Validation Bootstrap
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
</script>
</body>
</html>
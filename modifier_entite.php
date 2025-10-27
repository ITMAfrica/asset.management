<?php
// Inclure le fichier de connexion à la base de données
require_once 'db_connexion.php'; 

$message = '';
$entite = null;
$entiteId = null;

// --- 1. Vérification et Récupération de l'ID ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Si l'ID est manquant, rediriger vers la liste
    header("Location: liste_entite.php");
    exit();
}
$entiteId = (int)$_GET['id'];

// --- 2. Récupération des données initiales (Lecture) ---
try {
    // Récupérer les détails de l'entité (colonnes en minuscule)
    $stmt = $pdo->prepare("SELECT id, description FROM t_entite WHERE id = ?");
    $stmt->execute([$entiteId]);
    $entite = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si l'entité n'est pas trouvée
    if (!$entite) {
        die("<div class='alert alert-danger'>Entité non trouvée avec l'ID {$entiteId}.</div>");
    }

} catch (PDOException $e) {
    die("Erreur de base de données lors de la lecture initiale: " . $e->getMessage());
}

// --- 3. Traitement de la mise à jour (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $newDescription = trim($_POST['description'] ?? '');
    $oldDescription = $entite['description'];

    if (empty($newDescription)) {
        $message = "<div class='alert alert-warning mt-3' role='alert'>La description ne peut pas être vide.</div>";
    } elseif ($newDescription === $oldDescription) {
         $message = "<div class='alert alert-info mt-3' role='alert'>Aucune modification détectée.</div>";
    } else {
        try {
            // Requête de mise à jour (colonnes en minuscule)
            $sql = 'UPDATE t_entite SET description = ? WHERE id = ?';
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$newDescription, $entiteId]);

            // Mettre à jour l'objet $entite pour refléter le changement immédiat sur la page
            $entite['description'] = $newDescription;
            $message = "<div class='alert alert-success mt-3' role='alert'>✅ Entité mise à jour avec succès !</div>";

        } catch (PDOException $e) {
            // Erreur 23505 = Contrainte UNIQUE violée
            if ($e->getCode() === '23505') {
                 $message = "<div class='alert alert-danger mt-3' role='alert'>⚠️ Erreur : Cette description d'entité existe déjà. Veuillez en choisir une autre.</div>";
            } else {
                 $message = "<div class='alert alert-danger mt-3' role='alert'>❌ Erreur de modification : " . $e->getMessage() . "</div>";
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
    <title>Modifier Entité N°<?= htmlspecialchars($entiteId) ?></title>
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
        <h2><i class="bi bi-pencil-square me-2 text-primary"></i>Modifier l'Entité N°<?= htmlspecialchars($entiteId) ?></h2>
        <a href="liste_entite.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour à la Liste
        </a>
    </div>
    
    <?= $message ?>

    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
            Modification de la Description
        </div>
        <div class="card-body">
            <form action="modifier_entite.php?id=<?= htmlspecialchars($entiteId) ?>" method="post" class="needs-validation" novalidate>
                
                <div class="mb-3">
                    <label for="description" class="form-label required">Description de l'Entité</label>
                    <input type="text" class="form-control" id="description" name="description" 
                           value="<?= htmlspecialchars($entite['description'] ?? '') ?>" required>
                    <div class="invalid-feedback">Veuillez entrer une description pour l'entité.</div>
                </div>

                <div class="d-grid pt-3">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-check-circle me-2"></i>Enregistrer les Modifications
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
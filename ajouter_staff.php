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
$nomComplet = '';
$email = '';
$bureau = '';
$entites = [];

// --- 1. Récupérer la liste des Entités (t_entite) ---
try {
    $stmt = $pdo->query("SELECT description FROM t_entite ORDER BY description ASC");
    $entites = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Erreur de récupération des entités: " . $e->getMessage());
}

// --- 2. Traitement du formulaire (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nomComplet = trim($_POST['nom_complet'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bureau = $_POST['bureau'] ?? '';

    if (empty($nomComplet) || empty($email) || empty($bureau)) {
        $message = "<div class='alert alert-warning mt-3' role='alert'>Tous les champs requis doivent être remplis.</div>";
    } else {
        try {
            // Vérification simple du format email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                 $message = "<div class='alert alert-danger mt-3' role='alert'>⚠️ Erreur : L'adresse email n'est pas valide.</div>";
            } else {
                // Requête d'insertion dans la table t_staff (colonnes en minuscule)
                $sql = 'INSERT INTO t_staff (nom_complet, email, bureau) VALUES (?, ?, ?)';
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nomComplet, $email, $bureau]);

                $message = "<div class='alert alert-success mt-3' role='alert'>✅ Personnel **\"" . htmlspecialchars($nomComplet) . "\"** ajouté avec succès !</div>";
                // Réinitialiser les champs après succès
                $nomComplet = '';
                $email = '';
                $bureau = '';
            }

        } catch (PDOException $e) {
            // Erreur 23505 correspond à la violation de contrainte UNIQUE (email déjà existant)
            if ($e->getCode() === '23505') {
                 $message = "<div class='alert alert-danger mt-3' role='alert'>⚠️ Erreur : L'adresse email existe déjà dans le système.</div>";
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
    <title>Ajouter un Membre du Personnel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .container { max-width: 700px; margin-top: 50px; }
        .required::after { content: " *"; color: red; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-person-plus-fill me-2 text-success"></i>Ajouter un Membre du Personnel</h2>
        <a href="liste_staff.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour à la Liste
        </a>
    </div>
    
    <?= $message ?>

    <div class="card shadow-lg">
        <div class="card-header bg-success text-white">
            Informations du Membre
        </div>
        <div class="card-body">
            <form action="ajouter_staff.php" method="post" class="needs-validation" novalidate>
                
                <div class="row g-3">
                    <div class="col-md-12 mb-3">
                        <label for="nom_complet" class="form-label required">Nom Complet</label>
                        <input type="text" class="form-control" id="nom_complet" name="nom_complet" 
                               value="<?= htmlspecialchars($nomComplet) ?>" required>
                        <div class="invalid-feedback">Veuillez entrer le nom complet.</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label required">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($email) ?>" required>
                        <div class="invalid-feedback">Veuillez entrer une adresse email valide.</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="bureau" class="form-label required">Bureau / Entité</label>
                        <select class="form-select" id="bureau" name="bureau" required>
                            <option value="">-- Sélectionner un Bureau --</option>
                            <?php foreach ($entites as $entiteDesc): ?>
                                <option value="<?= htmlspecialchars($entiteDesc) ?>" 
                                    <?= ($bureau === $entiteDesc) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($entiteDesc) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner un bureau.</div>
                    </div>
                </div>

                <div class="d-grid pt-3">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-save me-2"></i>Enregistrer le Membre
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
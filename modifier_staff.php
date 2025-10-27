<?php
// Inclure le fichier de connexion à la base de données
require_once 'db_connexion.php'; 

$message = '';
$membre = null;
$staffId = null;
$entites = [];

// --- 1. Récupération des ID et Entités ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: liste_staff.php");
    exit();
}
$staffId = (int)$_GET['id'];

try {
    // 1.1 Récupérer les détails du membre du personnel
    $stmtStaff = $pdo->prepare("SELECT id_staff, nom_complet, email, bureau FROM t_staff WHERE id_staff = ?");
    $stmtStaff->execute([$staffId]);
    $membre = $stmtStaff->fetch(PDO::FETCH_ASSOC);

    if (!$membre) {
        die("<div class='alert alert-danger'>Membre du personnel non trouvé avec l'ID {$staffId}.</div>");
    }

    // 1.2 Récupérer la liste des Entités (pour le dropdown Bureau)
    $stmtEntites = $pdo->query("SELECT description FROM t_entite ORDER BY description ASC");
    $entites = $stmtEntites->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Erreur de base de données lors de la lecture initiale: " . $e->getMessage());
}

// --- 2. Traitement de la mise à jour (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $newNomComplet = trim($_POST['nom_complet'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newBureau = $_POST['bureau'] ?? '';

    if (empty($newNomComplet) || empty($newEmail) || empty($newBureau)) {
        $message = "<div class='alert alert-warning mt-3' role='alert'>Tous les champs requis doivent être remplis.</div>";
    } else {
        try {
            // Vérification simple du format email
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                 $message = "<div class='alert alert-danger mt-3' role='alert'>⚠️ Erreur : L'adresse email n'est pas valide.</div>";
            } else {
                // Requête de mise à jour (colonnes en minuscule)
                $sql = 'UPDATE t_staff SET nom_complet = ?, email = ?, bureau = ? WHERE id_staff = ?';
                
                $stmt = $pdo->prepare($sql);
                // L'ordre des paramètres est important !
                $stmt->execute([$newNomComplet, $newEmail, $newBureau, $staffId]);

                // Mettre à jour l'objet $membre pour refléter le changement immédiat
                $membre['nom_complet'] = $newNomComplet;
                $membre['email'] = $newEmail;
                $membre['bureau'] = $newBureau;
                
                $message = "<div class='alert alert-success mt-3' role='alert'>✅ Membre du personnel mis à jour avec succès !</div>";
            }

        } catch (PDOException $e) {
            // Erreur 23505 = Contrainte UNIQUE violée (email)
            if ($e->getCode() === '23505') {
                 $message = "<div class='alert alert-danger mt-3' role='alert'>⚠️ Erreur : L'adresse email existe déjà pour un autre membre.</div>";
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
    <title>Modifier Personnel N°<?= htmlspecialchars($staffId) ?></title>
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
        <h2><i class="bi bi-person-fill-gear me-2 text-primary"></i>Modifier le Personnel N°<?= htmlspecialchars($staffId) ?></h2>
        <a href="liste_staff.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour à la Liste
        </a>
    </div>
    
    <?= $message ?>

    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
            Modification des Informations (ID: <?= htmlspecialchars($staffId) ?>)
        </div>
        <div class="card-body">
            <form action="modifier_staff.php?id=<?= htmlspecialchars($staffId) ?>" method="post" class="needs-validation" novalidate>
                
                <div class="row g-3">
                    <div class="col-md-12 mb-3">
                        <label for="nom_complet" class="form-label required">Nom Complet</label>
                        <input type="text" class="form-control" id="nom_complet" name="nom_complet" 
                               value="<?= htmlspecialchars($membre['nom_complet'] ?? '') ?>" required>
                        <div class="invalid-feedback">Veuillez entrer le nom complet.</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label required">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($membre['email'] ?? '') ?>" required>
                        <div class="invalid-feedback">Veuillez entrer une adresse email valide.</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="bureau" class="form-label required">Bureau / Entité</label>
                        <select class="form-select" id="bureau" name="bureau" required>
                            <option value="">-- Sélectionner un Bureau --</option>
                            <?php foreach ($entites as $entiteDesc): ?>
                                <option value="<?= htmlspecialchars($entiteDesc) ?>" 
                                    <?= (isset($membre['bureau']) && $membre['bureau'] === $entiteDesc) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($entiteDesc) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner un bureau.</div>
                    </div>
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
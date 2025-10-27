<?php

session_start();

// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

// Inclure le fichier de connexion à la base de données (Assurez-vous qu'il existe)
require_once 'db_connexion.php'; 

// --- Fonction utilitaire pour construire la requête de pagination (réutilisée de liste_gest_actif.php) ---
function build_pagination_query($filters, $page) {
    $params = $filters;
    $params['page'] = $page;
    $params = array_filter($params, function($value) {
        return $value !== '';
    });
    return http_build_query($params);
}

$deleteError = '';
$message = '';

// --- 1. Logique de suppression ---
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM t_entite WHERE id = ?");
        $stmt->execute([$deleteId]);
        
        $message = "<div class='alert alert-success mt-3' role='alert'>Entité supprimée avec succès.</div>";
        // Redirection après succès pour nettoyer l'URL et éviter la resoumission, 
        // ou simplement mettre à jour le message comme ci-dessus.
        // header("Location: liste_entite.php"); exit();
    } catch (PDOException $e) {
        // En cas d'erreur de clé étrangère (si l'entité est utilisée ailleurs)
        $deleteError = "Erreur lors de la suppression : " . $e->getMessage();
        $message = "<div class='alert alert-danger mt-3' role='alert'>Erreur lors de la suppression : " . htmlspecialchars($deleteError) . "</div>";
    }
}

// --- 2. Variables de filtrage et de pagination ---
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Filtre par le champ 'description'
$filters = [
    'search' => $_GET['search'] ?? '',
];

$whereClauses = [];
$params = [];

// Filtre par recherche sur la description (PostgreSQL ILIKE pour non sensible à la casse)
if (!empty($filters['search'])) {
    $search = '%' . $filters['search'] . '%';
    $whereClauses[] = "description ILIKE ?";
    $params[] = $search;
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = " WHERE " . implode(" AND ", $whereClauses);
}

// --- 3. Exécution des requêtes ---
$entites = [];
$totalPages = 0;

try {
    // 3.1 Compter le nombre total d'entités pour la pagination
    $countSql = "SELECT COUNT(*) FROM t_entite" . $whereSql;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = $countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $itemsPerPage);

    // 3.2 Récupérer la liste des entités pour la page courante
    $listSql = "
        SELECT id, description
        FROM t_entite
        {$whereSql}
        ORDER BY description ASC
        LIMIT :limit OFFSET :offset";
    
    $stmtList = $pdo->prepare($listSql);
    
    // Ajout des paramètres de filtre
    foreach ($params as $key => $value) {
        $stmtList->bindValue($key + 1, $value);
    }
    $stmtList->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmtList->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmtList->execute();
    $entites = $stmtList->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Erreur de base de données : " . $e->getMessage();
    $message = "<div class='alert alert-danger mt-3' role='alert'>Erreur lors de la récupération des données : " . htmlspecialchars($error) . "</div>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Entités</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .container { max-width: 900px; margin-top: 30px; margin-bottom: 50px; }
        .table thead th { 
            position: sticky; 
            top: 0; 
            background-color: #343a40; 
            color: white; 
            z-index: 10; 
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Liste des Entités (<span class="badge bg-primary"><?= $totalItems ?></span>)</h2>
        
        <div class="btn-group" role="group">
            <a href="ajouter_entite.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Ajouter une Entité
            </a>
            <button type="button" class="btn btn-info text-white" onclick="alert('L\'implémentation de l\'exportation Excel doit être faite dans un fichier séparé.')">
                <i class="bi bi-file-earmark-spreadsheet"></i> Importer sur Excel
            </button>
        </div>
    </div>

    <?= $message ?>

    <div class="card mb-4 shadow-sm border-primary">
        <div class="card-body p-3">
            <form method="get" class="row g-3 align-items-center">
                <div class="col-md-9">
                    <label for="search" class="form-label small">Filtrer par Description</label>
                    <input type="text" class="form-control form-control-sm" id="search" name="search" 
                           value="<?= htmlspecialchars($filters['search']) ?>"
                           placeholder="Rechercher une entité...">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100 mt-md-3">
                        <i class="bi bi-search"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-lg">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col" class="text-center" style="width: 10%;">ID</th>
                            <th scope="col" style="width: 60%;">Description</th>
                            <th scope="col" class="text-center" style="width: 30%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($entites)): ?>
                            <?php foreach ($entites as $entite): ?>
                                <tr>
                                    <td class="text-center small text-muted"><?= htmlspecialchars($entite['id'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($entite['description'] ?? '') ?></td>
                                    <td class="text-center">
                                        <a href="modifier_entite.php?id=<?= htmlspecialchars($entite['id'] ?? '') ?>" class="btn btn-info btn-sm me-1" title="Modifier">
                                            <i class="bi bi-pencil"></i> Modifier
                                        </a>
                                        <a href="?delete_id=<?= htmlspecialchars($entite['id'] ?? '') ?>&page=<?= $currentPage ?>&search=<?= urlencode($filters['search']) ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette entité (<?= htmlspecialchars($entite['description'] ?? '') ?>) ?');" title="Supprimer">
                                            <i class="bi bi-trash"></i> Supprimer
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-4">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Aucune entité trouvée.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <nav aria-label="Page navigation example" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($totalPages > 1): ?>
                <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= build_pagination_query($filters, max(1, $currentPage - 1)) ?>">Précédent</a>
                </li>
                <?php 
                // Affichage d'un bloc de pagination simple
                for ($i = 1; $i <= $totalPages; $i++): 
                ?>
                    <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= build_pagination_query($filters, $i) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= build_pagination_query($filters, min($totalPages, $currentPage + 1)) ?>">Suivant</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
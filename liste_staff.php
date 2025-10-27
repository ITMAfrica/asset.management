<?php
session_start();

// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}


// Inclure le fichier de connexion à la base de données
require_once 'db_connexion.php'; 

// --- Fonction utilitaire pour construire la requête de pagination ---
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
        $stmt = $pdo->prepare("DELETE FROM t_staff WHERE id_staff = ?");
        $stmt->execute([$deleteId]);
        
        $message = "<div class='alert alert-success mt-3' role='alert'>Membre du personnel supprimé avec succès.</div>";
        // Redirection pour nettoyer l'URL après suppression
        header("Location: liste_staff.php");
        exit();
    } catch (PDOException $e) {
        $deleteError = "Erreur lors de la suppression : " . $e->getMessage();
        $message = "<div class='alert alert-danger mt-3' role='alert'>Erreur lors de la suppression : " . htmlspecialchars($deleteError) . "</div>";
    }
}

// --- 2. Variables de filtrage et de pagination ---
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

$filters = [
    'search' => $_GET['search'] ?? '',
];

$whereClauses = [];
$params = [];

// Filtre par recherche globale sur nom_complet, email ou bureau (ILIKE pour PostgreSQL)
if (!empty($filters['search'])) {
    $search = '%' . $filters['search'] . '%';
    $whereClauses[] = "(nom_complet ILIKE ? OR email ILIKE ? OR bureau ILIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = " WHERE " . implode(" AND ", $whereClauses);
}

// --- 3. Exécution des requêtes ---
$staff = [];
$totalPages = 0;

try {
    // 3.1 Compter le nombre total de membres pour la pagination
    $countSql = "SELECT COUNT(*) FROM t_staff" . $whereSql;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = $countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $itemsPerPage);

    // 3.2 Récupérer la liste du personnel pour la page courante
    $listSql = "
        SELECT id_staff, nom_complet, email, bureau
        FROM t_staff
        {$whereSql}
        ORDER BY nom_complet ASC
        LIMIT :limit OFFSET :offset";
    
    $stmtList = $pdo->prepare($listSql);
    
    // Ajout des paramètres de filtre
    foreach ($params as $key => $value) {
        $stmtList->bindValue($key + 1, $value);
    }
    $stmtList->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmtList->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmtList->execute();
    $staff = $stmtList->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Liste du Personnel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .container { max-width: 1000px; margin-top: 30px; margin-bottom: 50px; }
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
        <h2>Liste du Personnel (<span class="badge bg-primary"><?= $totalItems ?></span>)</h2>
        
        <div class="btn-group" role="group">
            <a href="ajouter_staff.php" class="btn btn-success">
                <i class="bi bi-person-plus"></i> Ajouter un Membre
            </a>
            <button type="button" class="btn btn-info text-white" onclick="alert('L\'implémentation de l\'exportation Excel doit être faite côté serveur.')">
                <i class="bi bi-file-earmark-spreadsheet"></i> Exporter sur Excel
            </button>
        </div>
    </div>

    <?= $message ?>

    <div class="card mb-4 shadow-sm border-primary">
        <div class="card-body p-3">
            <form method="get" class="row g-3 align-items-center">
                <div class="col-md-10">
                    <label for="search" class="form-label small">Filtrer par Nom, Email ou Bureau</label>
                    <input type="text" class="form-control form-control-sm" id="search" name="search" 
                           value="<?= htmlspecialchars($filters['search']) ?>"
                           placeholder="Rechercher...">
                </div>
                <div class="col-md-2">
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
                            <th scope="col" class="text-center" style="width: 5%;">ID</th>
                            <th scope="col" style="width: 30%;">Nom Complet</th>
                            <th scope="col" style="width: 35%;">Email</th>
                            <th scope="col" style="width: 15%;">Bureau</th>
                            <th scope="col" class="text-center" style="width: 15%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($staff)): ?>
                            <?php foreach ($staff as $membre): ?>
                                <tr>
                                    <td class="text-center small text-muted"><?= htmlspecialchars($membre['id_staff'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($membre['nom_complet'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($membre['email'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($membre['bureau'] ?? '') ?></td>
                                    <td class="text-center">
                                        <a href="modifier_staff.php?id=<?= htmlspecialchars($membre['id_staff'] ?? '') ?>" class="btn btn-info btn-sm me-1" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="?delete_id=<?= htmlspecialchars($membre['id_staff'] ?? '') ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce membre (<?= htmlspecialchars($membre['nom_complet'] ?? '') ?>) ?');" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Aucun membre du personnel trouvé correspondant aux filtres.
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
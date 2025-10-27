<?php

session_start();

// --- GESTION DE LA LANGUE : Dictionnaire et Sélection ---
// Récupérer la langue depuis la session (définie sur menu.php)
$current_lang = $_SESSION['lang'] ?? 'fr';

// Dictionnaire de traduction
$texts = [
    'fr' => [
        'title' => 'Liste des Actifs',
        'header' => 'Liste des Equipements', // Note: Le titre original utilise 'Actifs', le h2 utilise 'Equipements'. J'ai conservé 'Equipements' ici pour le h2.
        'add_button' => 'Ajouter un nouvel équipement',
        'delete_confirm' => 'Êtes-vous sûr de vouloir supprimer cet actif ?',
        'delete_error' => 'Erreur lors de la suppression : ',
        'search_placeholder' => 'Filtrer par description...',
        'search_button' => 'Rechercher',
        'reset_button' => 'Réinitialiser',
        'table_id' => 'Id',
        'table_description' => 'Description',
        'table_action' => 'Action',
        'action_modify' => 'Modifier',
        'action_delete' => 'Supprimer',
        'no_records' => 'Aucun actif trouvé.',
        'pagination_prev' => 'Précédent',
        'pagination_next' => 'Suivant',
    ],
    'en' => [
        'title' => 'Assets List',
        'header' => 'Equipment List',
        'add_button' => 'Add New Equipment',
        'delete_confirm' => 'Are you sure you want to delete this asset?',
        'delete_error' => 'Error during deletion: ',
        'search_placeholder' => 'Filter by description...',
        'search_button' => 'Search',
        'reset_button' => 'Reset',
        'table_id' => 'Id',
        'table_description' => 'Description',
        'table_action' => 'Action',
        'action_modify' => 'Modify',
        'action_delete' => 'Delete',
        'no_records' => 'No assets found.',
        'pagination_prev' => 'Previous',
        'pagination_next' => 'Next',
    ]
];

// Sélectionner les textes dans la langue actuelle
$T = $texts[$current_lang];


// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

require_once 'db_connexion.php';

// --- Logique de suppression ---
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM t_device WHERE id = ?");
        $stmt->execute([$deleteId]);
        // Conserver le paramètre de langue lors de la redirection
        header("Location: liste_actif.php");
        exit();
    } catch (PDOException $e) {
        // Utiliser la traduction pour le message d'erreur
        $deleteError = $T['delete_error'] . $e->getMessage();
    }
}

// --- Logique de filtrage ---
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';
$params = [];
if (!empty($searchQuery)) {
    $whereClause = " WHERE description ILIKE ?";
    $params[] = '%' . $searchQuery . '%';
}

// --- Logique de pagination ---
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

$countSql = "SELECT COUNT(*) FROM t_device" . $whereClause;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// --- Requête SQL pour récupérer les données ---
$sql = "SELECT id, description FROM t_device" . $whereClause . " ORDER BY id ASC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$params[] = $itemsPerPage;
$params[] = $offset;
$stmt->execute($params);
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $T['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> .container { margin-top: 30px; } </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><?php echo $T['header']; ?></h2>
        <a href="ajout_actif.php" class="btn btn-primary">
            <?php echo $T['add_button']; ?>
        </a>
    </div>
    <?php if (isset($deleteError)): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($deleteError) ?></div>
    <?php endif; ?>
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="<?php echo $T['search_placeholder']; ?>" name="search" value="<?= htmlspecialchars($searchQuery) ?>">
            <button class="btn btn-outline-secondary" type="submit"><?php echo $T['search_button']; ?></button>
            <a href="liste_actif.php" class="btn btn-outline-danger"><?php echo $T['reset_button']; ?></a>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th><?php echo $T['table_id']; ?></th>
                    <th><?php echo $T['table_description']; ?></th>
                    <th><?php echo $T['table_action']; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($devices): ?>
                    <?php foreach ($devices as $device): ?>
                        <tr>
                            <td><?= htmlspecialchars($device['id']) ?></td>
                            <td><?= htmlspecialchars($device['description']) ?></td>
                            <td>
                                <a href="modifier_actif.php?id=<?= htmlspecialchars($device['id']) ?>" class="btn btn-info btn-sm"><?php echo $T['action_modify']; ?></a>
                                <a href="?delete_id=<?= htmlspecialchars($device['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('<?php echo htmlspecialchars($T['delete_confirm']); ?>');"><?php echo $T['action_delete']; ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center"><?php echo $T['no_records']; ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <nav aria-label="Page navigation example">
        <ul class="pagination justify-content-center">
            <?php if ($totalPages > 1): ?>
                <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= max(1, $currentPage - 1) ?>&search=<?= urlencode($searchQuery) ?>"><?php echo $T['pagination_prev']; ?></a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchQuery) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= min($totalPages, $currentPage + 1) ?>&search=<?= urlencode($searchQuery) ?>"><?php echo $T['pagination_next']; ?></a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
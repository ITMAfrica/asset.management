<?php

session_start();

// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}
// Inclure le fichier de connexion
require_once 'db_connexion.php'; 

// --- Fonction utilitaire pour les badges de statut (Amélioration du design) ---
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'En service': return 'bg-success';
        case 'En stock': return 'bg-info';
        case 'En réparation': return 'bg-warning text-dark';
        case 'Volé':
        case 'Déclassé':
        case 'Hors service': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

// --- Fonction utilitaire pour construire la requête de pagination ---
function build_pagination_query($filters, $page) {
    $params = $filters;
    $params['page'] = $page;
    // Supprimer les filtres vides pour garder l'URL propre
    $params = array_filter($params, function($value) {
        return $value !== '';
    });
    return http_build_query($params);
}


// --- 1. Logique de suppression ---
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    try {
        // Suppression des entrées d'historique liées (colonnes en minuscule)
        $stmtHist = $pdo->prepare("DELETE FROM t_historique_actif WHERE id_actif_original = ?");
        $stmtHist->execute([$deleteId]);
        
        // Suppression de l'actif principal (colonne en minuscule)
        $stmt = $pdo->prepare("DELETE FROM t_actif WHERE id_actif = ?");
        $stmt->execute([$deleteId]);
        
        // Redirection pour éviter la resoumission du formulaire
        header("Location: liste_gest_actif.php");
        exit();
    } catch (PDOException $e) {
        $deleteError = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// --- 2. Variables de filtrage et de pagination ---
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Variables pour les filtres (récupération des valeurs GET)
$filters = [
    'statut' => $_GET['statut'] ?? '',
    'type' => $_GET['type'] ?? '',
    'search' => $_GET['search'] ?? '',
];

$whereClauses = [];
$params = [];

// Filtre par statut
if (!empty($filters['statut'])) {
    $whereClauses[] = "statut = ?";
    $params[] = $filters['statut'];
}

// Filtre par type d'équipement
if (!empty($filters['type'])) {
    $whereClauses[] = "type_equipement = ?";
    $params[] = $filters['type'];
}

// Filtre par recherche globale (toutes les colonnes sont en minuscules)
if (!empty($filters['search'])) {
    $search = '%' . $filters['search'] . '%';
    $whereClauses[] = "(
        numero_serie ILIKE ? OR 
        numero_inventaire ILIKE ? OR 
        nom_equipement ILIKE ? OR 
        affecter_a ILIKE ? OR 
        bureau ILIKE ?
    )";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search; 
    $params[] = $search;
    $params[] = $search;
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = " WHERE " . implode(" AND ", $whereClauses);
}

// --- 3. Exécution des requêtes ---
$actifs = [];
$totalPages = 0;
$deviceTypes = [];

try {
    // 3.1 Compter le nombre total d'actifs pour la pagination
    $countSql = "SELECT COUNT(*) FROM t_actif" . $whereSql;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = $countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $itemsPerPage);

    // 3.2 Récupérer la liste des actifs pour la page courante (colonnes en minuscule)
    $listSql = "
        SELECT 
            id_actif, type_equipement, nom_equipement, specification, numero_serie, numero_inventaire, 
            adresse_mac, date_achat, duree_amortissement, statut, date_service, bureau, 
            affecter_a, commentaire, creer_par
        FROM t_actif
        {$whereSql}
        ORDER BY numero_inventaire ASC
        LIMIT :limit OFFSET :offset";
    
    $stmtList = $pdo->prepare($listSql);
    
    // Ajout des paramètres de filtre
    foreach ($params as $key => $value) {
        $stmtList->bindValue($key + 1, $value);
    }
    $stmtList->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmtList->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmtList->execute();
    $actifs = $stmtList->fetchAll(PDO::FETCH_ASSOC);

    // 3.3 Récupérer tous les types d'équipement pour le filtre dropdown
    $stmtTypes = $pdo->query("SELECT description FROM t_device ORDER BY description ASC");
    $deviceTypes = $stmtTypes->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $error = "Erreur de base de données : " . $e->getMessage();
}

// --- 4. Définir les options de statut pour les filtres ---
$statutOptions = [
    'En stock', 'En service', 'Hors service', 'En réparation', 'Volé', 'Déclassé'
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste et Gestion des Actifs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .container { max-width: 1600px; margin-top: 30px; margin-bottom: 50px; }
        
        /* CSS Optimisé pour le "sticky header" et la visibilité */
        .table-responsive { 
            max-height: 80vh; /* Augmente la hauteur max pour la table */
            overflow: auto; /* Permet le défilement dans les deux directions */
        }
        /* Assure que l'en-tête reste en haut et est visible sur un fond blanc */
        .table thead th { 
            position: sticky; 
            top: 0; 
            background-color: #343a40; /* Couleur d'arrière-plan de l'en-tête */
            color: white; 
            z-index: 10; 
            white-space: nowrap; /* Empêche les titres de revenir à la ligne */
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Inventaire des Actifs (<span class="badge bg-primary"><?= $totalItems ?></span>)</h2>
        <a href="ajout_gest_actif.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Ajouter un Actif
        </a>
    </div>

    <?php if (isset($deleteError)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-octagon-fill me-2"></i><?= htmlspecialchars($deleteError) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-octagon-fill me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm border-primary">
        <div class="card-header bg-primary text-white"><i class="bi bi-funnel-fill me-2"></i>Filtres et Recherche Avancée</div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="statut" class="form-label small">Statut</label>
                    <select class="form-select form-select-sm" id="statut" name="statut">
                        <option value="">-- Tous les statuts --</option>
                        <?php foreach ($statutOptions as $option): ?>
                            <option value="<?= htmlspecialchars($option) ?>" 
                                <?= ($filters['statut'] === $option) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="type" class="form-label small">Type d'Équipement</label>
                    <select class="form-select form-select-sm" id="type" name="type">
                        <option value="">-- Tous les types --</option>
                        <?php foreach ($deviceTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" 
                                <?= ($filters['type'] === $type) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="search" class="form-label small">Recherche Globale</label>
                    <input type="text" class="form-control form-control-sm" id="search" name="search" 
                           value="<?= htmlspecialchars($filters['search']) ?>"
                           placeholder="N° Série, Inventaire, Nom, Affecté à, Bureau...">
                </div>

                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
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
                            <th scope="col" class="text-center">ID</th>
                            <th scope="col">N° Inventaire</th>
                            <th scope="col">Type</th>
                            <th scope="col">Nom Équipement</th> 
                            <th scope="col">N° Série</th>
                            <th scope="col" class="text-center">Statut</th>
                            <th scope="col">Affecté à</th>
                            <th scope="col">Bureau</th>
                            <th scope="col">Date Service</th>
                            <th scope="col">Date Achat</th>
                            <th scope="col" class="text-center">Amort. (ans)</th>
                            <th scope="col">Commentaire</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($actifs)): ?>
                            <?php foreach ($actifs as $actif): ?>
                                <tr>
                                    <td class="text-center small text-muted"><?= htmlspecialchars($actif['id_actif'] ?? '') ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($actif['numero_inventaire'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($actif['type_equipement'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($actif['nom_equipement'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($actif['numero_serie'] ?? '') ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= getStatusBadgeClass($actif['statut'] ?? '') ?>">
                                            <?= htmlspecialchars($actif['statut'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($actif['affecter_a'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($actif['bureau'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($actif['date_service'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($actif['date_achat'] ?? '') ?></td>
                                    <td class="text-center"><?= htmlspecialchars($actif['duree_amortissement'] ?? '') ?></td>
                                    <td>
                                        <span title="<?= htmlspecialchars($actif['commentaire'] ?? '') ?>">
                                            <?= htmlspecialchars(substr($actif['commentaire'] ?? '', 0, 30)) ?><?= (strlen($actif['commentaire'] ?? '') > 30) ? '...' : '' ?>
                                        </span>
                                    </td>
                                    <td class="text-center" style="min-width: 100px;">
                                        <a href="modifier_gest_actif.php?id=<?= htmlspecialchars($actif['id_actif'] ?? '') ?>" class="btn btn-info btn-sm me-1" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="?delete_id=<?= htmlspecialchars($actif['id_actif'] ?? '') ?>" class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet actif (ID: <?= htmlspecialchars($actif['id_actif'] ?? '') ?>) et tout son historique ?');" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="13" class="text-center py-4">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Aucun actif trouvé correspondant aux filtres.
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
                // Affichage d'un bloc de pagination intelligent
                $range = 2; // Nombre de pages à afficher de chaque côté de la page courante
                for ($i = 1; $i <= $totalPages; $i++): 
                    if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $range && $i <= $currentPage + $range)):
                ?>
                    <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= build_pagination_query($filters, $i) ?>"><?= $i ?></a>
                    </li>
                <?php 
                    elseif ($i == $currentPage - $range - 1 || $i == $currentPage + $range + 1):
                ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; endfor; ?>
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
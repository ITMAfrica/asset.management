<?php

session_start();

// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}
// Récupérer le profil de l'utilisateur connecté (Doit être défini dans la session lors de la connexion)
$user_profil = $_SESSION['user_profil'] ?? '';
// NOUVEAU : Récupérer le bureau de l'utilisateur connecté pour la restriction
$user_bureau = $_SESSION['user_bureau'] ?? '';
// Récupérer la langue depuis la session
$current_lang = $_SESSION['lang'] ?? 'fr';

// Inclure le fichier de connexion
require_once 'db_connexion.php'; 

// --- GESTION DE LA LANGUE : Dictionnaire et Sélection ---
$texts = [
    'fr' => [
        'title' => 'Liste et Gestion des Actifs',
        'header_inventory' => 'Inventaire des Actifs',
        'add_asset' => 'Ajouter un Actif',
        'import_asset' => 'Importer Actif', // NOUVEAU
        'delete_error_db' => 'Erreur lors de la suppression : ',
        'delete_error_access' => 'Accès refusé : Votre profil (',
        'delete_error_access_end' => ') n\'est pas autorisé à supprimer des actifs.',
        'db_error' => 'Erreur de base de données : ',
        // NOUVEAU MESSAGE D'ERREUR DE SÉCURITÉ
        'err_bureau_missing' => 'Accès refusé. Votre profil est restreint mais aucune entité/bureau n\'a été définie dans votre session.',
        
        // Filtres
        'filter_header' => 'Filtres et Recherche Avancée',
        'label_status' => 'Statut',
        'option_all_status' => '-- Tous les statuts --',
        'label_type' => 'Type d\'Équipement',
        'option_all_types' => '-- Tous les types --',
        'label_search' => 'Recherche Globale',
        'placeholder_search' => 'N° Série, Inventaire, Nom, Affecté à, Bureau...',
        'button_filter' => 'Filtrer',
        'no_asset_found' => 'Aucun actif trouvé correspondant aux filtres.',
        
        // Tableau (En-têtes)
        'th_id' => 'ID',
        'th_inventory' => 'N° Inventaire',
        'th_type' => 'Type',
        'th_name' => 'Nom Équipement',
        'th_serial' => 'N° Série',
        'th_status' => 'Statut',
        'th_affected_to' => 'Affecté à',
        'th_office' => 'Bureau',
        'th_date_service' => 'Date Service',
        'th_date_purchase' => 'Date Achat',
        'th_amort_years' => 'Amort. (ans)',
        'th_comment' => 'Commentaire',
        'th_actions' => 'Actions',
        
        // Actions
        'action_modify' => 'Modifier',
        'action_delete' => 'Supprimer',
        'confirm_delete' => 'Êtes-vous sûr de vouloir supprimer cet actif (ID: ',
        'confirm_delete_end' => ') et tout son historique ?',
        
        // Pagination
        'pagination_previous' => 'Précédent',
        'pagination_next' => 'Suivant',

        // Statuts (La clé est le nom interne/DB, la valeur est l'affichage FR)
        'status_service_db' => 'En service',
        'status_stock_db' => 'En stock',
        'status_repair_db' => 'En réparation',
        'status_stolen_db' => 'Volé',
        'status_decommissioned_db' => 'Déclassé',
        'status_out_of_service_db' => 'Hors service',
    ],
    'en' => [
        'title' => 'Asset List and Management',
        'header_inventory' => 'Asset Inventory',
        'add_asset' => 'Add Asset',
        'import_asset' => 'Import Asset', // NOUVEAU
        'delete_error_db' => 'Error during deletion: ',
        'delete_error_access' => 'Access denied: Your profile (',
        'delete_error_access_end' => ') is not authorized to delete assets.',
        'db_error' => 'Database error: ',
        // NOUVEAU MESSAGE D'ERREUR DE SÉCURITÉ
        'err_bureau_missing' => 'Access denied. Your profile is restricted but no entity/office was defined in your session.',
        
        // Filters
        'filter_header' => 'Advanced Filters and Search',
        'label_status' => 'Status',
        'option_all_status' => '-- All Statuses --',
        'label_type' => 'Equipment Type',
        'option_all_types' => '-- All Types --',
        'label_search' => 'Global Search',
        'placeholder_search' => 'Serial N°, Inventory N°, Name, Assigned to, Office...',
        'button_filter' => 'Filter',
        'no_asset_found' => 'No assets found matching the filters.',
        
        // Tableau (En-têtes)
        'th_id' => 'ID',
        'th_inventory' => 'Inventory N°',
        'th_type' => 'Type',
        'th_name' => 'Equipment Name',
        'th_serial' => 'Serial N°',
        'th_status' => 'Status',
        'th_affected_to' => 'Assigned to',
        'th_office' => 'Office',
        'th_date_service' => 'Service Date',
        'th_date_purchase' => 'Purchase Date',
        'th_amort_years' => 'Amort. (yrs)',
        'th_comment' => 'Comment',
        'th_actions' => 'Actions',
        
        // Actions
        'action_modify' => 'Modify',
        'action_delete' => 'Delete',
        'confirm_delete' => 'Are you sure you want to delete this asset (ID: ',
        'confirm_delete_end' => ') and all its history?',
        
        // Pagination
        'pagination_previous' => 'Previous',
        'pagination_next' => 'Next',

        // Statuts (La clé est le nom interne/DB, la valeur est l'affichage EN)
        'status_service_db' => 'In service',
        'status_stock_db' => 'In stock',
        'status_repair_db' => 'In repair',
        'status_stolen_db' => 'Stolen',
        'status_decommissioned_db' => 'Decommissioned',
        'status_out_of_service_db' => 'Out of service',
    ]
];

// Sélectionner les textes dans la langue actuelle
$T = $texts[$current_lang];

// --- Fonction utilitaire pour traduire les noms de statut DB pour l'affichage ---
function translateStatusForDisplay($db_status, $T) {
    switch ($db_status) {
        // Les clés du case DOIVENT rester en français (noms stockés en DB)
        case 'En service': return $T['status_service_db'];
        case 'En stock': return $T['status_stock_db'];
        case 'En réparation': return $T['status_repair_db'];
        case 'Volé': return $T['status_stolen_db'];
        case 'Déclassé': return $T['status_decommissioned_db'];
        case 'Hors service': return $T['status_out_of_service_db'];
        default: return $db_status;
    }
}

// --- Fonction utilitaire pour les badges de statut (Amélioration du design) ---
// La fonction utilise toujours les noms de statut DB (FR) pour déterminer la classe CSS.
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


// --- 1. Logique de suppression (Sécurisé par profil) ---
$deleteError = null;
if (isset($_GET['delete_id'])) {
    // VÉRIFICATION D'AUTORISATION : Seul le profil IT est autorisé à supprimer.
    if ($user_profil === 'IT') {
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
            // Traduction du message d'erreur
            $deleteError = $T['delete_error_db'] . $e->getMessage();
        }
    } else {
        // Traduction du message d'erreur d'accès
        $deleteError = $T['delete_error_access'] . htmlspecialchars($user_profil) . $T['delete_error_access_end'];
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

// =========================================================================
// NOUVELLE LOGIQUE DE RESTRICTION PAR BUREAU (ENTITÉ)
// =========================================================================
if ($user_profil !== 'IT') {
    if (!empty($user_bureau)) {
        // Restriction : la colonne 'bureau' de t_actif doit correspondre au bureau de l'utilisateur
        $whereClauses[] = "bureau = ?";
        $params[] = $user_bureau;
    } else {
        // Mesure de sécurité : Si le profil est restreint mais le bureau non défini,
        // on bloque l'affichage des données en forçant une condition fausse.
        $whereClauses[] = "1 = 0"; 
        $error = $T['err_bureau_missing']; // Afficher un message d'erreur spécifique
    }
}
// =========================================================================
// FIN DE LA LOGIQUE DE RESTRICTION PAR BUREAU
// =========================================================================


// Filtre par statut (La valeur du filtre est le nom français stocké en DB)
if (!empty($filters['statut'])) {
    $whereClauses[] = "statut = ?";
    $params[] = $filters['statut'];
}

// Filtre par type d'équipement
if (!empty($filters['type'])) {
    $whereClauses[] = "type_equipement = ?";
    $params[] = $filters['type'];
}

// Filtre par recherche globale
if (!empty($filters['search'])) {
    $search = '%' . $filters['search'] . '%';
    $whereClauses[] = "(
        numero_serie ILIKE ? OR 
        numero_inventaire ILIKE ? OR 
        nom_equipement ILIKE ? OR 
        affecter_a ILIKE ? OR 
        bureau ILIKE ?
    )";
    // Ajout des paramètres pour la recherche globale
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
// $error est défini par la restriction de bureau ci-dessus, ou null

try {
    // 3.1 Compter le nombre total d'actifs pour la pagination
    $countSql = "SELECT COUNT(*) FROM t_actif" . $whereSql;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = $countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $itemsPerPage);

    // 3.2 Récupérer la liste des actifs pour la page courante
    $listSql = "
        SELECT 
            id_actif, type_equipement, nom_equipement, specification, numero_serie, numero_inventaire, 
            adresse_mac, date_achat, duree_amortissement, statut, date_service, bureau, 
            affecter_a, commentaire, creer_par
        FROM t_actif
        {$whereSql}
        ORDER BY numero_inventaire ASC
        LIMIT ? OFFSET ?";
    
    $stmtList = $pdo->prepare($listSql);
    
    $paramIndex = 1;

    // Ajout des paramètres de filtre (Positional parameters 1 to N)
    foreach ($params as $value) {
        $stmtList->bindValue($paramIndex++, $value);
    }

    // Ajout des paramètres de pagination (LIMIT et OFFSET)
    $stmtList->bindValue($paramIndex++, $itemsPerPage, PDO::PARAM_INT); // N+1: LIMIT
    $stmtList->bindValue($paramIndex, $offset, PDO::PARAM_INT);       // N+2: OFFSET
    
    $stmtList->execute();
    $actifs = $stmtList->fetchAll(PDO::FETCH_ASSOC);

    // 3.3 Récupérer tous les types d'équipement pour le filtre dropdown
    $stmtTypes = $pdo->query("SELECT description FROM t_device ORDER BY description ASC");
    $deviceTypes = $stmtTypes->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    // Traduction du message d'erreur
    $error = $T['db_error'] . $e->getMessage();
}

// --- 4. Définir les options de statut pour les filtres (Les valeurs restent en FR pour la DB) ---
$statutOptionsDB = [
    'En stock', 'En service', 'Hors service', 'En réparation', 'Volé', 'Déclassé'
];

?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $T['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .container { max-width: 1600px; margin-top: 30px; margin-bottom: 50px; }
        
        /* CSS Optimisé pour le "sticky header" et la visibilité */
        .table-responsive { 
            max-height: 80vh; 
            overflow: auto; 
        }
        .table thead th { 
            position: sticky; 
            top: 0; 
            background-color: #343a40; 
            color: white; 
            z-index: 10; 
            white-space: nowrap;
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?php echo $T['header_inventory']; ?> (<span class="badge bg-primary"><?= $totalItems ?></span>)</h2>
        
        <div class="d-flex">
            <?php if ($user_profil !== 'Finance'): ?>
                <a href="import_data_actif.php" class="btn btn-primary me-2">
                    <i class="bi bi-file-earmark-spreadsheet"></i> <?php echo $T['import_asset']; ?>
                </a>
                <a href="ajout_gest_actif.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> <?php echo $T['add_asset']; ?>
                </a>
            <?php endif; ?>
        </div>
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
        <div class="card-header bg-primary text-white"><i class="bi bi-funnel-fill me-2"></i><?php echo $T['filter_header']; ?></div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="statut" class="form-label small"><?php echo $T['label_status']; ?></label>
                    <select class="form-select form-select-sm" id="statut" name="statut">
                        <option value=""><?php echo $T['option_all_status']; ?></option>
                        <?php foreach ($statutOptionsDB as $option): ?>
                            <option value="<?= htmlspecialchars($option) ?>" 
                                <?= ($filters['statut'] === $option) ? 'selected' : '' ?>>
                                <?= translateStatusForDisplay($option, $T) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="type" class="form-label small"><?php echo $T['label_type']; ?></label>
                    <select class="form-select form-select-sm" id="type" name="type">
                        <option value=""><?php echo $T['option_all_types']; ?></option>
                        <?php foreach ($deviceTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" 
                                <?= ($filters['type'] === $type) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="search" class="form-label small"><?php echo $T['label_search']; ?></label>
                    <input type="text" class="form-control form-control-sm" id="search" name="search" 
                                 value="<?= htmlspecialchars($filters['search']) ?>"
                                 placeholder="<?php echo $T['placeholder_search']; ?>">
                </div>

                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-search"></i> <?php echo $T['button_filter']; ?>
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
                            <th scope="col" class="text-center"><?php echo $T['th_id']; ?></th>
                            <th scope="col"><?php echo $T['th_inventory']; ?></th>
                            <th scope="col"><?php echo $T['th_type']; ?></th>
                            <th scope="col"><?php echo $T['th_name']; ?></th> 
                            <th scope="col"><?php echo $T['th_serial']; ?></th>
                            <th scope="col" class="text-center"><?php echo $T['th_status']; ?></th>
                            <th scope="col"><?php echo $T['th_affected_to']; ?></th>
                            <th scope="col"><?php echo $T['th_office']; ?></th>
                            <th scope="col"><?php echo $T['th_date_service']; ?></th>
                            <th scope="col"><?php echo $T['th_date_purchase']; ?></th>
                            <th scope="col" class="text-center"><?php echo $T['th_amort_years']; ?></th>
                            <th scope="col"><?php echo $T['th_comment']; ?></th>
                            <th scope="col" class="text-center"><?php echo $T['th_actions']; ?></th>
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
                                            <?= translateStatusForDisplay($actif['statut'] ?? 'N/A', $T) ?>
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
                                        
                                        <?php if ($user_profil !== 'Finance'): ?>
                                            <a href="modifier_gest_actif.php?id=<?= htmlspecialchars($actif['id_actif'] ?? '') ?>" class="btn btn-info btn-sm me-1" title="<?php echo $T['action_modify']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($user_profil === 'IT'): ?>
                                            <a href="?delete_id=<?= htmlspecialchars($actif['id_actif'] ?? '') ?>" class="btn btn-danger btn-sm" 
                                               onclick="return confirm('<?php echo $T['confirm_delete']; ?><?= htmlspecialchars($actif['id_actif'] ?? '') ?><?php echo $T['confirm_delete_end']; ?>');" 
                                               title="<?php echo $T['action_delete']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="13" class="text-center py-4">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> <?php echo $T['no_asset_found']; ?>
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
                    <a class="page-link" href="?<?= build_pagination_query($filters, max(1, $currentPage - 1)) ?>"><?php echo $T['pagination_previous']; ?></a>
                </li>
                <?php 
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
                    <a class="page-link" href="?<?= build_pagination_query($filters, min($totalPages, $currentPage + 1)) ?>"><?php echo $T['pagination_next']; ?></a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
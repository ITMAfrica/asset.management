<?php

session_start();
// --- 1. GESTION DE LA LANGUE ET TRADUCTIONS ---
$current_lang = $_SESSION['lang'] ?? 'fr';

// Dictionnaire de traduction (CORRIGÉ : PAS D'ESPACE INSÉCABLE)
$lang_texts = [
    'fr' => [ // Ligne 9 corrigée
        'title'                 => 'Liste des Utilisateurs',
        'h2_title'              => 'Gestion des Utilisateurs',
        // MESSAGES STATUT MIS À JOUR
        'status_success'        => 'Utilisateur ID: {$toggleId} {$actionWord} avec succès.',
        'status_error'          => 'Erreur lors de la modification du statut: ',
        'add_user'              => 'Ajouter un Utilisateur',
        'export_excel'          => 'Exporter Excel',
        'filter_title'          => 'Filtrer la liste',
        'placeholder_name'      => 'Nom Complet',
        'placeholder_email'     => 'Email',
        'placeholder_phone'     => 'Téléphone',
        'placeholder_entite'    => 'Entité', 
        'opt_all_profiles'      => 'Tous les Profils',
        'opt_all_entities'      => 'Toutes les Entités', 
        'opt_all_status'        => 'Tous les Statuts',
        'btn_filter'            => 'Filtrer',
        'btn_clear'             => 'Effacer',
        'th_id'                 => 'ID',
        'th_full_name'          => 'Nom Complet',
        'th_email'              => 'Email',
        'th_phone'              => 'Téléphone',
        'th_profile'            => 'Profil',
        'th_entite'             => 'Entité', 
        'th_status_compte'      => 'Statut Compte',
        'th_creation_date'      => 'Date Création',
        'th_actions'            => 'Actions',
        'btn_edit_title'        => 'Modifier',
        // ACTIONS STATUT NOUVELLES
        'btn_toggle_title'      => 'Activer/Désactiver',
        'status_actif'          => 'Actif',
        'status_inactif'        => 'Inactif',
        'action_disable'        => 'Désactiver',
        'action_enable'         => 'Activer',
        'confirm_disable'       => 'Êtes-vous sûr de vouloir DÉSACTIVER l\'utilisateur : ${nom} (ID: ${id}) ? Il ne pourra plus se connecter.',
        'confirm_enable'        => 'Êtes-vous sûr de vouloir ACTIVER l\'utilisateur : ${nom} (ID: ${id}) ? Il pourra à nouveau se connecter.',
        'no_users'              => 'Aucun utilisateur trouvé correspondant aux filtres.',
        'btn_previous'          => 'Précédent',
        'btn_next'              => 'Suivant',
        'confirm_delete'        => 'Êtes-vous sûr de vouloir supprimer DÉFINITIVEMENT l\'utilisateur : ${nom} (ID: ${id}) ? Cette action est irréversible.', // MODIFICATION 1 : Clé de traduction
        'delete_success'        => 'Utilisateur ID: {$deleteId} supprimé avec succès.', // MODIFICATION 1 : Clé de traduction
        'delete_error'          => 'Erreur lors de la suppression de l\'utilisateur: ', // MODIFICATION 1 : Clé de traduction
        'btn_delete_title'      => 'Supprimer l\'utilisateur', // MODIFICATION 1 : Clé de traduction
    ],
    'en' => [
        'title'                 => 'User List',
        'h2_title'              => 'User Management',
        // MESSAGES STATUT MIS À JOUR
        'status_success'        => 'User ID: {$toggleId} {$actionWord} successfully.',
        'status_error'          => 'Error while updating status: ',
        'add_user'              => 'Add User',
        'export_excel'          => 'Export to Excel',
        'filter_title'          => 'Filter List',
        'placeholder_name'      => 'Full Name',
        'placeholder_email'     => 'Email',
        'placeholder_phone'     => 'Phone',
        'placeholder_entite'    => 'Entity',
        'opt_all_profiles'      => 'All Profiles',
        'opt_all_entities'      => 'All Entities',
        'opt_all_status'        => 'All Statuses',
        'btn_filter'            => 'Filter',
        'btn_clear'             => 'Clear',
        'th_id'                 => 'ID',
        'th_full_name'          => 'Full Name',
        'th_email'              => 'Email',
        'th_phone'              => 'Phone',
        'th_profile'            => 'Profile',
        'th_entite'             => 'Entity', 
        'th_status_compte'      => 'Account Status',
        'th_creation_date'      => 'Creation Date',
        'th_actions'            => 'Actions',
        'btn_edit_title'        => 'Edit',
        // ACTIONS STATUT NOUVELLES
        'btn_toggle_title'      => 'Activate/Deactivate',
        'status_actif'          => 'Active',
        'status_inactif'        => 'Inactive',
        'action_disable'        => 'Disable',
        'action_enable'         => 'Enable',
        'confirm_disable'       => 'Are you sure you want to DISABLE the user: ${nom} (ID: ${id})? They will no longer be able to log in.',
        'confirm_enable'        => 'Are you sure you want to ENABLE the user: ${nom} (ID: ${id})? They will be able to log in again.',
        'no_users'              => 'No user found matching the filters.',
        'btn_previous'          => 'Previous',
        'btn_next'              => 'Next',
        'confirm_delete'        => 'Are you sure you want to permanently DELETE the user: ${nom} (ID: ${id})? This action is irreversible.', // MODIFICATION 1 : Clé de traduction
        'delete_success'        => 'User ID: {$deleteId} deleted successfully.', // MODIFICATION 1 : Clé de traduction
        'delete_error'          => 'Error deleting user: ', // MODIFICATION 1 : Clé de traduction
        'btn_delete_title'      => 'Delete user', // MODIFICATION 1 : Clé de traduction
    ]
];

// Fonction d'accès facile aux textes
function __($key) {
    global $lang_texts, $current_lang;
    return $lang_texts[$current_lang][$key] ?? $lang_texts['en'][$key] ?? 'MISSING_KEY';
}
// ---------------------------------------------


// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

// **ATTENTION : Assurez-vous que ce fichier existe et initialise bien $pdo**
require_once 'db_connexion.php'; // Votre fichier de connexion PDO 

// --- Configuration ---
$itemsPerPage = 10;
$profils = ['IT', 'Administration', 'Finance'];

// LISTE DES ENTITÉS PRÉDÉFINIES (VOTRE LISTE COMPLÈTE)
$entites = [
    'EMS',
    'GEO KATANGA',
    'IBS',
    'IFS',
    'ITM ANGOLA LDA',
    'ITM BENIN',
    'ITM BURUNDI',
    'ITM CAMEROUN',
    'ITM CONGO BRAZZAVILLE',
    'ITM COTE D\'IVOIRE',
    'ITM CX',
    'ITM ENVIRONNEMENT',
    'ITM GABON',
    'ITM HOLDING',
    'ITM KATOPE PTY',
    'ITM KENYA LTD',
    'ITM MAINTENANCE',
    'ITM NEXUS',
    'ITM NIGERIA',
    'ITM RWANDA LTD',
    'ITM SARL',
    'ITM SENEGAL',
    'ITM TANZANIA LTD',
    'ITM TOGO',
    'ITM UGANDA LTD',
    'ITM ZAMBIE',
    'JAMON',
    'KUVULU'
]; 

$message = '';

// --- Fonction utilitaire pour construire l'URL de pagination/filtres ---
function build_query(array $filters, int $page) {
    $params = $filters;
    $params['page'] = $page;
    // Nettoyer les filtres vides
    $params = array_filter($params, function($value) {
        return $value !== '' && $value !== null;
    });
    return http_build_query($params);
}

// =========================================================================
// LOGIQUE DE SUPPRESSION D'UTILISATEUR (MODIFICATION 2)
// =========================================================================
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $deleteId = (int)$_GET['delete_id'];
    
    try {
        // Préparation de la requête DELETE
        $stmt = $pdo->prepare("DELETE FROM t_users WHERE id = ?");
        $stmt->execute([$deleteId]);
        
        // Vérifier si une ligne a été affectée
        if ($stmt->rowCount() > 0) {
            $message_raw = str_replace(['{$deleteId}'], [$deleteId], __("delete_success"));
            $message = "<div class='alert alert-success'>{$message_raw}</div>";
        } else {
            $message = "<div class='alert alert-warning'>Erreur: Utilisateur ID: {$deleteId} non trouvé ou déjà supprimé.</div>";
        }
        
        // Redirection pour éviter la resoumission et nettoyer l'URL
        header("Location: liste_utilisateur.php?message=" . urlencode(strip_tags($message)));
        exit();
    } catch (PDOException $e) {
        // En cas d'erreur de la base de données
        $message = "<div class='alert alert-danger'>" . __("delete_error") . $e->getMessage() . "</div>";
        header("Location: liste_utilisateur.php?message=" . urlencode(strip_tags($message)));
        exit();
    }
}
// =========================================================================

// =========================================================================
// LOGIQUE D'ACTIVATION / DÉSACTIVATION (Inchangée)
// =========================================================================
if (isset($_GET['toggle_id']) && isset($_GET['new_status']) && is_numeric($_GET['toggle_id'])) {
    $toggleId = (int)$_GET['toggle_id'];
    // S'assurer que le statut est sécurisé ('Actif' ou 'Inactif')
    $newStatus = ($_GET['new_status'] === 'Actif') ? 'Actif' : 'Inactif'; 
    
    // Déterminer le mot pour le message de succès (e.g. "activé" ou "désactivé")
    $actionWordRaw = ($newStatus === 'Actif') ? __("action_enable") : __("action_disable");
    $actionWord = strtolower($actionWordRaw) . ($current_lang === 'fr' ? 'é' : 'd'); // 'activé' ou 'disabled'
    
    try {
        // Mettre à jour la colonne statut_compte
        $stmt = $pdo->prepare("UPDATE t_users SET statut_compte = ? WHERE id = ?");
        $stmt->execute([$newStatus, $toggleId]);
        
        // Préparer le message de succès
        $message_raw = str_replace(['{$toggleId}', '{$actionWord}'], [$toggleId, $actionWord], __("status_success"));
        $message = "<div class='alert alert-success'>{$message_raw}</div>";
        
        // Redirection pour éviter la resoumission et nettoyer l'URL
        header("Location: liste_utilisateur.php?message=" . urlencode(strip_tags($message)));
        exit();
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>" . __("status_error") . $e->getMessage() . "</div>";
    }
}
// =========================================================================


// Récupérer les messages après la redirection
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
}

// --- 2. Filtres et Pagination (AJOUT DE STATUT_COMPTE) ---
$filters = [
    'nom_complet' => trim($_GET['nom_complet'] ?? ''),
    'email' => trim($_GET['email'] ?? ''),
    'telephone' => trim($_GET['telephone'] ?? ''),
    'profil' => trim($_GET['profil'] ?? ''),
    'entite' => trim($_GET['entite'] ?? ''), 
    'statut_compte' => trim($_GET['statut_compte'] ?? ''), // AJOUT DU FILTRE STATUT
];

$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Construction de la clause WHERE
$whereClauses = [];
$bindParams = [];

if (!empty($filters['nom_complet'])) {
    $whereClauses[] = "nom_complet ILIKE ?";
    $bindParams[] = '%' . $filters['nom_complet'] . '%';
}
if (!empty($filters['email'])) {
    $whereClauses[] = "email ILIKE ?";
    $bindParams[] = '%' . $filters['email'] . '%';
}
if (!empty($filters['telephone'])) {
    $whereClauses[] = "telephone ILIKE ?";
    $bindParams[] = '%' . $filters['telephone'] . '%';
}
if (!empty($filters['profil'])) {
    $whereClauses[] = "profil = ?";
    $bindParams[] = $filters['profil'];
}
if (!empty($filters['entite'])) { 
    $whereClauses[] = "entite = ?";
    $bindParams[] = $filters['entite'];
}
if (!empty($filters['statut_compte'])) { // CLAUSE DE FILTRE STATUT COMPTE
    $whereClauses[] = "statut_compte = ?";
    $bindParams[] = $filters['statut_compte'];
}


$whereSql = count($whereClauses) > 0 ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

// --- 3. Comptage Total (pour la pagination) ---
$sqlCount = "SELECT COUNT(id) FROM t_users" . $whereSql;
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($bindParams);
$totalItems = $stmtCount->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);
$currentPage = max(1, min($currentPage, $totalPages > 0 ? $totalPages : 1)); // Valider la page

// --- 4. Récupération des Données (avec LIMIT et OFFSET) ---
// AJOUT DE 'statut_compte' dans la requête SELECT
$sqlData = "SELECT id, nom_complet, email, telephone, profil, entite, statut_compte, date_creation 
             FROM t_users" . $whereSql . "
             ORDER BY nom_complet ASC 
             LIMIT ? OFFSET ?";
$stmtData = $pdo->prepare($sqlData);
$finalBindParams = array_merge($bindParams, [$itemsPerPage, $offset]);
$stmtData->execute($finalBindParams);
$users = $stmtData->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __("title") ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* Ajuster la largeur max pour accueillir la nouvelle colonne */
        .container { max-width: 1600px; margin-top: 30px; margin-bottom: 50px; } 
    </style>
</head>
<body>

<div class="container">
    
    <h2 class="mb-4"><i class="bi bi-people-fill"></i> <?= __("h2_title") ?></h2>

    <?= $message ?>

    <div class="d-flex justify-content-between mb-3">
        <a href="ajouter_utilisateur.php" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> <?= __("add_user") ?>
        </a>
        <form action="export_excel.php" method="post" target="_blank">
            <input type="hidden" name="table" value="t_users">
            <?php foreach ($filters as $key => $value): ?>
                <input type="hidden" name="filter_<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
            <?php endforeach; ?>
            <button type="submit" class="btn btn-success">
                <i class="bi bi-file-earmark-spreadsheet"></i> <?= __("export_excel") ?>
            </button>
        </form>
    </div>

    <form method="get" class="card card-body mb-4 shadow-sm">
        <h5 class="card-title"><i class="bi bi-funnel"></i> <?= __("filter_title") ?></h5>
        <div class="row g-3">
            <div class="col-md-2">
                <input type="text" name="nom_complet" class="form-control form-control-sm" placeholder="<?= __("placeholder_name") ?>" value="<?= htmlspecialchars($filters['nom_complet']) ?>">
            </div>
            <div class="col-md-2">
                <input type="text" name="email" class="form-control form-control-sm" placeholder="<?= __("placeholder_email") ?>" value="<?= htmlspecialchars($filters['email']) ?>">
            </div>
            <div class="col-md-1">
                <input type="text" name="telephone" class="form-control form-control-sm" placeholder="<?= __("placeholder_phone") ?>" value="<?= htmlspecialchars($filters['telephone']) ?>">
            </div>
            <div class="col-md-2">
                <select name="profil" class="form-select form-select-sm">
                    <option value=""><?= __("opt_all_profiles") ?></option>
                    <?php foreach ($profils as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= ($filters['profil'] === $p) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"> 
                <select name="entite" class="form-select form-select-sm">
                    <option value=""><?= __("opt_all_entities") ?></option>
                    <?php foreach ($entites as $e): ?>
                        <option value="<?= htmlspecialchars($e) ?>" <?= ($filters['entite'] === $e) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1"> 
                <select name="statut_compte" class="form-select form-select-sm">
                    <option value=""><?= __("opt_all_status") ?></option>
                    <option value="Actif" <?= ($filters['statut_compte'] === 'Actif') ? 'selected' : '' ?>>
                        <?= __("status_actif") ?>
                    </option>
                    <option value="Inactif" <?= ($filters['statut_compte'] === 'Inactif') ? 'selected' : '' ?>>
                        <?= __("status_inactif") ?>
                    </option>
                </select>
            </div>
            <div class="col-md-2 d-flex">
                <button type="submit" class="btn btn-info btn-sm w-100 me-2"><i class="bi bi-search"></i> <?= __("btn_filter") ?></button>
                <a href="liste_utilisateur.php" class="btn btn-warning btn-sm"><i class="bi bi-x-circle"></i> <?= __("btn_clear") ?></a>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th><?= __("th_id") ?></th>
                            <th><?= __("th_full_name") ?></th>
                            <th><?= __("th_email") ?></th>
                            <th><?= __("th_phone") ?></th>
                            <th><?= __("th_profile") ?></th>
                            <th><?= __("th_entite") ?></th>
                            <th><?= __("th_status_compte") ?></th> <th><?= __("th_creation_date") ?></th>
                            <th class="text-center"><?= __("th_actions") ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): 
                                // Déterminer le statut et les actions
                                // Utiliser 'Actif' par défaut si la colonne n'est pas encore remplie
                                $isActif = ($user['statut_compte'] ?? 'Actif') === 'Actif';
                                $badgeClass = $isActif ? 'bg-success' : 'bg-danger';
                                $newStatus = $isActif ? 'Inactif' : 'Actif';
                                $actionIcon = $isActif ? 'bi-lock' : 'bi-unlock';
                                $actionColor = $isActif ? 'btn-outline-danger' : 'btn-outline-success';
                                $actionTitle = $isActif ? __("action_disable") : __("action_enable");
                                $confirmMessageKey = $isActif ? 'confirm_disable' : 'confirm_enable';
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['id']) ?></td>
                                    <td><?= htmlspecialchars($user['nom_complet']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['telephone']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($user['profil']) ?></span></td>
                                    <td><?= htmlspecialchars($user['entite'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= $isActif ? __("status_actif") : __("status_inactif") ?>
                                        </span>
                                    </td> <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($user['date_creation']))) ?></td>
                                    <td class="text-center d-flex justify-content-center">
                                        <a href="modifier_utilisateur.php?id=<?= htmlspecialchars($user['id']) ?>" class="btn btn-sm btn-outline-primary me-1" title="<?= __("btn_edit_title") ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm <?= $actionColor ?> me-1" title="<?= $actionTitle ?>" 
                                            onclick="confirmToggle(<?= htmlspecialchars($user['id']) ?>, '<?= htmlspecialchars(addslashes($user['nom_complet'])) ?>', '<?= $newStatus ?>', '<?= $confirmMessageKey ?>')">
                                            <i class="bi <?= $actionIcon ?>"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" title="<?= __("btn_delete_title") ?>" 
                                            onclick="confirmDelete(<?= htmlspecialchars($user['id']) ?>, '<?= htmlspecialchars(addslashes($user['nom_complet'])) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4"> <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> <?= __("no_users") ?>
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
                    <a class="page-link" href="?<?= build_query($filters, max(1, $currentPage - 1)) ?>"><?= __("btn_previous") ?></a>
                </li>
                <?php 
                // Affichage intelligent des pages (ex: 1... 5 6 7 ... 10)
                $start = max(1, $currentPage - 2);
                $end = min($totalPages, $currentPage + 2);
                
                if ($start > 1) { echo '<li class="page-item"><a class="page-link" href="?' . build_query($filters, 1) . '">1</a></li>'; }
                if ($start > 2) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; }
                
                for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= build_query($filters, $i) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                
                if ($end < $totalPages - 1) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; }
                if ($end < $totalPages) { echo '<li class="page-item"><a class="page-link" href="?' . build_query($filters, $totalPages) . '">' . $totalPages . '</a></li>'; }

                ?>
                <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= build_query($filters, min($totalPages, $currentPage + 1)) ?>"><?= __("btn_next") ?></a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Fonction pour gérer l'activation/désactivation du compte (Inchangée)
    function confirmToggle(id, nom, newStatus, confirmKey) {
        // Dictionnaire de messages de confirmation (récupérés depuis PHP)
        const messages = {
            'confirm_disable': '<?= htmlspecialchars(str_replace("\n", "", __("confirm_disable"))) ?>',
            'confirm_enable': '<?= htmlspecialchars(str_replace("\n", "", __("confirm_enable"))) ?>'
        };

        // Récupérer le message de confirmation traduit
        const messageTemplate = messages[confirmKey];
        
        // Remplacer les placeholders ${nom} et ${id} dans la chaîne JS
        const message = messageTemplate.replace('${nom}', nom).replace('${id}', id);

        if (confirm(message)) {
            // Rediriger vers la page avec les paramètres pour la modification du statut
            window.location.href = `liste_utilisateur.php?toggle_id=${id}&new_status=${newStatus}`;
        }
    }

    // MODIFICATION 4 : FONCTION POUR GÉRER LA SUPPRESSION DÉFINITIVE
    function confirmDelete(id, nom) {
        // Récupérer le message de confirmation de suppression
        const messageTemplate = '<?= htmlspecialchars(str_replace("\n", "", __("confirm_delete"))) ?>';
        
        // Remplacer les placeholders ${nom} et ${id}
        const message = messageTemplate.replace('${nom}', nom).replace('${id}', id);

        if (confirm(message)) {
            // Rediriger vers la page avec le paramètre delete_id pour la suppression
            window.location.href = `liste_utilisateur.php?delete_id=${id}`;
        }
    }
    // FIN MODIFICATION 4
</script>
</body>
</html>
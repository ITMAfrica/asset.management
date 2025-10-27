<?php
session_start();

// --- GESTION DE LA LANGUE ET TRADUCTIONS ---
$current_lang = $_SESSION['lang'] ?? 'fr';

// Dictionnaire de traduction
$lang_texts = [
    'fr' => [
        // Titres & Descriptions
        'title'                     => 'Notifications - Amortissement des Actifs',
        'h1_title'                  => 'Notifications : Actifs Amortis',
        'lead_text'                 => 'Cette page liste tous les équipements qui ont atteint ou dépassé leur **durée d\'amortissement** déclarée depuis leur date de service.',
        'card_header'               => 'Liste des Actifs Amortis',
        
        // Messages d'erreurs/succès (PHP)
        'success_update_prefix'     => '🎉 **Succès!** ',
        'success_update_suffix'     => ' actif(s) ont été automatiquement mis à jour au statut \'Amorti\'.',
        'err_db'                    => 'Erreur de base de données lors de l\'amortissement:',
        
        // En-têtes de colonnes
        'col_id'                    => 'ID',
        'col_inventory'             => 'Inventaire',
        'col_type'                  => 'Type d\'Équipement',
        'col_date_service'          => 'Date Service',
        'col_amortization_years'    => 'Durée Amortissement (Ans)',
        'col_status'                => 'Statut',
        'col_office'                => 'Bureau',
        'no_amortized_assets'       => '<i class="bi bi-check-circle-fill text-success me-2"></i> Aucun actif n\'est actuellement amorti.',
        
        // Traduction des Statuts (pour l'affichage)
        'status_amorti'             => 'Amorti',
        'status_service'            => 'En service',
        'status_stock'              => 'En stock',
        'status_repair'             => 'En réparation',
        'status_decommissioned'     => 'Déclassé',
        'status_out_of_service'     => 'Hors service',
        'status_stolen'             => 'Volé',

    ],
    'en' => [
        // Titles & Descriptions
        'title'                     => 'Notifications - Asset Depreciations',
        'h1_title'                  => 'Notifications: Depreciated Assets',
        'lead_text'                 => 'This page lists all equipment that have reached or exceeded their declared **depreciation period** since their date of service.',
        'card_header'               => 'List of Depreciated Assets',
        
        // Error/Success Messages (PHP)
        'success_update_prefix'     => '🎉 **Success!** ',
        'success_update_suffix'     => ' asset(s) have been automatically updated to the \'Depreciated\' status.',
        'err_db'                    => 'Database error during amortization:',
        
        // Column Headers
        'col_id'                    => 'ID',
        'col_inventory'             => 'Inventory',
        'col_type'                  => 'Equipment Type',
        'col_date_service'          => 'Service Date',
        'col_amortization_years'    => 'Depreciation Period (Years)',
        'col_status'                => 'Status',
        'col_office'                => 'Office',
        'no_amortized_assets'       => '<i class="bi bi-check-circle-fill text-success me-2"></i> No assets are currently depreciated.',
        
        // Status Translation (for display)
        'status_amorti'             => 'Depreciated',
        'status_service'            => 'In Service',
        'status_stock'              => 'In Stock',
        'status_repair'             => 'In Repair',
        'status_decommissioned'     => 'Decommissioned',
        'status_out_of_service'     => 'Out of Service',
        'status_stolen'             => 'Stolen',
    ]
];

// Fonction d'accès facile aux textes
function __($key) {
    global $lang_texts, $current_lang;
    return $lang_texts[$current_lang][$key] ?? $lang_texts['en'][$key] ?? 'MISSING_KEY';
}

// Fonction pour traduire les statuts (pour l'affichage)
function translate_status($status) {
    $status_map = [
        'Amorti'        => 'status_amorti',
        'En service'    => 'status_service',
        'En stock'      => 'status_stock',
        'En réparation' => 'status_repair',
        'Déclassé'      => 'status_decommissioned',
        'Hors service'  => 'status_out_of_service',
        'Volé'          => 'status_stolen',
    ];
    $key = $status_map[$status] ?? null;
    return $key ? __($key) : htmlspecialchars($status);
}
// ---------------------------------------------


// Vérification de session
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

require_once 'db_connexion.php'; 

$message = '';
$actifsAmortis = [];
$actifsMisAJour = 0;

try {
    // --- 1. Logique d'Amortissement Automatique (Mise à jour du statut) ---
    // NOTE: Le statut 'Amorti' ici est en français, car il correspond à la valeur DB.
    $sql_update = "
        UPDATE t_actif 
        SET statut = 'Amorti'
        WHERE 
            statut NOT IN ('Amorti', 'Déclassé', 'Hors service', 'Volé') 
            AND date_service IS NOT NULL 
            AND date_achat IS NOT NULL
            -- Vérifie si le nombre d'années entre date_service et aujourd'hui est supérieur à duree_amortissement
            AND DATE_PART('year', NOW()::date) - DATE_PART('year', date_service::date) >= duree_amortissement
        RETURNING id_actif;
    ";
    
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute();
    $actifsMisAJour = $stmt_update->rowCount(); 

    if ($actifsMisAJour > 0) {
        $message = "<div class='alert alert-success'>" . __("success_update_prefix") . $actifsMisAJour . __("success_update_suffix") . "</div>";
    }

    // --- 2. Récupération de la liste des Actifs Amortis ---
    $sql_select = "
        SELECT 
            id_actif, type_equipement, numero_inventaire, 
            date_achat, duree_amortissement, statut, date_service, bureau
        FROM 
            t_actif
        WHERE 
            statut = 'Amorti'
        ORDER BY 
            date_service DESC
    ";

    $stmt_select = $pdo->query($sql_select);
    $actifsAmortis = $stmt_select->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = "<div class='alert alert-danger'>" . __("err_db") . " " . $e->getMessage() . "</div>";
}

// Fonction utilitaire pour le design (badges) - Utilise les valeurs DB (en français)
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Amorti': return 'bg-dark';
        case 'En service': return 'bg-success';
        case 'En stock': return 'bg-info';
        case 'En réparation': return 'bg-warning text-dark';
        default: return 'bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __("title") ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid mt-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-bell-fill text-warning"></i> <?= __("h1_title") ?></h1>
    </div>

    <?= $message ?>

    <p class="lead">
        <?= __("lead_text") ?>
    </p>

    <div class="card shadow mt-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-table"></i> <?= __("card_header") ?> (<?= count($actifsAmortis) ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?= __("col_id") ?></th>
                            <th><?= __("col_inventory") ?></th>
                            <th><?= __("col_type") ?></th> 
                            <th><?= __("col_date_service") ?></th>
                            <th><?= __("col_amortization_years") ?></th>
                            <th><?= __("col_status") ?></th>
                            <th><?= __("col_office") ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($actifsAmortis) > 0): ?>
                            <?php foreach ($actifsAmortis as $actif): ?>
                            <tr>
                                <td><?= htmlspecialchars($actif['id_actif']) ?></td>
                                <td><?= htmlspecialchars($actif['numero_inventaire'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($actif['type_equipement']) ?></td> 
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($actif['date_service']))) ?></td>
                                <td><?= htmlspecialchars($actif['duree_amortissement']) ?></td>
                                <td>
                                    <span class="badge <?= getStatusBadgeClass($actif['statut']) ?>">
                                        <?= translate_status($actif['statut']) // Afficher le statut traduit ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($actif['bureau'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">
                                    <?= __("no_amortized_assets") ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
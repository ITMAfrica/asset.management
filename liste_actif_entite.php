<?php
session_start();
require_once 'db_connexion.php'; // Assurez-vous que ce fichier existe et fonctionne

// Vérification de session
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

// --- GESTION DE LA LANGUE ET TRADUCTIONS (Réutilisé de rapports.php) ---
$current_lang = $_SESSION['lang'] ?? 'fr';

$lang_texts = [
    'fr' => [
        'page_title' => 'Liste Actifs par Entité',
        'h1_title' => 'Liste des Actifs par Bureau / Localité',
        'back_to_reports' => 'Retour aux Rapports',
        'filter_title' => 'Filtrer les Actifs',
        'label_bureau' => 'Sélectionner l\'Entité / Bureau',
        'option_all' => 'Toutes les Entités',
        'button_filter' => 'Afficher la Liste',
        'no_results' => 'Aucun actif trouvé pour ce filtre.',
        'table_inventory' => 'N° Inventaire',
        'table_type' => 'Type',
        'table_marque' => 'Marque',
        'table_status' => 'Statut',
        'table_staff' => 'Affecté à',
        'button_pdf' => 'Télécharger le Rapport PDF',
    ],
    'en' => [
        'page_title' => 'Assets List by Entity',
        'h1_title' => 'Assets List by Office / Location',
        'back_to_reports' => 'Back to Reports',
        'filter_title' => 'Filter Assets',
        'label_bureau' => 'Select Entity / Office',
        'option_all' => 'All Entities',
        'button_filter' => 'Show List',
        'no_results' => 'No assets found for this filter.',
        'table_inventory' => 'Inventory N°',
        'table_type' => 'Type',
        'table_marque' => 'Brand',
        'table_status' => 'Status',
        'table_staff' => 'Assigned to',
        'button_pdf' => 'Download PDF Report',
    ]
];

function __($key) {
    global $lang_texts, $current_lang;
    return $lang_texts[$current_lang][$key] ?? $lang_texts['en'][$key] ?? 'MISSING_KEY';
}
// ---------------------------------------------------------------------

// Liste des entités fournies
$entites = [
    'EMS', 'GEO KATANGA', 'IBS', 'IFS', 'ITM ANGOLA LDA', 'ITM BENIN', 'ITM BURUNDI', 
    'ITM CAMEROUN', 'ITM CONGO BRAZZAVILLE', 'ITM COTE D\'IVOIRE', 'ITM CX', 
    'ITM ENVIRONNEMENT', 'ITM GABON', 'ITM HOLDING', 'ITM KATOPE PTY', 'ITM KENYA LTD', 
    'ITM MAINTENANCE', 'ITM NEXUS', 'ITM NIGERIA', 'ITM RWANDA LTD', 'ITM SARL', 
    'ITM SENEGAL', 'ITM TANZANIA LTD', 'ITM TOGO', 'ITM UGANDA LTD', 'ITM ZAMBIE', 
    'JAMON', 'KUVULU'
];

$selected_bureau = $_GET['bureau'] ?? '';
$actifs = [];
$error_message = '';

try {
    // Construction de la requête SQL
    $sql = "SELECT numero_inventaire, type_equipement, nom_equipement, statut, affecter_a 
            FROM t_actif";
    $params = [];
    
    if (!empty($selected_bureau) && in_array($selected_bureau, $entites)) {
        $sql .= " WHERE bureau = :bureau";
        $params[':bureau'] = $selected_bureau;
    }
    
    $sql .= " ORDER BY numero_inventaire ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $actifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Erreur de base de données lors du chargement des actifs : " . $e->getMessage();
}

// Fonction utilitaire pour le badge de statut (copiée de modifier_gest_actif.php ou à adapter)
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'En service': return 'bg-success';
        case 'En stock': return 'bg-info';
        case 'En réparation': return 'bg-warning text-dark';
        case 'Volé':
        case 'Déclassé':
        case 'Hors service': return 'bg-danger';
        case 'Amorti': return 'bg-secondary';
        default: return 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __("page_title") ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .container { max-width: 1000px; margin-top: 20px; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-building text-info"></i> <?= __("h1_title") ?></h1>
        <a href="liste_rapport" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> <?= __("back_to_reports") ?></a>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light"><strong><?= __("filter_title") ?></strong></div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label for="bureau_filter" class="form-label"><?= __("label_bureau") ?></label>
                    <select class="form-select" id="bureau_filter" name="bureau">
                        <option value=""><?= __("option_all") ?></option>
                        <?php foreach ($entites as $entite): ?>
                            <option value="<?= htmlspecialchars($entite) ?>" 
                                <?= ($selected_bureau === $entite) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($entite) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> <?= __("button_filter") ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="d-flex justify-content-end mb-3">
        <?php 
        // L'URL du PDF doit inclure le filtre actuel
        $pdf_link = "generer_rapport_entite?bureau=" . urlencode($selected_bureau);
        ?>
        <a href="<?= $pdf_link ?>" target="_blank" class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf-fill"></i> <?= __("button_pdf") ?>
        </a>
    </div>

    <?php if (count($actifs) > 0): ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?= __("table_inventory") ?></th>
                                <th><?= __("table_type") ?></th>
                                <th><?= __("table_marque") ?></th>
                                <th><?= __("table_status") ?></th>
                                <th><?= __("table_staff") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($actifs as $actif): ?>
                            <tr>
                                <td><?= htmlspecialchars($actif['numero_inventaire']) ?></td>
                                <td><?= htmlspecialchars($actif['type_equipement']) ?></td>
                                <td><?= htmlspecialchars($actif['nom_equipement']) ?></td>
                                <td>
                                    <span class="badge <?= getStatusBadgeClass($actif['statut']) ?>">
                                        <?= htmlspecialchars($actif['statut']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($actif['affecter_a'] ?? 'N/A') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center mt-3">
            <?= __("no_results") ?>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
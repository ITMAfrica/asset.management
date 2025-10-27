<?php
session_start();

// Vérification de session simplifiée
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

// --- GESTION DE LA LANGUE ET TRADUCTIONS ---
$current_lang = $_SESSION['lang'] ?? 'fr';

// Dictionnaire de traduction (NETTOYÉ DES CARACTÈRES INVISIBLES)
$lang_texts = [
    'fr' => [
        'page_title'                => 'Gestion des Rapports',
        'h1_title'                  => 'Rapports d\'Actifs et de Personnel',
        'btn_back'                  => 'Retour au Menu',
        'alert_info_title'          => 'Informations',
        'alert_info_body'           => 'Cliquez sur un bouton pour générer et télécharger immédiatement le rapport correspondant au format PDF.',
        
        // Rapport Actifs
        'report_actif_title'        => 'Rapport Global des Actifs (Statuts)',
        'report_actif_desc'         => 'Liste détaillée des actifs avec leur statut actuel (En stock, En service, En réparation, etc.).',
        
        // Rapport Actif par Entité (NOUVEAU)
        'report_actif_entite_title' => 'Liste Actifs par Entité / Localité',
        'report_actif_entite_desc'  => 'Afficher la liste des actifs avec filtre par bureau et option de téléchargement PDF.',
        
        // Rapport Entités
        'report_entite_title'       => 'Rapport Types d\'Équipement',
        'report_entite_desc'        => 'Liste de tous les types d\'équipements enregistrés.',
        
        // Rapport Personnel
        'report_staff_title'        => 'Rapport Personnel / Staff',
        'report_staff_desc'         => 'Liste complète du personnel (Staff).',
    ],
    'en' => [
        'page_title'                => 'Reports Management',
        'h1_title'                  => 'Assets and Staff Reports',
        'btn_back'                  => 'Back to Menu',
        'alert_info_title'          => 'Information',
        'alert_info_body'           => 'Click a button to immediately generate and download the corresponding report in PDF format.',
        
        // Asset Report
        'report_actif_title'        => 'Global Asset Report (Statuses)',
        'report_actif_desc'         => 'Detailed list of assets with their current status (In Stock, In Service, In Repair, etc.).',
        
        // Asset by Entity Report (NEW)
        'report_actif_entite_title' => 'Assets List by Entity / Location',
        'report_actif_entite_desc'  => 'Display the list of assets with office filter and PDF download option.',
        
        // Entity Report
        'report_entite_title'       => 'Entity / Equipment Types Report',
        'report_entite_desc'        => 'List of all registered equipment types.',
        
        // Staff Report
        'report_staff_title'        => 'Personnel / Staff Report',
        'report_staff_desc'         => 'Complete list of personnel (Staff).',
    ]
];

// Fonction d'accès facile aux textes
function __($key) {
    global $lang_texts, $current_lang;
    return $lang_texts[$current_lang][$key] ?? $lang_texts['en'][$key] ?? 'MISSING_KEY';
}
// ---------------------------------------------

// Assurez-vous que la connexion à la BD est disponible pour d'éventuels futurs besoins
// require_once 'db_connexion.php'; 
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __("page_title") ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5" style="max-width: 800px;">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1><i class="bi bi-file-earmark-bar-graph-fill text-primary"></i> <?= __("h1_title") ?></h1>
        <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> <?= __("btn_back") ?></a>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill"></i> **<?= __("alert_info_title") ?>** : <?= __("alert_info_body") ?>
    </div>

    <div class="row g-4">
        
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-body d-grid gap-2">
                    <a href="generer_rapport.php?type=actif" target="_blank" class="btn btn-primary btn-lg">
                        <i class="bi bi-file-pdf"></i> <?= __("report_actif_title") ?>
                    </a>
                    <p class="card-text text-muted text-center mt-2">
                        <?= __("report_actif_desc") ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-body d-grid gap-2">
                    <a href="liste_actif_entite.php" class="btn btn-info btn-lg text-white">
                        <i class="bi bi-building"></i> <?= __("report_actif_entite_title") ?>
                    </a>
                    <p class="card-text text-muted text-center mt-2">
                        <?= __("report_actif_entite_desc") ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body d-grid gap-2">
                    <a href="generer_rapport.php?type=entite" target="_blank" class="btn btn-success btn-lg">
                        <i class="bi bi-file-pdf"></i> <?= __("report_entite_title") ?>
                    </a>
                    <p class="card-text text-muted text-center mt-2">
                        <?= __("report_entite_desc") ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body d-grid gap-2">
                    <a href="generer_rapport.php?type=staff" target="_blank" class="btn btn-warning btn-lg text-dark">
                        <i class="bi bi-file-pdf"></i> <?= __("report_staff_title") ?>
                    </a>
                    <p class="card-text text-muted text-center mt-2">
                        <?= __("report_staff_desc") ?>
                    </p>
                </div>
            </div>
        </div>

    </div>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
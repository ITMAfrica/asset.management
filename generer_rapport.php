<?php
// Démarrer la session pour récupérer la langue
session_start();

// Inclure l'autoloader de dompdf
// Ajustez ce chemin si votre installation dompdf est différente
require 'vendor/autoload.php';
require_once 'db_connexion.php'; 

use Dompdf\Dompdf;
use Dompdf\Options;

// --- 1. GESTION DE LA LANGUE ET TRADUCTIONS ---
$current_lang = $_SESSION['lang'] ?? 'fr';

// Dictionnaire de traduction
$lang_texts = [
    'fr' => [
        'err_invalid_type'          => 'Type de rapport invalide.',
        'err_db'                    => 'Erreur de base de données :',
        
        // Titres et Noms de fichiers
        'title_actif'               => 'Rapport Global des Actifs',
        'filename_actif'            => 'rapport_actifs_',
        'title_entite'              => 'Rapport des Entités (Types d\'Équipement)',
        'filename_entite'           => 'rapport_entites_',
        'title_staff'               => 'Rapport du Personnel (Staff)',
        'filename_staff'            => 'rapport_staff_',
        
        // En-têtes de colonnes
        'col_type'                  => 'Type',
        'col_inventory'             => 'Inventaire',
        'col_status'                => 'Statut',
        'col_assigned_to'           => 'Affecté à',
        'col_office'                => 'Bureau',
        'col_id'                    => 'ID',
        'col_description_entity'    => 'Description (Type d\'Équipement)',
        'col_staff_id'              => 'ID Staff',
        'col_full_name'             => 'Nom Complet',
        'col_email'                 => 'Email',
        
        // Informations du rapport
        'report_date_label'         => 'Date de génération:',
    ],
    'en' => [
        'err_invalid_type'          => 'Invalid report type.',
        'err_db'                    => 'Database error:',
        
        // Titles and Filenames
        'title_actif'               => 'Global Asset Report',
        'filename_actif'            => 'asset_report_',
        'title_entite'              => 'Entity Report (Equipment Types)',
        'filename_entite'           => 'entity_report_',
        'title_staff'               => 'Staff Report',
        'filename_staff'            => 'staff_report_',
        
        // Column Headers
        'col_type'                  => 'Type',
        'col_inventory'             => 'Inventory',
        'col_status'                => 'Status',
        'col_assigned_to'           => 'Assigned To',
        'col_office'                => 'Office',
        'col_id'                    => 'ID',
        'col_description_entity'    => 'Description (Equipment Type)',
        'col_staff_id'              => 'Staff ID',
        'col_full_name'             => 'Full Name',
        'col_email'                 => 'Email',
        
        // Report Information
        'report_date_label'         => 'Generation Date:',
    ]
];

// Fonction d'accès facile aux textes
function __($key) {
    global $lang_texts, $current_lang;
    return $lang_texts[$current_lang][$key] ?? $lang_texts['en'][$key] ?? 'MISSING_KEY';
}
// ---------------------------------------------


// Vérifier le type de rapport demandé
$reportType = $_GET['type'] ?? '';
$allowedTypes = ['actif', 'entite', 'staff'];

if (!in_array($reportType, $allowedTypes)) {
    die(__("err_invalid_type"));
}

$reportTitle = "";
$htmlContent = "";
$filename = "";

try {
    // --- 2. Récupération des données ---
    $reportTitle = __("title_{$reportType}");
    $filename = __("filename_{$reportType}") . date('Ymd_His') . ".pdf";

    switch ($reportType) {
        case 'actif':
            // Requête pour les actifs
            $stmt = $pdo->query("SELECT type_equipement, numero_inventaire, statut, affecter_a, bureau FROM t_actif ORDER BY statut, type_equipement");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $htmlContent = generateActifHtml($data);
            break;

        case 'entite':
            // Requête pour les entités (t_entite)
            $stmt = $pdo->query("SELECT id, description FROM t_entite ORDER BY description");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $htmlContent = generateEntiteHtml($data);
            break;

        case 'staff':
            // Requête pour le personnel (t_staff)
            $stmt = $pdo->query("SELECT id_staff, nom_complet, email, bureau FROM t_staff ORDER BY nom_complet");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $htmlContent = generateStaffHtml($data);
            break;
    }

} catch (PDOException $e) {
    die(__("err_db") . " " . $e->getMessage());
}

// --- 3. Fonctions de génération du contenu HTML (Utilisation de global pour __() ) ---

function generateActifHtml($data) {
    global $lang_texts, $current_lang; // Rendre la fonction __() accessible
    $html = '<table width="100%" border="1" cellspacing="0" cellpadding="5">';
    $html .= '<thead><tr>';
    $html .= '<th>' . __("col_type") . '</th>';
    $html .= '<th>' . __("col_inventory") . '</th>';
    $html .= '<th>' . __("col_status") . '</th>';
    $html .= '<th>' . __("col_assigned_to") . '</th>';
    $html .= '<th>' . __("col_office") . '</th>';
    $html .= '</tr></thead>';
    $html .= '<tbody>';
    foreach ($data as $row) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['type_equipement']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['numero_inventaire']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['statut']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['affecter_a']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['bureau']) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    return $html;
}

function generateEntiteHtml($data) {
    global $lang_texts, $current_lang; // Rendre la fonction __() accessible
    $html = '<table width="50%" border="1" cellspacing="0" cellpadding="5">';
    $html .= '<thead><tr>';
    $html .= '<th>' . __("col_id") . '</th>';
    $html .= '<th>' . __("col_description_entity") . '</th>';
    $html .= '</tr></thead>';
    $html .= '<tbody>';
    foreach ($data as $row) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['id']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['description']) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    return $html;
}

function generateStaffHtml($data) {
    global $lang_texts, $current_lang; // Rendre la fonction __() accessible
    $html = '<table width="100%" border="1" cellspacing="0" cellpadding="5">';
    $html .= '<thead><tr>';
    $html .= '<th>' . __("col_staff_id") . '</th>';
    $html .= '<th>' . __("col_full_name") . '</th>';
    $html .= '<th>' . __("col_email") . '</th>';
    $html .= '<th>' . __("col_office") . '</th>';
    $html .= '</tr></thead>';
    $html .= '<tbody>';
    foreach ($data as $row) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['id_staff']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['nom_complet']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['email']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['bureau']) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    return $html;
}


// --- 4. Configuration et Génération du PDF avec dompdf ---

// Configuration (pour CSS et gestion des encodages)
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica'); // Définir une police de base

$dompdf = new Dompdf($options);

// Contenu de base (CSS minimal pour le PDF)
$baseHtml = "
<html>
<head>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; }
        h1 { text-align: center; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>{$reportTitle}</h1>
    <p>" . __("report_date_label") . " " . date('d/m/Y H:i:s') . "</p>
    " . $htmlContent . "
</body>
</html>";

$dompdf->loadHtml($baseHtml);

// Définir la taille du papier (A4 portrait ou paysage si besoin)
$dompdf->setPaper('A4', 'portrait');

// Rendre le HTML en PDF
$dompdf->render();

// --- 5. Envoi du fichier au navigateur pour téléchargement ---
$dompdf->stream($filename, ["Attachment" => true]);
?>
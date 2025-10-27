<?php
session_start();

// 1. VÉRIFICATION DE SESSION ET INCLUSIONS
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

// Inclure la connexion à la base de données
// ASSUREZ-VOUS QUE CE CHEMIN EST CORRECT
require_once 'db_connexion.php'; 

// Inclure la librairie Dompdf (CHEMIN À ADAPTER)
// Si vous utilisez Composer :
// require 'vendor/autoload.php'; 
// Si vous utilisez Dompdf en téléchargeant les fichiers :
require 'dompdf/autoload.inc.php'; 

use Dompdf\Dompdf;
use Dompdf\Options;

// Récupérer le filtre de bureau
$bureau_filtre = $_GET['bureau'] ?? '';

// --- Configuration des traductions ---
$current_lang = $_SESSION['lang'] ?? 'fr';

$texts = [
    'fr' => [
        'report_title' => 'Rapport des Actifs par Entité',
        'table_bureau' => 'Entité/Bureau',
        'all_bureaux' => 'TOUTES LES ENTITÉS',
        'header_inventory' => 'N° Inventaire',
        'header_type' => 'Type d\'Équipement',
        'header_marque' => 'Marque / Modèle',
        'header_serial' => 'N° Série',
        'header_status' => 'Statut',
        'header_staff' => 'Affecté à',
        'header_spec' => 'Spécification', // NOUVEAU
        'report_date' => 'Date du rapport : ',
        'no_data' => 'Aucune donnée trouvée pour ce filtre.',
    ],
    'en' => [
        'report_title' => 'Assets Report by Entity',
        'table_bureau' => 'Entity/Office',
        'all_bureaux' => 'ALL ENTITIES',
        'header_inventory' => 'Inventory N°',
        'header_type' => 'Equipment Type',
        'header_marque' => 'Brand / Model',
        'header_serial' => 'Serial N°',
        'header_status' => 'Status',
        'header_staff' => 'Assigned to',
        'header_spec' => 'Specification', // NEW
        'report_date' => 'Report Date: ',
        'no_data' => 'No data found for this filter.',
    ]
];

function __pdf($key) {
    global $texts, $current_lang;
    return $texts[$current_lang][$key] ?? $texts['en'][$key];
}

function getStatusStyle($status) {
    switch ($status) {
        case 'En service': return 'background-color: #d4edda; color: #155724;'; 
        case 'En stock': return 'background-color: #cce5ff; color: #004085;';   
        case 'En réparation': return 'background-color: #fff3cd; color: #856404;'; 
        case 'Volé':
        case 'Déclassé':
        case 'Hors service': return 'background-color: #f8d7da; color: #721c24;'; 
        case 'Amorti': return 'background-color: #e2e3e5; color: #383d41;'; 
        default: return '';
    }
}

// 2. RÉCUPÉRATION DES DONNÉES

// AJOUT DE 'specification' dans la requête SQL
$sql = "SELECT numero_inventaire, type_equipement, nom_equipement, numero_serie, specification, statut, affecter_a, bureau
        FROM t_actif";
$params = [];
$where_clause = '';

if (!empty($bureau_filtre)) {
    $where_clause = " WHERE bureau = :bureau";
    $params[':bureau'] = $bureau_filtre;
}

$sql .= $where_clause . " ORDER BY bureau ASC, numero_inventaire ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $actifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

if (empty($actifs)) {
    die(__pdf('no_data'));
}

// 3. GÉNÉRATION DU CONTENU HTML

$html = '
<html>
<head>
    <meta charset="utf-8">
    <style>
        /** Configuration du PDF en paysage (A4-L) */
        @page { margin: 20px; size: A4 landscape; } 
        body { font-family: Arial, sans-serif; font-size: 8pt; } /* Taille de police réduite pour plus de colonnes */
        h1 { font-size: 16pt; text-align: center; margin-bottom: 5px; }
        .header-info { font-size: 9pt; margin-bottom: 10px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .header-info span { float: right; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; vertical-align: top;}
        th { background-color: #f2f2f2; font-size: 8.5pt; text-align: center; }
        
        .bureau-group { 
            background-color: #e0e0e0; 
            font-weight: bold; 
            font-size: 9pt; 
            padding: 5px; 
            text-align: left !important;
        }
    </style>
</head>
<body>';

// --- EN-TÊTE DU DOCUMENT ---
$bureau_display = empty($bureau_filtre) ? __pdf('all_bureaux') : htmlspecialchars($bureau_filtre);

$html .= '
    <h1>' . __pdf('report_title') . '</h1>
    <div class="header-info">
        ' . __pdf('table_bureau') . ' : <strong>' . $bureau_display . '</strong>
        <span>' . __pdf('report_date') . date('d/m/Y H:i:s') . '</span>
    </div>';

// --- TABLEAU DES DONNÉES ---
// Nous ajoutons 'Specification' et ajustons les largeurs pour 7 colonnes
$html .= '<table>
    <thead>
        <tr>
            <th style="width: 10%;">' . __pdf('header_inventory') . '</th>
            <th style="width: 12%;">' . __pdf('header_type') . '</th>
            <th style="width: 18%;">' . __pdf('header_marque') . '</th>
            <th style="width: 10%;">' . __pdf('header_serial') . '</th>
            <th style="width: 20%;">' . __pdf('header_spec') . '</th> <th style="width: 10%;">' . __pdf('header_status') . '</th>
            <th style="width: 20%;">' . __pdf('header_staff') . '</th>
        </tr>
    </thead>
    <tbody>';

$current_bureau = '';
// Compter le nombre total de colonnes (pour le colspan du groupe)
$total_columns = 7; 

foreach ($actifs as $actif) {
    
    // Regroupement par bureau si 'Toutes les entités' est sélectionné
    if (empty($bureau_filtre) && $actif['bureau'] !== $current_bureau) {
        $current_bureau = $actif['bureau'];
        $html .= '<tr>
            <td colspan="' . $total_columns . '" class="bureau-group">' . __pdf('table_bureau') . ' : ' . htmlspecialchars($current_bureau) . '</td>
        </tr>';
    }
    
    // Ligne de données
    $html .= '<tr>
        <td>' . htmlspecialchars($actif['numero_inventaire']) . '</td>
        <td>' . htmlspecialchars($actif['type_equipement']) . '</td>
        <td>' . htmlspecialchars($actif['nom_equipement']) . '</td>
        <td>' . htmlspecialchars($actif['numero_serie']) . '</td>
        <td>' . nl2br(htmlspecialchars($actif['specification'])) . '</td> <td style="text-align: center; font-weight: bold; ' . getStatusStyle($actif['statut']) . '">' . htmlspecialchars($actif['statut']) . '</td>
        <td>' . htmlspecialchars($actif['affecter_a'] ?? 'N/A') . '</td>
    </tr>';
}

$html .= '
    </tbody>
</table>';

$html .= '
</body>
</html>';


// 4. PARAMÈTRES ET EXÉCUTION DOMPDF

// Configuration de Dompdf (utilisation de 'A4' et 'landscape' pour le format)
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isHtml5ParserEnabled', true); 
$options->set('isPhpEnabled', true);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

// Définit le format du papier et l'orientation
$dompdf->setPaper('A4', 'landscape');

// Rendu du HTML en PDF
$dompdf->render();

// Nom du fichier PDF
$filename = "Rapport_Actifs_";
if (!empty($bureau_filtre)) {
    $filename .= str_replace([' ', '/'], ['_', '-'], $bureau_filtre);
} else {
    $filename .= "Global";
}
$filename .= "_" . date('Ymd_His') . ".pdf";

// Envoi du fichier PDF au navigateur (inline)
$dompdf->stream($filename, ["Attachment" => false]);

exit;
?>
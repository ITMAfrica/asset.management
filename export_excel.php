<?php
session_start();

// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

// --- GESTION DE LA LANGUE ET TRADUCTIONS ---
$current_lang = $_SESSION['lang'] ?? 'fr';

$lang_texts = [
    'fr' => [
        'col_id'            => 'ID',
        'col_full_name'     => 'Nom Complet',
        'col_email'         => 'Email',
        'col_phone'         => 'Telephone',
        'col_profile'       => 'Profil',
        'col_creation_date' => 'Date Creation',
        'err_db_export'     => 'Erreur de base de données:',
        'filename_prefix'   => 'export_utilisateurs_',
    ],
    'en' => [
        'col_id'            => 'ID',
        'col_full_name'     => 'Full Name',
        'col_email'         => 'Email',
        'col_phone'         => 'Phone',
        'col_profile'       => 'Profile',
        'col_creation_date' => 'Creation Date',
        'err_db_export'     => 'Database error:',
        'filename_prefix'   => 'users_export_',
    ]
];

function __($key) {
    global $lang_texts, $current_lang;
    return $lang_texts[$current_lang][$key] ?? $lang_texts['en'][$key] ?? 'MISSING_KEY';
}
// ---------------------------------------------

require_once 'db_connexion.php'; 

// Définir les en-têtes pour le téléchargement du fichier CSV
header('Content-Type: text/csv; charset=utf-8');
// Utilisation du préfixe traduit pour le nom du fichier
header('Content-Disposition: attachment; filename=' . __("filename_prefix") . date('Ymd_His') . '.csv');

// Ouvrir le flux de sortie
// NOTE: Pour les fichiers CSV en UTF-8 lisibles par Excel, il est souvent recommandé d'inclure le BOM
// fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

$output = fopen('php://output', 'w');

// Ajouter l'en-tête CSV (Noms des colonnes traduits)
$csv_header = [
    __('col_id'),
    __('col_full_name'),
    __('col_email'),
    __('col_phone'),
    __('col_profile'),
    __('col_creation_date')
];
fputcsv($output, $csv_header, ';'); // Utiliser ';' comme séparateur pour la compatibilité Excel

// --- 1. Reconstruction des Filtres (Logique inchangée) ---
$filters = [
    'nom_complet' => trim($_POST['filter_nom_complet'] ?? ''),
    'email' => trim($_POST['filter_email'] ?? ''),
    'telephone' => trim($_POST['filter_telephone'] ?? ''),
    'profil' => trim($_POST['filter_profil'] ?? ''),
];

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

$whereSql = count($whereClauses) > 0 ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

// --- 2. Récupération de TOUTES les données (sans LIMIT/OFFSET) ---
try {
    $sqlExport = "SELECT id, nom_complet, email, telephone, profil, date_creation 
                  FROM t_users" . $whereSql . "
                  ORDER BY nom_complet ASC";
    $stmtExport = $pdo->prepare($sqlExport);
    $stmtExport->execute($bindParams);

    // Écriture des lignes de données
    while ($row = $stmtExport->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row, ';');
    }

} catch (PDOException $e) {
    // En cas d'erreur de base de données (message traduit)
    fputcsv($output, [__("err_db_export") . " " . $e->getMessage()], ';');
}

// Fermer le flux de sortie
fclose($output);
exit;
?>
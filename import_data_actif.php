<?php
// Inclure le fichier de connexion
require_once 'db_connexion.php'; 

// Démarrer la session pour le cache et la langue
session_start();

// Simuler la langue (doit être définie ailleurs, ici par défaut)
$current_lang = $_SESSION['lang'] ?? 'fr';
$modifier_par = $_SESSION['user_nom'] ?? 'Système/Admin Inconnu';
$date_creation = date('Y-m-d H:i:s');


// --- 1. Dictionnaire de traduction ---
$texts = [
    'fr' => [
        'title' => 'Importation de Données d\'Actifs',
        'header' => 'Importation en Masse d\'Actifs',
        'select_file' => 'Sélectionner un Fichier (.csv ou .xlsx)',
        'required_cols' => 'Colonnes du Fichier (Doivent correspondre à l\'ordre) :',
        'template_cols' => 'type_equipement, nom_equipement, specification, numero_serie, numero_inventaire, adresse_mac, date_achat, duree_amortissement, statut, date_service, bureau, affecter_a, commentaire, creer_par',
        'file_upload' => 'Parcourir...',
        'button_view' => 'Visualiser les Données',
        'button_import' => 'Importer les Données',
        'button_cancel' => 'Annuler et Recommencer',
        'section_data_preview' => 'Aperçu des Données à Importer (Lignes Vides Ignorées)',
        'preview_status_valid' => '✅ Validé',
        'preview_status_invalid' => '❌ Invalide (Manque Champ Requis)',
        'msg_success_upload' => 'Fichier lu avec succès. Veuillez vérifier et valider l\'importation.',
        'msg_success_import' => '✅ **%d** enregistrements importés avec succès dans la table t_actif.',
        'msg_no_file' => 'Veuillez sélectionner un fichier valide.',
        'msg_invalid_format' => 'Format de fichier non supporté. Veuillez utiliser .csv ou .xlsx.',
        'msg_no_data' => 'Aucune donnée valide trouvée pour l\'importation.',
        'msg_error_db_transaction' => 'Erreur de transaction lors de l\'importation : ',
        'msg_import_canceled' => 'Importation annulée. Le formulaire a été réinitialisé.',
        'msg_upload_error' => 'Erreur lors du téléchargement du fichier.',
        'header_status' => 'Statut',
        'header_row' => 'Ligne',
        
        // Headers de colonnes pour l'affichage (Doivent correspondre aux données)
        'col_type_equipement' => 'Type Équipement',
        'col_nom_equipement' => 'Marque',
        'col_specification' => 'Spécification',
        'col_numero_serie' => 'N° Série',
        'col_numero_inventaire' => 'N° Inventaire',
        'col_adresse_mac' => 'Adresse MAC',
        'col_date_achat' => 'Date Achat',
        'col_duree_amortissement' => 'Durée Amort.',
        'col_statut' => 'Statut',
        'col_date_service' => 'Date Service',
        'col_bureau' => 'Bureau',
        'col_affecter_a' => 'Affecté à',
        'col_commentaire' => 'Commentaire',
        'col_creer_par' => 'Créé par',
        'col_date_creation' => 'Date Création',
    ],
    'en' => [
        'title' => 'Asset Data Import',
        'header' => 'Bulk Asset Import',
        'select_file' => 'Select a File (.csv or .xlsx)',
        'required_cols' => 'File Columns (Must match the order):',
        'template_cols' => 'type_equipement, nom_equipement, specification, numero_serie, numero_inventaire, adresse_mac, date_achat, duree_amortissement, statut, date_service, bureau, affecter_a, commentaire, creer_par',
        'file_upload' => 'Browse...',
        'button_view' => 'Preview Data',
        'button_import' => 'Import Data',
        'button_cancel' => 'Cancel and Restart',
        'section_data_preview' => 'Data Preview for Import (Empty Rows Ignored)',
        'preview_status_valid' => '✅ Validated',
        'preview_status_invalid' => '❌ Invalid (Missing Required Field)',
        'msg_success_upload' => 'File read successfully. Please check and confirm the import.',
        'msg_success_import' => '✅ **%d** records successfully imported into the t_actif table.',
        'msg_no_file' => 'Please select a valid file.',
        'msg_invalid_format' => 'Unsupported file format. Please use .csv or .xlsx.',
        'msg_no_data' => 'No valid data found for import.',
        'msg_error_db_transaction' => 'Transaction error during import: ',
        'msg_import_canceled' => 'Import canceled. Form has been reset.',
        'msg_upload_error' => 'Error during file upload.',
        'header_status' => 'Status',
        'header_row' => 'Row',

        'col_type_equipement' => 'Equipment Type',
        'col_nom_equipement' => 'Brand',
        'col_specification' => 'Specification',
        'col_numero_serie' => 'Serial N°',
        'col_numero_inventaire' => 'Inventory N°',
        'col_adresse_mac' => 'MAC Address',
        'col_date_achat' => 'Purchase Date',
        'col_duree_amortissement' => 'Depreciation Period',
        'col_statut' => 'Status',
        'col_date_service' => 'Service Date',
        'col_bureau' => 'Office',
        'col_affecter_a' => 'Assigned To',
        'col_commentaire' => 'Comment',
        'col_creer_par' => 'Created By',
        'col_date_creation' => 'Creation Date',
    ]
];
$T = $texts[$current_lang];

// Noms des colonnes de la DB (doivent correspondre aux données lues)
$db_columns = [
    'type_equipement', 'nom_equipement', 'specification', 'numero_serie', 'numero_inventaire',
    'adresse_mac', 'date_achat', 'duree_amortissement', 'statut', 'date_service',
    'bureau', 'affecter_a', 'commentaire', 'creer_par'
];

$message = '';

/**
 * Fonction LISANT un fichier CSV/XLSX.
 * NOTE : La lecture réelle des fichiers .xlsx nécessite une librairie externe (ex: PhpSpreadsheet).
 */
function readImportFile($filePath, $fileExtension, $dbColumns, $modifierPar, $dateCreation) {
    
    $rows = [];
    
    if ($fileExtension === 'csv') {
        // Lecture basique pour CSV (suppose le point-virgule comme séparateur)
        // ATTENTION: Changez ";" par "," si votre fichier utilise la virgule.
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $rowNum = 0;
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                if ($rowNum++ === 0) continue; // Ignorer l'en-tête (ligne 1)

                // Enlève les lignes entièrement vides
                if (count(array_filter($data, 'strlen')) === 0) continue;

                // On prend les 14 premières colonnes du fichier
                $rowData = array_slice($data, 0, count($dbColumns));
                
                // Si la ligne n'est pas complètement vide
                if (!empty(array_filter($rowData))) {
                    // S'assurer qu'on a le bon nombre de colonnes (au moins 14)
                    if (count($rowData) >= count($dbColumns)) {
                        $rows[] = array_combine($dbColumns, $rowData);
                    }
                }
            }
            fclose($handle);
        }
    } elseif ($fileExtension === 'xlsx') {
        // --- NOTE IMPORTANTE : Lecture XLSX désactivée (simulation retirée) ---
        // Le code de simulation des données fictives a été retiré pour corriger l'erreur de visualisation.
        $rows = []; 
    } else {
        return false; // Format non géré
    }
    
    $processedData = [];
    // Champs requis pour la validation d'une ligne
    $requiredFields = ['type_equipement', 'nom_equipement', 'numero_inventaire', 'numero_serie', 'duree_amortissement', 'statut'];
    $lineNumber = 2; // Démarrer à 2 pour correspondre aux lignes Excel/CSV

    foreach ($rows as $row) {
        // Remplacer les champs vides ou nulls par null (pour la DB)
        $cleanRow = array_map(function($value) {
            return (is_string($value) && trim($value) === '') || $value === '' ? null : $value;
        }, $row);
        
        // Ajouter les champs par défaut
        $cleanRow['creer_par'] = $modifierPar;
        $cleanRow['date_creation'] = $dateCreation; 
        
        // Validation basique
        $isValid = true;
        foreach ($requiredFields as $field) {
            // Vérifie si le champ est vide ou null (sauf si la valeur est 0, qui est numérique)
            if (!isset($cleanRow[$field]) || (empty($cleanRow[$field]) && !is_numeric($cleanRow[$field]))) {
                $isValid = false;
                break;
            }
        }

        $processedData[] = [
            'data' => $cleanRow,
            'is_valid' => $isValid,
            'line' => $lineNumber++
        ];
    }

    return $processedData;
}


// --- 2. Traitement des actions (Upload, Import, Annulation) ---

// a) Importation des données
if (isset($_POST['action']) && $_POST['action'] === 'import_data' && isset($_SESSION['import_data'])) {
    
    $dataToImport = $_SESSION['import_data'];
    $validRecords = array_filter($dataToImport, function($item) {
        return $item['is_valid'];
    });

    if (empty($validRecords)) {
        $message = "<div class='alert alert-warning'>{$T['msg_no_data']}</div>";
    } else {
        // On récupère l'ordre exact des colonnes à partir du premier enregistrement valide
        $columns = array_keys($validRecords[array_key_first($validRecords)]['data']); 
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnNames = implode(', ', $columns);

        $sql = "INSERT INTO t_actif ({$columnNames}) VALUES ({$placeholders})";
        
        $importedCount = 0;

        try {
            // Assurez-vous que $pdo est bien l'objet PDO de votre db_connexion.php
            $pdo->beginTransaction();
            $stmt = $pdo->prepare($sql);

            foreach ($validRecords as $record) {
                $values = array_values($record['data']);
                
                // Ajuster les valeurs pour PDO: s'assurer que les valeurs vides ou 'NULL' sont null PHP
                $pdoValues = array_map(function($value) {
                    return (is_string($value) && trim($value) === '') || $value === '' || strtolower($value) === 'null' ? null : $value;
                }, $values);
                
                $stmt->execute($pdoValues);
                $importedCount++;
            }
            
            $pdo->commit();
            $message = "<div class='alert alert-success'>" . sprintf($T['msg_success_import'], $importedCount) . "</div>";
            unset($_SESSION['import_data']); // Nettoyer la session après succès

        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "<div class='alert alert-danger'>{$T['msg_error_db_transaction']}" . $e->getMessage() . "</div>";
        }
    }
} 
// b) Annulation / Réinitialisation
else if (isset($_POST['action']) && $_POST['action'] === 'cancel_import') {
    unset($_SESSION['import_data']);
    $message = "<div class='alert alert-info'>{$T['msg_import_canceled']}</div>";
}
// c) Upload et Lecture du Fichier (Visualisation)
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
    
    $fileTmpPath    = $_FILES['import_file']['tmp_name'];
    $fileName       = $_FILES['import_file']['name'];
    $fileSize       = $_FILES['import_file']['size'];
    $fileType       = $_FILES['import_file']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    $allowedfileExtensions = ['csv', 'xlsx'];

    if (in_array($fileExtension, $allowedfileExtensions)) {
        
        // Lire et traiter les données
        $data = readImportFile($fileTmpPath, $fileExtension, $db_columns, $modifier_par, $date_creation);

        if ($data !== false && !empty($data)) {
            $_SESSION['import_data'] = $data;
            $message = "<div class='alert alert-info'>{$T['msg_success_upload']}</div>";
        } else {
            // Affiche le message d'erreur si aucune donnée n'a pu être lue (y compris pour les .xlsx non supportés)
            $message = "<div class='alert alert-warning'>{$T['msg_no_data']}</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>{$T['msg_invalid_format']}</div>";
    }
}
// d) Erreur d'upload
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file']) && $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    $message = "<div class='alert alert-danger'>{$T['msg_upload_error']}</div>";
}

$dataToDisplay = $_SESSION['import_data'] ?? null;
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $T['title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .container { margin-top: 20px; }
        .table-responsive { max-height: 500px; overflow-y: auto; }
         /* Style pour marquer les lignes invalides */
        .table-danger { background-color: #f8d7da !important; } 
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= $T['header'] ?></h1>
        <a href="liste_gest_actif.php" class="btn btn-secondary"><i class="bi bi-list"></i> Retour à la Liste</a>
    </div>

    <?= $message ?>

    <div class="alert alert-light border">
        <strong><?= $T['required_cols'] ?></strong>
        <p class="mb-0 small fst-italic"><?= $T['template_cols'] ?></p>
    </div>

    <?php if (!$dataToDisplay): ?>
    
    <div class="card mb-4">
        <div class="card-header bg-info text-white"><?= $T['select_file'] ?></div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="import_file" class="form-label"><?= $T['select_file'] ?></label>
                    <input class="form-control" type="file" id="import_file" name="import_file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required>
                </div>
                <button type="submit" class="btn btn-primary" name="action_upload" value="upload">
                    <i class="bi bi-eye"></i> <?= $T['button_view'] ?>
                </button>
            </form>
        </div>
    </div>

    <?php else: 
    
    // --- PHASE 2 & 3: Aperçu et Importation ---
    $validCount = count(array_filter($dataToDisplay, fn($item) => $item['is_valid']));
    $invalidCount = count($dataToDisplay) - $validCount;
    ?>
    
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark"><?= $T['section_data_preview'] ?></div>
        <div class="card-body">
            <p>
                <strong>Validé :</strong> <span class="badge bg-success"><?= $validCount ?></span> |
                <strong>Invalide :</strong> <span class="badge bg-danger"><?= $invalidCount ?></span> |
                **Total :** <span class="badge bg-secondary"><?= count($dataToDisplay) ?></span>
            </p>

            <div class="table-responsive">
                <table class="table table-sm table-striped table-bordered align-middle">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th scope="col" style="width: 50px;"><?= $T['header_row'] ?></th>
                            <th scope="col" style="width: 80px;"><?= $T['header_status'] ?></th>
                            <?php 
                            // Colonnes de la DB + la colonne 'date_creation' pour l'affichage
                            $display_columns = array_merge($db_columns, ['date_creation']);
                            foreach ($display_columns as $col): 
                                // Utiliser la clé de traduction si elle existe, sinon le nom de la colonne
                            ?>
                                <th scope="col"><?= htmlspecialchars($T['col_' . $col] ?? $col) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dataToDisplay as $item): ?>
                        <tr class="<?= $item['is_valid'] ? '' : 'table-danger' ?>">
                            <td class="text-center"><?= $item['line'] ?></td>
                            <td><?= $item['is_valid'] ? $T['preview_status_valid'] : $T['preview_status_invalid'] ?></td>
                            <?php 
                                $displayData = $item['data'];
                                foreach ($display_columns as $key): 
                            ?>
                                <td><?= htmlspecialchars($displayData[$key] ?? 'NULL') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <form method="POST" class="mt-4 d-flex justify-content-between">
                <button type="submit" class="btn btn-success btn-lg" name="action" value="import_data" <?= $validCount === 0 ? 'disabled' : '' ?>>
                    <i class="bi bi-box-arrow-in-down"></i> <?= $T['button_import'] ?> (<?= $validCount ?>)
                </button>
                <button type="submit" class="btn btn-secondary btn-lg" name="action" value="cancel_import">
                    <i class="bi bi-x-circle"></i> <?= $T['button_cancel'] ?>
                </button>
            </form>

        </div>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Inclure le fichier de connexion (Assurez-vous qu'il est correct et accessible)
require_once 'db_connexion.php'; 

// Démarrer la session pour le cache, la langue et les infos utilisateur
session_start();

// --- GESTION DE LA LANGUE ET DE L'UTILISATEUR CONNECTÉ ---
$current_lang = $_SESSION['lang'] ?? 'fr';
$modifier_par = $_SESSION['user_nom'] ?? 'Système/Admin Inconnu';
$user_profil = $_SESSION['user_profil'] ?? '';
$user_entite = $_SESSION['user_entite'] ?? 'Bureau Inconnu'; // Valeur par défaut si non définie

// **DÉBUT DU BLOC DE CORRECTION DE L'ENTITÉ (Bureau)**
// Si le bureau est "Bureau Inconnu" et que nous avons un nom d'utilisateur, essayons de le charger depuis la DB.
if ($user_entite === 'Bureau Inconnu' && $modifier_par !== 'Système/Admin Inconnu') {
    try {
        // Préparer la requête pour récupérer l'entité basée sur le nom_complet
        $stmt = $pdo->prepare("SELECT entite FROM t_users WHERE nom_complet = :nom_complet LIMIT 1");
        // NOTE : On utilise $modifier_par qui contient $_SESSION['user_nom']
        $stmt->execute([':nom_complet' => $modifier_par]);
        $entite_from_db = $stmt->fetchColumn();

        if ($entite_from_db) {
            // Mettre à jour la variable locale et la session pour les futures pages
            $user_entite = $entite_from_db;
            $_SESSION['user_entite'] = $entite_from_db;
        }
    } catch (PDOException $e) {
        // En cas d'erreur DB, on maintient la valeur "Bureau Inconnu"
        error_log("Erreur de récupération d'entité pour l'utilisateur: " . $e->getMessage());
    }
}
// **FIN DU BLOC DE CORRECTION DE L'ENTITÉ**

$is_admin_profil = ($user_profil === 'Administration'); 


$message = '';

// --- Dictionnaire de traduction (Correction des espaces insécables) ---
$texts = [
    'fr' => [
        // Status DB values (maintenu en français pour la DB)
        'status_service_db' => 'En service',
        'status_stock_db' => 'En stock',
        // Status display values (utilisé pour l'affichage)
        'status_service_db_display' => 'En service',
        'status_stock_db_display' => 'En stock', // UNIQUE OPTION pour l'ajout
        
        // Titles and Headers
        'title_prefix' => 'Ajouter un Actif',
        'header' => 'Nouvel Actif',
        'back_to_list' => 'Retour à la Liste',
        // Sections
        'section_general' => 'Informations Générales',
        'section_management' => 'Gestion & Amortissement',
        'section_status_assign' => 'Statut et Affectation',
        'section_history_comment' => 'Commentaire Initial (Raisons d\'affectation/stock)',
        
        // Labels
        'type_equipement' => 'Type d\'Équipement',
        'option_select' => 'Sélectionner...',
        'required_feedback_type' => 'Veuillez sélectionner le type d\'équipement.',
        'marque' => 'Marque',
        'required_feedback_marque' => 'La marque est requise.', // Nouveau
        'specification' => 'Spécification',
        'required_feedback_spec' => 'La spécification est requise.', // Nouveau
        'numero_inventaire' => 'N° Inventaire',
        'required_feedback_inventaire' => 'Le numéro d\'inventaire est requis.',
        'numero_serie' => 'N° Série',
        'required_feedback_serie' => 'Le numéro de série est requis.', // Nouveau
        'adresse_mac' => 'Adresse MAC',
        'date_achat' => 'Date d\'Achat',
        'duree_amortissement' => 'Durée Amortissement (Ans)',
        'required_feedback_amortissement' => 'La durée d\'amortissement est requise.',
        'statut_actuel' => 'Statut Initial',
        'required_feedback_statut' => 'Le statut est requis.',
        'date_service' => 'Date de Mise en Service',
        'bureau' => 'Bureau / Localité',
        'option_not_assigned' => 'Non Affecté (Stock)',
        'affecter_a' => 'Affecté à (Staff)',
        'option_select_first' => 'Sélectionner une Localité d\'abord',
        'commentaire_actuel' => 'Commentaire Actuel (Affiché sur la fiche)',
        'history_comment_desc' => 'Décrivez brièvement le contexte (ex: Mise en service, ajout au stock):',
        'required_feedback_history' => 'Le commentaire initial est requis.',
        'button_save' => 'Enregistrer le Nouvel Actif',

        // Messages & Feedback
        'error_db_general' => 'Erreur de base de données : ',
        'error_db_transaction' => 'Erreur de transaction : ',
        'error_assignment_restriction' => '🚫 **Erreur d\'affectation :** Le staff **%s** détient déjà un autre actif dont le statut est \'%s\'.', 
        'error_duplicate_inventory' => '🚫 **Erreur d\'inventaire :** Le numéro d\'inventaire **%s** existe déjà dans le système.',
        'success' => '✅ L\'actif **%s** a été ajouté avec succès.',
        'email_notification_sent' => '📧 Notification d\'affectation envoyée par email au staff concerné et aux adresses en copie.', 
        'no_comment' => 'Aucun commentaire.',
        
        // Email 
        'email_subject' => 'NOTIFICATION : Affectation d\'Actif %s (%s)', 
        'email_h2' => 'Nouvelle Affectation d\'Actif',
        'email_greeting' => 'Bonjour %s,',
        'email_body1' => 'L\'actif **%s** vous a été affecté et son statut est passé à **\'%s\'**. Voici les détails :',
        'email_table_equipment' => 'Équipement :',
        'email_table_inventory' => 'N° Inventaire :',
        'email_table_office' => 'Bureau :',
        'email_table_service_date' => 'Date Service :',
        'email_body2' => 'Veuillez en accuser réception.',
        'email_footer' => 'Ceci est un message automatique de l\'outil de Gestion des Actifs. Merci de ne pas répondre.',
    ],
    'en' => [
        'status_service_db' => 'En service',
        'status_stock_db' => 'En stock',
        'status_service_db_display' => 'In service',
        'status_stock_db_display' => 'In stock',
        
        'title_prefix' => 'Add Asset',
        'header' => 'New Asset',
        'back_to_list' => 'Back to List',
        'section_general' => 'General Information',
        'section_management' => 'Management & Depreciation',
        'section_status_assign' => 'Status and Assignment',
        'section_history_comment' => 'Initial Comment (Reason for assignment/stock)',
        
        'type_equipement' => 'Equipment Type',
        'option_select' => 'Select...',
        'required_feedback_type' => 'Please select the equipment type.',
        'marque' => 'Brand',
        'required_feedback_marque' => 'Brand is required.', // Nouveau
        'specification' => 'Specification',
        'required_feedback_spec' => 'Specification is required.', // Nouveau
        'numero_inventaire' => 'Inventory N°',
        'required_feedback_inventaire' => 'The inventory number is required.',
        'numero_serie' => 'Serial N°',
        'required_feedback_serie' => 'The serial number is required.', // Nouveau
        'adresse_mac' => 'MAC Address',
        'date_achat' => 'Purchase Date',
        'duree_amortissement' => 'Depreciation Period (Years)',
        'required_feedback_amortissement' => 'The depreciation period is required.',
        'statut_actuel' => 'Initial Status',
        'required_feedback_statut' => 'The status is required.',
        'date_service' => 'In-Service Date',
        'bureau' => 'Office / Location',
        'option_not_assigned' => 'Not Assigned (Stock)',
        'affecter_a' => 'Assigned to (Staff)',
        'option_select_first' => 'Select an Office first',
        'commentaire_actuel' => 'Current Comment (Displayed on the sheet)',
        'history_comment_desc' => 'Briefly describe the context (e.g., In-service, added to stock):',
        'required_feedback_history' => 'The initial comment is required.',
        'button_save' => 'Save New Asset',
        
        'error_db_general' => 'Database error: ',
        'error_db_transaction' => 'Transaction error: ',
        'error_assignment_restriction' => '🚫 **Assignment Error:** Staff **%s** already holds another asset with status \'%s\'.',
        'error_duplicate_inventory' => '🚫 **Inventory Error:** Inventory number **%s** already exists in the system.',
        'success' => '✅ Asset **%s** has been added successfully.',
        'email_notification_sent' => '📧 Assignment notification sent by email to the concerned staff and CC addresses.',
        'no_comment' => 'No comment.',

        'email_subject' => 'NOTIFICATION: Asset Assignment %s (%s)',
        'email_h2' => 'New Asset Assignment',
        'email_greeting' => 'Hello %s,',
        'email_body1' => 'Asset **%s** has been assigned to you and its status changed to **\'%s\'**. Here are the details:',
        'email_table_equipment' => 'Equipment:',
        'email_table_inventory' => 'Inventory N°:',
        'email_table_office' => 'Office:',
        'email_table_service_date' => 'Service Date:',
        'email_body2' => 'Please acknowledge receipt.',
        'email_footer' => 'This is an automated message from the Asset Management tool. Please do not reply.',
    ]
];

// Sélectionner les textes dans la langue actuelle
$T = $texts[$current_lang];

// --- Fonctions utilitaires ---
function translateStatusForDisplay($db_status, $T) {
    switch ($db_status) {
        case 'En service': return $T['status_service_db_display'];
        case 'En stock': return $T['status_stock_db_display'];
        default: return $db_status; 
    }
}

// Map avec la seule option "En stock"
$statusOptionsMap = [
    'En stock' => 'status_stock_db_display',
];


// --- 1. RÉCUPÉRATION DES DONNÉES DE CONFIGURATION ---
$deviceTypes = [];
$staffList = []; 

try {
    // 1.1 Charger les types d'équipement (DB locale)
    $stmtTypes = $pdo->query("SELECT description FROM t_device ORDER BY description ASC");
    $deviceTypes = $stmtTypes->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("{$T['error_db_general']}" . $e->getMessage());
}


// --- 2. Traitement du formulaire (POST) pour l'INSERTION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Affectation (staff) et email sont ignorés/laissés vides car l'API est supprimée et le statut est 'En stock'
    $staff_name_final = null; 
    $staff_email_final = null;

    // Affectation automatique du bureau avec l'entité de l'utilisateur
    $bureau_final = $user_entite; 

    // Récupération des données du formulaire
    $data = [
        'type_equipement' => $_POST['type_equipement'] ?? null,
        'nom_equipement' => $_POST['marque_equipement'] ?? null, 
        'specification' => $_POST['specification'] ?? null, 
        'numero_serie' => $_POST['numero_serie'] ?? null, 
        'numero_inventaire' => $_POST['numero_inventaire'] ?? null,
        'adresse_mac' => $_POST['adresse_mac'] ?? null,
        'date_achat' => empty($_POST['date_achat']) ? null : $_POST['date_achat'],
        'duree_amortissement' => $_POST['duree_amortissement'] ?? null,
        'statut' => $T['status_stock_db'], // Forcé à 'En stock'
        'date_service' => null, // Forcé à NULL car le statut est 'En stock'
        'bureau' => $bureau_final, // Utilisation de la valeur de l'entité
        'affecter_a' => $staff_name_final, // Forcé à NULL
        'commentaire' => $_POST['commentaire'] ?? null,
        'commentaire_historique' => $_POST['commentaire_historique'] ?? null, 
        'creer_par' => $modifier_par,
    ];

    $new_statut = $data['statut'];
    $numero_inventaire = $data['numero_inventaire'];
    
    // Les valeurs finales (nulles) sont prêtes pour l'insertion
    $final_date_service = $data['date_service']; 
    $final_bureau = $data['bureau']; 
    $final_affecter_a = $data['affecter_a']; 
    
    // 3. Validation des champs obligatoires (en plus de la validation HTML5)
    if (empty($data['nom_equipement']) || empty($data['specification']) || empty($data['numero_serie']) || empty($data['type_equipement']) || empty($data['numero_inventaire']) || empty($data['duree_amortissement']) || empty($data['commentaire_historique'])) {
        // Cette vérification est redondante si la validation HTML5 est bien gérée, mais sécurise
        // le cas où un utilisateur désactiverait JS. Le message d'erreur est générique ici.
        // Si $user_entite est 'Bureau Inconnu', l'enregistrement n'aura pas la bonne valeur, mais le formulaire n'est pas bloqué.
        if (empty($message)) {
            // Afficher une erreur si le formulaire n'est pas rempli correctement
            $message = "<div class='alert alert-danger'>Veuillez remplir tous les champs obligatoires.</div>";
            goto end_of_post_treatment; 
        }
    }
    
    // 3.1 Vérification de l'unicité du Numéro d'Inventaire
    try {
        $stmtCheckInv = $pdo->prepare("SELECT COUNT(*) FROM t_actif WHERE numero_inventaire = ?");
        $stmtCheckInv->execute([$numero_inventaire]);
        if ($stmtCheckInv->fetchColumn() > 0) {
            $message = "<div class='alert alert-danger'>" . sprintf($T['error_duplicate_inventory'], htmlspecialchars($numero_inventaire)) . "</div>";
            goto end_of_post_treatment; 
        }
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>{$T['error_db_general']}" . $e->getMessage() . "</div>";
        goto end_of_post_treatment; 
    }

    // 4. Exécution de l'INSERTION (Transaction)
    try {
        $pdo->beginTransaction();

        // Insertion dans t_actif
        $sql_insert_actif = "
            INSERT INTO t_actif (
                type_equipement, nom_equipement, specification,
                numero_serie, numero_inventaire, 
                adresse_mac, date_achat, 
                duree_amortissement, statut, date_service, 
                bureau, affecter_a, commentaire, creer_par
            ) VALUES (
                :type_equipement, :nom_equipement, :specification,
                :numero_serie, :numero_inventaire, 
                :adresse_mac, :date_achat, 
                :duree_amortissement, :statut, :date_service, 
                :bureau, :affecter_a, :commentaire, :creer_par
            )
        ";
        $stmt_insert_actif = $pdo->prepare($sql_insert_actif);
        $stmt_insert_actif->execute([
            ':type_equipement' => $data['type_equipement'], 
            ':nom_equipement' => $data['nom_equipement'],
            ':specification' => $data['specification'], 
            ':numero_serie' => $data['numero_serie'], 
            ':numero_inventaire' => $data['numero_inventaire'], 
            ':adresse_mac' => $data['adresse_mac'], 
            ':date_achat' => $data['date_achat'], 
            ':duree_amortissement' => $data['duree_amortissement'], 
            ':statut' => $data['statut'], 
            ':date_service' => $final_date_service, 
            ':bureau' => $final_bureau, // Utilise $user_entite, maintenant rechargé si nécessaire
            ':affecter_a' => $final_affecter_a, 
            ':commentaire' => $data['commentaire'],
            ':creer_par' => $data['creer_par']
        ]);
        
        $newActifId = $pdo->lastInsertId();

        // Insertion dans t_historique_actif pour le statut initial
        if (!empty(trim($data['commentaire_historique'] ?? ''))) {
            $sql_history = "
                INSERT INTO t_historique_actif (
                    Id_actif_original, ancien_statut, nouveau_statut, ancien_affectation, 
                    nouvelle_affectation, commentaire_changement, date_historique, creer_par
                ) VALUES (:id, :ancien_statut, :nouveau_statut, :ancien_affectation, 
                            :nouvelle_affectation, :commentaire_changement, NOW(), :creer_par) 
            ";
            $stmt_history = $pdo->prepare($sql_history);
            $stmt_history->execute([
                ':id' => $newActifId, 
                ':ancien_statut' => 'NOUVEAU',
                ':nouveau_statut' => $new_statut, 
                ':ancien_affectation' => 'N/A',
                ':nouvelle_affectation' => $final_affecter_a, 
                ':commentaire_changement' => $data['commentaire_historique'], 
                ':creer_par' => $data['creer_par']
            ]);
        }
        
        $pdo->commit();

        $message = "<div class='alert alert-success'>" . sprintf($T['success'], htmlspecialchars($data['numero_inventaire'])) . "</div>";
        
        // Vider $_POST après succès pour vider le formulaire
        unset($_POST);


    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger'>{$T['error_db_transaction']}" . $e->getMessage() . "</div>";
    }
}
end_of_post_treatment:
$form_data = $_POST ?? [];

?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $T['title_prefix']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .container { margin-top: 20px; }
        .form-label.required::after { content: " *"; color: red; } 
    </style>
</head>
<body>

<div class="container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $T['header']; ?></h1>
        <a href="liste_gest_actif.php" class="btn btn-secondary"><?php echo $T['back_to_list']; ?></a>
    </div>

    <?= $message ?>

    <form method="POST" class="needs-validation" novalidate>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white"><?php echo $T['section_general']; ?></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="type_equipement" class="form-label required"><?php echo $T['type_equipement']; ?></label>
                        <select class="form-select" id="type_equipement" name="type_equipement" required>
                            <option value=""><?php echo $T['option_select']; ?></option>
                            <?php 
                            $selectedType = $form_data['type_equipement'] ?? '';
                            foreach ($deviceTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>" 
                                    <?= $selectedType == $type ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?php echo $T['required_feedback_type']; ?></div>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="marque_equipement" class="form-label required"><?php echo $T['marque']; ?></label>
                        <input type="text" class="form-control" id="marque_equipement" name="marque_equipement" required
                               value="<?= htmlspecialchars($form_data['marque_equipement'] ?? '') ?>"> 
                        <div class="invalid-feedback"><?php echo $T['required_feedback_marque']; ?></div>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="specification" class="form-label required"><?php echo $T['specification']; ?></label>
                        <input type="text" class="form-control" id="specification" name="specification" required
                               value="<?= htmlspecialchars($form_data['specification'] ?? '') ?>">
                        <div class="invalid-feedback"><?php echo $T['required_feedback_spec']; ?></div>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="numero_inventaire" class="form-label required"><?php echo $T['numero_inventaire']; ?></label>
                        <input type="text" class="form-control" id="numero_inventaire" name="numero_inventaire" required
                               value="<?= htmlspecialchars($form_data['numero_inventaire'] ?? '') ?>">
                        <div class="invalid-feedback"><?php echo $T['required_feedback_inventaire']; ?></div>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="numero_serie" class="form-label required"><?php echo $T['numero_serie']; ?></label>
                        <input type="text" class="form-control" id="numero_serie" name="numero_serie" required
                               value="<?= htmlspecialchars($form_data['numero_serie'] ?? '') ?>">
                        <div class="invalid-feedback"><?php echo $T['required_feedback_serie']; ?></div>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="adresse_mac" class="form-label"><?php echo $T['adresse_mac']; ?></label>
                        <input type="text" class="form-control" id="adresse_mac" name="adresse_mac" 
                               value="<?= htmlspecialchars($form_data['adresse_mac'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white"><?php echo $T['section_management']; ?></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="date_achat" class="form-label"><?php echo $T['date_achat']; ?></label>
                        <input type="date" class="form-control" id="date_achat" name="date_achat" 
                               value="<?= htmlspecialchars($form_data['date_achat'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="duree_amortissement" class="form-label required"><?php echo $T['duree_amortissement']; ?></label>
                        <input type="number" class="form-control" id="duree_amortissement" name="duree_amortissement" required min="1"
                               value="<?= htmlspecialchars($form_data['duree_amortissement'] ?? '') ?>">
                        <div class="invalid-feedback"><?php echo $T['required_feedback_amortissement']; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-success text-white"><?php echo $T['section_status_assign']; ?></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="statut" class="form-label required"><?php echo $T['statut_actuel']; ?></label>
                        <select class="form-select" id="statut" name="statut" required>
                            <?php 
                            $db_value = 'En stock';
                            $display_key = 'status_stock_db_display';
                            ?>
                                <option value="<?= htmlspecialchars($db_value) ?>" selected>
                                    <?= htmlspecialchars($T[$display_key]) ?>
                                </option>
                            </select>
                        <div class="invalid-feedback"><?php echo $T['required_feedback_statut']; ?></div>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="date_service" class="form-label"><?php echo $T['date_service']; ?></label>
                        <input type="date" class="form-control" id="date_service" name="date_service" 
                               value="<?= htmlspecialchars($form_data['date_service'] ?? '') ?>" disabled>
                    </div>

                    <div class="col-md-4">
                        <label for="bureau" class="form-label"><?php echo $T['bureau']; ?></label>
                        <input type="text" class="form-control" id="bureau" name="bureau"
                               value="<?= htmlspecialchars($user_entite) ?>" readonly>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="affecter_a" class="form-label"><?php echo $T['affecter_a']; ?></label>
                        <select class="form-select" id="affecter_a" name="affecter_a" disabled>
                            <option value="" selected><?php echo $T['option_not_assigned']; ?></option>
                            </select>
                    </div>
                </div>
                
                <div class="row g-3 mt-3">
                    <div class="col-12">
                        <label for="commentaire" class="form-label"><?php echo $T['commentaire_actuel']; ?></label>
                        <textarea class="form-control" id="commentaire" name="commentaire" rows="2"><?= htmlspecialchars($form_data['commentaire'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-dark"><?php echo $T['section_history_comment']; ?></div>
            <div class="card-body">
                <div class="col-12">
                    <label for="commentaire_historique" class="form-label required"><?php echo $T['history_comment_desc']; ?></label>
                    <textarea class="form-control" id="commentaire_historique" name="commentaire_historique" rows="2" required><?= htmlspecialchars($form_data['commentaire_historique'] ?? '') ?></textarea>
                    <div class="invalid-feedback"><?php echo $T['required_feedback_history']; ?></div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 mb-5">
            <button type="submit" class="btn btn-primary btn-lg"><?php echo $T['button_save']; ?></button>
        </div>
    </form>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Activer la validation Bootstrap
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })();
    });
</script>
</body>
</html>
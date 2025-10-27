<?php
// Inclure le fichier de connexion (Assurez-vous qu'il est correct et accessible)
require_once 'db_connexion.php'; 

// --- NOUVEAU : Inclure les classes PHPMailer (PRÉ-REQUIS) ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Assurez-vous que le chemin vers les fichiers PHPMailer est correct par rapport à votre script
// VOUS DEVEZ AVOIR CES FICHIERS DISPONIBLES :
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';


// Démarrer la session pour le cache, la langue et les infos utilisateur
session_start();

// --- GESTION DE LA LANGUE ET DE L'UTILISATEUR CONNECTÉ ---
$current_lang = $_SESSION['lang'] ?? 'fr';
$modifier_par = $_SESSION['user_nom'] ?? 'Système/Admin Inconnu';
$user_profil = $_SESSION['user_profil'] ?? '';
$is_admin_profil = ($user_profil === 'Administration');


$message = '';
$actifId = 0; // Initialisation

// --- Token d'API (Nécessaire pour l'appel AJAX) ---
// Ce token doit être valide pour la route getUsersForInventory
$authToken = '9f800a7213bf7292f0e0d05156f4e906e176a032c99abb59c027511246df721e023b0aa19494c38abfbb5eda155bdab7522041cead0524d8f6804eaa47b4365d7995620f67306bf031cd8139be9927707e3bf453264b306bd285bca017e95080e4faaa1892bc0ea0c76d158fc1b05fb8d94f142d599eda2512597ce58e11c2e8'; 

// --- Dictionnaire de traduction ---
$texts = [
    'fr' => [
        // Status DB values (must remain in French as they are stored in the DB, only for PHP logic/value)
        'status_service_db' => 'En service',
        'status_stock_db' => 'En stock',
        'status_repair_db' => 'En réparation',
        'status_stolen_db' => 'Volé',
        'status_decommissioned_db' => 'Déclassé',
        'status_out_of_service_db' => 'Hors service',
        'status_depreciated_db' => 'Amorti',
        // Status display values (used for the visible text in <option> and in emails/messages)
        'status_service_db_display' => 'En service',
        'status_stock_db_display' => 'En stock',
        'status_repair_db_display' => 'En réparation',
        'status_stolen_db_display' => 'Volé',
        'status_decommissioned_db_display' => 'Déclassé',
        'status_out_of_service_db_display' => 'Hors service',
        'status_depreciated_db_display' => 'Amorti',
        
        // Titles and Headers
        'title_prefix' => 'Modifier Actif: ',
        'header' => 'Modification Actif: ',
        'back_to_list' => 'Retour à la Liste',
        'admin_warning_title' => 'Profil Administration :',
        'admin_warning_text' => 'Certains champs liés au statut et à l\'affectation sont en lecture seule.',
        
        // Sections
        'section_general' => 'Informations Générales',
        'section_management' => 'Gestion & Amortissement',
        'section_status_assign' => 'Statut et Affectation',
        'section_history_comment' => 'Commentaire pour l\'Historique (Obligatoire si statut/affectation change)',
        
        // Labels
        'type_equipement' => 'Type d\'Équipement',
        'option_select' => 'Sélectionner...',
        'required_feedback_type' => 'Veuillez sélectionner le type d\'équipement.',
        'marque' => 'Marque',
        'specification' => 'Spécification',
        'numero_inventaire' => 'N° Inventaire',
        'required_feedback_inventaire' => 'Le numéro d\'inventaire est requis.',
        'numero_serie' => 'N° Série',
        'adresse_mac' => 'Adresse MAC',
        'date_achat' => 'Date d\'Achat',
        'duree_amortissement' => 'Durée Amortissement (Ans)',
        'required_feedback_amortissement' => 'La durée d\'amortissement est requise.',
        'statut_actuel' => 'Statut Actuel',
        'required_feedback_statut' => 'Le statut est requis.',
        'date_service' => 'Date de Mise en Service',
        'bureau' => 'Bureau / Localité',
        'option_not_assigned' => 'Non Affecté (Stock)',
        'affecter_a' => 'Affecté à (Staff)',
        'option_select_first' => 'Sélectionner un Bureau d\'abord',
        'commentaire_actuel' => 'Commentaire Actuel (Affiché sur la fiche)',
        'history_comment_desc' => 'Décrivez brièvement le changement:',
        'required_feedback_history' => 'Le commentaire historique est requis lors d\'un changement de statut ou d\'affectation.',
        'button_save' => 'Enregistrer les Modifications',

        // History Table
        'history_title' => 'Historique des Changements (Actif N°: ',
        'history_details' => 'Détails de l\'Historique (',
        'history_entries' => ' entrées)',
        'history_download_btn' => 'Télécharger en Pdf',
        'history_date' => 'Date',
        'history_status' => 'Statut',
        'history_assignment' => 'Affectation',
        'history_comment' => 'Commentaire',
        'history_modified_by' => 'Modifié par',
        'history_no_entry' => 'Aucun historique enregistré pour cet actif.',
        'history_status_na' => 'Statut N/A',
        'history_assignment_old' => ' (Ancienne Affectation)',

        // Messages & Feedback
        'error_db_general' => 'Erreur de base de données : ',
        'error_db_transaction' => 'Erreur de transaction : ',
        'error_assignment_restriction' => '🚫 **Erreur d\'affectation :** Le staff **%s** détient déjà un autre actif dont le statut est \'%s\'.', 
        'success' => '✅ L\'actif **%s** a été mis à jour avec succès.',
        'email_notification_sent' => '📧 Notification d\'affectation envoyée par email au staff concerné et aux adresses en copie.',
        'no_comment' => 'Aucun commentaire.',
        'error_api_fetch' => 'Erreur: Impossible de récupérer les données du personnel depuis l\'API. Les champs d\'affectation sont désactivés.',
        'loading_data' => 'Chargement des données...',
        'error_loading_staff' => 'Erreur lors du chargement du staff pour ce bureau.',
        
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
    // ... (Ajouter la version 'en' ou autre si nécessaire)
    'en' => [
        // Status DB values (must remain in French as they are stored in the DB, only for PHP logic/value)
        'status_service_db' => 'En service',
        'status_stock_db' => 'En stock',
        'status_repair_db' => 'In repair',
        'status_stolen_db' => 'Stolen',
        'status_decommissioned_db' => 'Decommissioned',
        'status_out_of_service_db' => 'Out of service',
        'status_depreciated_db' => 'Depreciated',
        // Status display values
        'status_service_db_display' => 'In service',
        'status_stock_db_display' => 'In stock',
        'status_repair_db_display' => 'In repair',
        'status_stolen_db_display' => 'Stolen',
        'status_decommissioned_db_display' => 'Decommissioned',
        'status_out_of_service_db_display' => 'Out of service',
        'status_depreciated_db_display' => 'Depreciated',
        
        // Titles and Headers
        'title_prefix' => 'Edit Asset: ',
        'header' => 'Asset Modification: ',
        'back_to_list' => 'Back to List',
        'admin_warning_title' => 'Administration Profile:',
        'admin_warning_text' => 'Some fields related to status and assignment are read-only.',

        // Sections
        'section_general' => 'General Information',
        'section_management' => 'Management & Depreciation',
        'section_status_assign' => 'Status and Assignment',
        'section_history_comment' => 'Comment for History (Required if status/assignment changes)',
        
        // Labels
        'type_equipement' => 'Equipment Type',
        'option_select' => 'Select...',
        'required_feedback_type' => 'Please select the equipment type.',
        'marque' => 'Brand',
        'specification' => 'Specification',
        'numero_inventaire' => 'Inventory N°',
        'required_feedback_inventaire' => 'The inventory number is required.',
        'numero_serie' => 'Serial N°',
        'adresse_mac' => 'MAC Address',
        'date_achat' => 'Purchase Date',
        'duree_amortissement' => 'Depreciation Period (Years)',
        'required_feedback_amortissement' => 'The depreciation period is required.',
        'statut_actuel' => 'Current Status',
        'required_feedback_statut' => 'The status is required.',
        'date_service' => 'In-Service Date',
        'bureau' => 'Office / Location',
        'option_not_assigned' => 'Not Assigned (Stock)',
        'affecter_a' => 'Assigned to (Staff)',
        'option_select_first' => 'Select an Office first',
        'commentaire_actuel' => 'Current Comment (Displayed on the sheet)',
        'history_comment_desc' => 'Briefly describe the change:',
        'required_feedback_history' => 'The historical comment is required upon a status or assignment change.',
        'button_save' => 'Save Changes',

        // History Table
        'history_title' => 'Change History (Asset N°: ',
        'history_details' => 'History Details (',
        'history_entries' => ' entries)',
        'history_download_btn' => 'Download in Pdf',
        'history_date' => 'Date',
        'history_status' => 'Status',
        'history_assignment' => 'Assignment',
        'history_comment' => 'Comment',
        'history_modified_by' => 'Modified by',
        'history_no_entry' => 'No history recorded for this asset.',
        'history_status_na' => 'Status N/A',
        'history_assignment_old' => ' (Old Assignment)',

        // Messages & Feedback
        'error_db_general' => 'Database error: ',
        'error_db_transaction' => 'Transaction error: ',
        'error_assignment_restriction' => '🚫 **Assignment Error:** Staff **%s** already holds another asset with status \'%s\'.',
        'success' => '✅ Asset **%s** has been updated successfully.',
        'email_notification_sent' => '📧 Assignment notification sent by email to the concerned staff and CC addresses.',
        'no_comment' => 'No comment.',
        'error_api_fetch' => 'Error: Could not retrieve staff data from the API. Assignment fields are disabled.',
        'loading_data' => 'Loading data...',
        'error_loading_staff' => 'Error loading staff for this office.',

        // Email
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
        case 'En réparation': return $T['status_repair_db_display'];
        case 'Volé': return $T['status_stolen_db_display'];
        case 'Déclassé': return $T['status_decommissioned_db_display'];
        case 'Hors service': return $T['status_out_of_service_db_display'];
        case 'Amorti': return $T['status_depreciated_db_display'];
        default: return $db_status; 
    }
}

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

$statusOptionsMap = [
    'En service' => 'status_service_db_display',
    'En stock' => 'status_stock_db_display',
    'En réparation' => 'status_repair_db_display',
    'Amorti' => 'status_depreciated_db_display',
    'Hors service' => 'status_out_of_service_db_display',
    'Volé' => 'status_stolen_db_display',
    'Déclassé' => 'status_decommissioned_db_display',
];

// --- MIS À JOUR : LISTE D'ORGANISATIONS HARDCODÉE ---
$organisations = [
    'EMS', 'GEO KATANGA', 'IBS', 'IFS', 'ITM ANGOLA LDA', 'ITM BENIN',
    'ITM BURUNDI', 'ITM CAMEROUN', 'ITM CONGO BRAZZAVILLE', 'ITM COTE D\'IVOIRE', 
    'ITM CX', 'ITM ENVIRONNEMENT', 'ITM GABON', 'ITM HOLDING', 'ITM KATOPE PTY', 
    'ITM KENYA LTD', 'ITM MAINTENANCE', 'ITM NEXUS', 'ITM NIGERIA', 
    'ITM RWANDA LTD', 'ITM SARL', 'ITM SENEGAL', 'ITM TANZANIA LTD', 
    'ITM TOGO', 'ITM UGANDA LTD', 'ITM ZAMBIE', 'JAMON', 'KUVULU'
];
sort($organisations); // Tri alphabétique

// --- 1. Vérification et Récupération de l'ID ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: liste_gest_actif.php");
    exit();
}
$actifId = $_GET['id'];

// Initialiser les variables de données
$actif = [];
$historique = [];
$deviceTypes = [];
$staffList = []; // N'est plus chargé au départ

// --- 2. RÉCUPÉRATION DES DONNÉES (DB locale) ---
try {
    // 2.1 Charger les types d'équipement (DB locale)
    $stmtTypes = $pdo->query("SELECT description FROM t_device ORDER BY description ASC");
    $deviceTypes = $stmtTypes->fetchAll(PDO::FETCH_COLUMN);

    // 2.2 Charger l'actif et l'historique (DB locale)
    $stmtActif = $pdo->prepare("SELECT * FROM t_actif WHERE id_actif = ?");
    $stmtActif->execute([$actifId]);
    $actif = $stmtActif->fetch(PDO::FETCH_ASSOC);

    if (!$actif) {
        header("Location: liste_gest_actif.php");
        exit();
    }
    
    $stmtHist = $pdo->prepare("SELECT * FROM t_historique_actif WHERE Id_actif_original = ? ORDER BY date_historique DESC");
    $stmtHist->execute([$actifId]);
    $historique = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
    
    $old_statut = $actif['statut'];
    $old_affecter_a = $actif['affecter_a'];

} catch (PDOException $e) {
    die("{$T['error_db_general']}" . $e->getMessage());
}

// --- FONCTION D'ENVOI D'EMAIL (Inchangée) ---
function sendNotificationEmail($staff_name, $staff_email, $pdo, $data, $numero_inventaire, $T) {
    if (empty($staff_email) || !filter_var($staff_email, FILTER_VALIDATE_EMAIL)) {
        error_log("Tentative d'envoi d'email sans adresse valide pour le staff: " . $staff_name);
        return;
    }

    $mail = new PHPMailer(true);

    try {
        // Paramètres du serveur SMTP (À ADAPTER)
        $mail->isSMTP();
        $mail->Host       = 'mail.pag-tech.net'; // À ADAPTER
        $mail->SMTPAuth   = true;
        $mail->Username   = 'it.services@pag-tech.net'; // À ADAPTER
        $mail->Password   = 'IT_service@2024'; // À ADAPTER
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Destinataires
        $mail->setFrom('it.services@pag-tech.net', 'Gestion des Actifs');
        $mail->addAddress($staff_email, $staff_name);
        
        // Copie (CC)
        $mail->addCC('glodi.nsaka@itmafrica.com'); // À ADAPTER
        $mail->addCC('diglogosen@gmail.com'); // À ADAPTER

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = sprintf($T['email_subject'], $numero_inventaire, $staff_name);
        
        $body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; background-color: #f4f4f4; padding: 20px;'>
                <div style='max-width: 600px; margin: auto; background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05);'>
                    <h2 style='color: #0d6efd;'>{$T['email_h2']}</h2>
                    <p>" . sprintf($T['email_greeting'], $staff_name) . "</p>
                    <p>" . sprintf($T['email_body1'], $numero_inventaire, translateStatusForDisplay($data['statut'], $T)) . "</p>
                    <table style='border-collapse: collapse; width: 100%; margin-top: 15px; background: #fafafa;'>
                        <tr><td style='border: 1px solid #ddd; padding: 8px; font-weight: bold; width: 30%;'>{$T['email_table_equipment']}</td><td style='border: 1px solid #ddd; padding: 8px;'>{$data['type_equipement']} ({$data['nom_equipement']})</td></tr> 
                        <tr><td style='border: 1px solid #ddd; padding: 8px; font-weight: bold; width: 30%;'>{$T['email_table_inventory']}</td><td style='border: 1px solid #ddd; padding: 8px;'>{$numero_inventaire}</td></tr>
                        <tr><td style='border: 1px solid #ddd; padding: 8px; font-weight: bold; width: 30%;'>{$T['email_table_office']}</td><td style='border: 1px solid #ddd; padding: 8px;'>{$data['bureau']}</td></tr>
                        <tr><td style='border: 1px solid #ddd; padding: 8px; font-weight: bold; width: 30%;'>{$T['email_table_service_date']}</td><td style='border: 1px solid #ddd; padding: 8px;'>{$data['date_service']}</td></tr>
                    </table>
                    <p style='margin-top: 20px;'>{$T['email_body2']}</p>
                    <p style='font-size: 0.9em; color: #777;'>{$T['email_footer']}</p>
                </div>
            </body>
            </html>
        ";
        $mail->Body = $body;
        $mail->send();
    } catch (Exception $e) {
        error_log("L'envoi d'email de notification a échoué. Mailer Error: {$mail->ErrorInfo}");
    }
}

// --- 3. Traitement du formulaire (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $actifId > 0) {
    
    // Analyser la valeur de 'affecter_a' pour séparer le nom et l'email
    $affecter_a_post_raw = $_POST['affecter_a'] ?? null;
    $staff_name_final = null;
    $staff_email_final = null;

    // Le champ Affecté à renvoie "NOM COMPLET|EMAIL"
    if (!empty($affecter_a_post_raw) && strpos($affecter_a_post_raw, '|') !== false) {
        // Extraction du nom (stocké dans DB) et de l'email (pour l'envoi)
        list($staff_name_final, $staff_email_final) = explode('|', $affecter_a_post_raw, 2);
    } else {
        // Si la valeur est vide ou non affectée (Non Affecté (Stock))
        $staff_name_final = null; 
        $staff_email_final = null;
    }

    if ($is_admin_profil) {
        // L'admin ne peut modifier que les champs non liés au statut/affectation
        $statut_post = $actif['statut'];
        $date_service_post = $actif['date_service'];
        $bureau_post = $actif['bureau'];
        $affecter_a_post = $actif['affecter_a'];
        $commentaire_post = $actif['commentaire'];
        $commentaire_historique_post = ''; // Pas de log d'historique pour les changements mineurs admin
    } else {
        $statut_post = $_POST['statut'] ?? null;
        $date_service_post = $_POST['date_service'] ?? null;
        $bureau_post = $_POST['bureau'] ?? null;
        $affecter_a_post = $staff_name_final; // On utilise le nom extrait pour la DB
        $commentaire_post = $_POST['commentaire'] ?? null;
        $commentaire_historique_post = $_POST['commentaire_historique'] ?? null;
    }
    
    $data = [
        'type_equipement' => $_POST['type_equipement'] ?? null,
        'nom_equipement' => $_POST['marque_equipement'] ?? null, 
        'specification' => $_POST['specification'] ?? null,
        'numero_serie' => $_POST['numero_serie'] ?? null,
        'numero_inventaire' => $_POST['numero_inventaire'] ?? null,
        'adresse_mac' => $_POST['adresse_mac'] ?? null,
        'date_achat' => $_POST['date_achat'] ?? null,
        'duree_amortissement' => $_POST['duree_amortissement'] ?? null,
        'statut' => $statut_post,
        'date_service' => $date_service_post,
        'bureau' => $bureau_post,
        'affecter_a' => $affecter_a_post,
        'commentaire' => $commentaire_post,
        'commentaire_historique' => $commentaire_historique_post, 
        'modifier_par' => $modifier_par, 
    ];

    $new_statut = $data['statut'];
    
    // Définir les valeurs finales pour la DB en fonction du statut (Logique de l'application)
    $final_date_service = ($new_statut == 'En service') ? $data['date_service'] : null;
    $final_bureau = ($new_statut == 'En service') ? $data['bureau'] : null;
    $final_affecter_a = ($new_statut == 'En service') ? $data['affecter_a'] : null;
    
    // Vérification de la restriction : un staff ne peut avoir qu'un seul actif "En service"
    $should_check_restriction = $new_statut == 'En service' && !empty($final_affecter_a);
    if ($should_check_restriction) {
        $stmtCheck = $pdo->prepare("
            SELECT COUNT(*) FROM t_actif 
            WHERE affecter_a = :affecter_a AND statut = 'En service' AND id_actif != :actifId
        ");
        $stmtCheck->execute([':affecter_a' => $final_affecter_a, ':actifId' => $actifId]);
        $count = $stmtCheck->fetchColumn();

        if ($count > 0) {
            $message = "<div class='alert alert-danger'>" . sprintf($T['error_assignment_restriction'], htmlspecialchars($final_affecter_a), $T['status_service_db_display']) . "</div>";
            goto end_of_post_treatment; 
        }
    }

    // Détermination de la nécessité de logguer l'historique
    $statut_changed = ($new_statut != $old_statut);
    $affectation_changed = ($final_affecter_a != $old_affecter_a);
    $should_log_history = ($statut_changed || $affectation_changed) && (!empty(trim($data['commentaire_historique'] ?? '')) || $is_admin_profil);

    try {
        $pdo->beginTransaction();

        $sql_update = "
            UPDATE t_actif SET 
                type_equipement = :type_equipement, nom_equipement = :nom_equipement, specification = :specification,
                numero_serie = :numero_serie, numero_inventaire = :numero_inventaire, 
                adresse_mac = :adresse_mac, date_achat = :date_achat, 
                duree_amortissement = :duree_amortissement, statut = :statut, date_service = :date_service, 
                bureau = :bureau, affecter_a = :affecter_a, commentaire = :commentaire
            WHERE id_actif = :id_actif
        ";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
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
            ':bureau' => $final_bureau, 
            ':affecter_a' => $final_affecter_a, 
            ':commentaire' => $data['commentaire'],
            ':id_actif' => $actifId
        ]);
        
        if ($should_log_history) {
            $comment_to_log = $data['commentaire_historique'];
            if ($is_admin_profil && !$statut_changed && !$affectation_changed) {
                 $comment_to_log = "Modification des champs non-statut par l'administration.";
            }

            $sql_history = "
                INSERT INTO t_historique_actif (
                    Id_actif_original, ancien_statut, nouveau_statut, ancien_affectation, 
                    nouvelle_affectation, commentaire_changement, date_historique, modifier_par, creer_par 
                ) VALUES (:id, :ancien_statut, :nouveau_statut, :ancien_affectation, 
                          :nouvelle_affectation, :commentaire_changement, NOW(), :modifier_par, :modifier_par) 
            ";
            $stmt_history = $pdo->prepare($sql_history);
            $stmt_history->execute([
                ':id' => $actifId, 
                ':ancien_statut' => $old_statut, 
                ':nouveau_statut' => $new_statut, 
                ':ancien_affectation' => $old_affecter_a, 
                ':nouvelle_affectation' => $final_affecter_a, 
                ':commentaire_changement' => $comment_to_log, 
                ':modifier_par' => $data['modifier_par']
            ]);
        }
        
        $pdo->commit();

        $message = "<div class='alert alert-success'>" . sprintf($T['success'], htmlspecialchars($data['numero_inventaire'])) . "</div>";
        
        // Envoi d'email : si le nouveau statut est 'En service', si le statut a changé, et si nous avons l'email du staff
        if ($new_statut == 'En service' && $statut_changed && !empty($staff_email_final)) {
            sendNotificationEmail($staff_name_final, $staff_email_final, $pdo, $data, $data['numero_inventaire'], $T); 
            $message .= "<div class='alert alert-info'>{$T['email_notification_sent']}</div>";
        }

        // Recharger les données pour la vue
        $stmtActif->execute([$actifId]);
        $actif = $stmtActif->fetch(PDO::FETCH_ASSOC);
        $stmtHist->execute([$actifId]);
        $historique = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger'>{$T['error_db_transaction']}" . $e->getMessage() . "</div>";
    }
}
end_of_post_treatment:
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $T['title_prefix']; ?><?= htmlspecialchars($actif['numero_inventaire'] ?? 'N/A') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .container { margin-top: 20px; }
        .form-label.required::after { content: " *"; color: red; } 
        .form-control:disabled, .form-select:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $T['header']; ?><?= htmlspecialchars($actif['numero_inventaire'] ?? 'N/A') ?></h1>
        <a href="liste_gest_actif.php" class="btn btn-secondary"><?php echo $T['back_to_list']; ?></a>
    </div>

    <?= $message ?>
    
    <?php if ($is_admin_profil): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
             <strong><?php echo $T['admin_warning_title']; ?></strong>
            <?php echo $T['admin_warning_text']; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="needs-validation" novalidate>
        <div class="card mb-4">
             <div class="card-header bg-primary text-white"><?php echo $T['section_general']; ?></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                         <label for="type_equipement" class="form-label required"><?php echo $T['type_equipement']; ?></label>
                        <select class="form-select" id="type_equipement" name="type_equipement" required>
                             <option value=""><?php echo $T['option_select']; ?></option>
                            <?php foreach ($deviceTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>" 
                                    <?= ($actif['type_equipement'] ?? '') == $type ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                         <div class="invalid-feedback"><?php echo $T['required_feedback_type']; ?></div>
                    </div>
                    <div class="col-md-4">
                         <label for="marque_equipement" class="form-label"><?php echo $T['marque']; ?></label>
                        <input type="text" class="form-control" id="marque_equipement" name="marque_equipement" 
                               value="<?= htmlspecialchars($actif['nom_equipement'] ?? '') ?>"> </div>
                    <div class="col-md-4">
                         <label for="specification" class="form-label"><?php echo $T['specification']; ?></label>
                        <input type="text" class="form-control" id="specification" name="specification" 
                               value="<?= htmlspecialchars($actif['specification'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                         <label for="numero_inventaire" class="form-label required"><?php echo $T['numero_inventaire']; ?></label>
                        <input type="text" class="form-control" id="numero_inventaire" name="numero_inventaire" required
                               value="<?= htmlspecialchars($actif['numero_inventaire'] ?? '') ?>">
                         <div class="invalid-feedback"><?php echo $T['required_feedback_inventaire']; ?></div>
                    </div>
                    <div class="col-md-4">
                         <label for="numero_serie" class="form-label"><?php echo $T['numero_serie']; ?></label>
                        <input type="text" class="form-control" id="numero_serie" name="numero_serie" 
                               value="<?= htmlspecialchars($actif['numero_serie'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                         <label for="adresse_mac" class="form-label"><?php echo $T['adresse_mac']; ?></label>
                        <input type="text" class="form-control" id="adresse_mac" name="adresse_mac" 
                               value="<?= htmlspecialchars($actif['adresse_mac'] ?? '') ?>">
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
                               value="<?= htmlspecialchars($actif['date_achat'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                         <label for="duree_amortissement" class="form-label required"><?php echo $T['duree_amortissement']; ?></label>
                        <input type="number" class="form-control" id="duree_amortissement" name="duree_amortissement" required min="1"
                               value="<?= htmlspecialchars($actif['duree_amortissement'] ?? '') ?>">
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
                        <select class="form-select" id="statut" name="statut" required <?php if ($is_admin_profil) echo 'disabled'; ?>>
                            <?php 
                            $selectedStatut = $actif['statut'] ?? $T['status_stock_db'];
                            foreach ($statusOptionsMap as $db_value => $display_key): 
                            ?>
                                <option value="<?= htmlspecialchars($db_value) ?>" 
                                        <?= ($selectedStatut === $db_value) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($T[$display_key]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                         <div class="invalid-feedback"><?php echo $T['required_feedback_statut']; ?></div>
                    </div>
                    
                    <div class="col-md-4">
                         <label for="date_service" class="form-label"><?php echo $T['date_service']; ?></label>
                        <input type="date" class="form-control" id="date_service" name="date_service" 
                               value="<?= htmlspecialchars($actif['date_service'] ?? '') ?>" <?php if ($is_admin_profil) echo 'disabled'; ?>>
                    </div>

                    <div class="col-md-4">
                         <label for="bureau" class="form-label"><?php echo $T['bureau']; ?></label>
                        <select class="form-select" id="bureau" name="bureau" <?php if ($is_admin_profil) echo 'disabled'; ?>>
                             <option value=""><?php echo $T['option_not_assigned']; ?></option>
                            <?php foreach ($organisations as $org): ?>
                                <option value="<?= htmlspecialchars($org) ?>"
                                    <?= ($actif['bureau'] ?? '') == $org ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($org) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                         <label for="affecter_a" class="form-label"><?php echo $T['affecter_a']; ?></label>
                        <select class="form-select" id="affecter_a" name="affecter_a" disabled>
                            <option value=""><?php echo $T['option_select_first']; ?></option>
                             </select>
                    </div>
                </div>
                
                <div class="row g-3 mt-3">
                    <div class="col-12">
                         <label for="commentaire" class="form-label"><?php echo $T['commentaire_actuel']; ?></label>
                        <textarea class="form-control" id="commentaire" name="commentaire" rows="2" <?php if ($is_admin_profil) echo 'disabled'; ?>><?= htmlspecialchars($actif['commentaire'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4 border-warning">
             <div class="card-header bg-warning text-dark"><?php echo $T['section_history_comment']; ?></div>
            <div class="card-body">
                <div class="col-12">
                     <label for="commentaire_historique" class="form-label"><?php echo $T['history_comment_desc']; ?></label>
                    <textarea class="form-control" id="commentaire_historique" name="commentaire_historique" rows="2" <?php if ($is_admin_profil) echo 'disabled'; ?>></textarea>
                     <div class="invalid-feedback"><?php echo $T['required_feedback_history']; ?></div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 mb-5">
             <button type="submit" class="btn btn-primary btn-lg"><?php echo $T['button_save']; ?></button>
        </div>
    </form>
    
    
     <h3 class="mt-5 mb-3"><?php echo $T['history_title']; ?><?= htmlspecialchars($actif['numero_inventaire'] ?? 'N/A') ?>)</h3>
    <div class="card mb-5">
        <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white">
             <span><?php echo $T['history_details']; ?><?= count($historique) ?><?php echo $T['history_entries']; ?></span>
            <a href="historique_pdf.php?id=<?= $actifId ?>" target="_blank" class="btn btn-sm btn-danger">
                <i class="bi bi-file-earmark-pdf-fill"></i> <?php echo $T['history_download_btn']; ?>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                             <th><?php echo $T['history_date']; ?></th>
                            <th><?php echo $T['history_status']; ?></th>
                            <th><?php echo $T['history_assignment']; ?></th>
                            <th><?php echo $T['history_comment']; ?></th>
                            <th><?php echo $T['history_modified_by']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($historique) > 0): ?>
                            <?php foreach ($historique as $h): ?>
                            <tr>
                                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($h['date_historique'] ?? 'NOW'))) ?></td>
                                <td>
                                    <?php if (isset($h['ancien_statut'], $h['nouveau_statut'])): ?>
                                        <?= htmlspecialchars($h['ancien_statut']) ?> → 
                                        <span class="badge <?= getStatusBadgeClass($h['nouveau_statut']) ?>"><?= htmlspecialchars(translateStatusForDisplay($h['nouveau_statut'], $T)) ?></span>
                                    <?php else: ?>
                                        <span class="badge <?= getStatusBadgeClass($h['statut'] ?? 'N/A') ?>">
                                            <?= htmlspecialchars(translateStatusForDisplay($h['statut'] ?? 'N/A', $T)) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($h['ancien_affectation'], $h['nouvelle_affectation'])): ?>
                                        <?= htmlspecialchars($h['ancien_affectation'] ?? 'N/A') ?> → 
                                        <strong><?= htmlspecialchars($h['nouvelle_affectation'] ?? 'N/A') ?></strong>
                                    <?php else: ?>
                                        <strong><?= htmlspecialchars($h['affecter_a'] ?? 'N/A') ?></strong>
                                    <?php endif; ?>
                                </td>
                                
                                <td><?= htmlspecialchars($h['commentaire_changement'] ?? $h['commentaire'] ?? $T['no_comment']) ?></td>
                                
                                <td><?= htmlspecialchars($h['modifier_par'] ?? $h['creer_par'] ?? 'Inconnu') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    <?php echo $T['history_no_entry']; ?>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Constantes PHP passées à JS ---
        const isAdmin = <?= json_encode($is_admin_profil) ?>;
        const statusEnService = "<?= $T['status_service_db'] ?>";
        const initialStatut = "<?= htmlspecialchars($actif['statut'] ?? '') ?>";
        const initialAffected = "<?= htmlspecialchars($actif['affecter_a'] ?? '') ?>";
        const initialBureau = "<?= htmlspecialchars($actif['bureau'] ?? '') ?>";

        // --- Constantes pour les messages et l'API ---
        const API_TOKEN = "<?= $authToken ?>";
        const API_URL = "https://api.kazipro.app/api/authentification/getUsersForInventory";
        const textSelectFirst = "<?= $T['option_select_first'] ?>"; 
        const textNotAssigned = "<?= $T['option_not_assigned'] ?>";
        const textOldAssignment = "<?= $T['history_assignment_old'] ?>";
        const textLoadingData = "<?= $T['loading_data'] ?>";
        const textErrorLoadingStaff = "<?= $T['error_loading_staff'] ?>";


        // --- Éléments du DOM ---
        const statutSelect = document.getElementById('statut');
        const bureauSelect = document.getElementById('bureau');
        const affecterASelect = document.getElementById('affecter_a');
        const dateServiceInput = document.getElementById('date_service');
        const commentaireHistoriqueInput = document.getElementById('commentaire_historique');
        
        const affectedFields = [dateServiceInput, bureauSelect, affecterASelect];
        
        /**
         * Charge la liste du staff pour une organisation donnée via l'API.
         * @param {string} selectedOrganisation - L'organisation sélectionnée.
         * @param {string|null} initialValue - La valeur d'affectation actuelle pour la pré-sélection.
         */
        async function loadStaffByOrganisation(selectedOrganisation, initialValue = null) {
            
            // 1. État de chargement initial
            affecterASelect.innerHTML = `<option value="">${textLoadingData}</option>`; 
            affecterASelect.disabled = true;

            if (!selectedOrganisation) {
                // Si aucune organisation, réinitialiser à l'état par défaut (Non Affecté)
                affecterASelect.innerHTML = `<option value="">${textNotAssigned}</option>`;
                affecterASelect.disabled = statutSelect.value !== statusEnService;
                return;
            }

            try {
                // Construire l'URL avec l'organisation en tant que paramètre de requête
                const url = `${API_URL}?organisation=${encodeURIComponent(selectedOrganisation)}`;
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${API_TOKEN}`
                    }
                });

                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }

                const staffList = await response.json();
                
                // 2. Mise à jour du sélecteur après succès
                affecterASelect.innerHTML = `<option value="">${textNotAssigned}</option>`; // Toujours ajouter Non Affecté
                
                let foundInitialValue = false;
                
                if (Array.isArray(staffList)) {
                    staffList.forEach(staff => {
                        // Combinaison firstName et name (nom et prénom)
                        const staffFullName = (staff.firstName + ' ' + staff.name).trim();
                        // La VALEUR contient Nom Complet | Email (pour l'envoi du formulaire)
                        const optionValue = `${staffFullName}|${staff.email}`;
                        
                        const option = document.createElement('option');
                        option.value = optionValue;
                        option.textContent = staffFullName;
                        
                        // Pré-sélectionner l'affectation actuelle
                        if (initialValue && staffFullName === initialValue) {
                            option.selected = true;
                            foundInitialValue = true;
                        }
                        affecterASelect.appendChild(option);
                    });
                }
                
                // Gérer le cas de l'ancienne affectation si elle n'est plus dans la liste API mais est le bureau actuel
                if (initialValue && initialValue !== '' && initialAffected === initialValue && !foundInitialValue && initialBureau === selectedOrganisation) {
                    const option = document.createElement('option');
                    // On ne peut pas récupérer l'email de l'ancienne affectation, on le laisse vide
                    option.value = initialValue + '|'; 
                    option.textContent = initialValue + textOldAssignment;
                    option.selected = true;
                    affecterASelect.appendChild(option);
                }

            } catch (error) {
                console.error("API Staff Fetch Error:", error);
                affecterASelect.innerHTML = `<option value="">${textErrorLoadingStaff}</option>`;
            } finally {
                // 3. Activer le champ si le statut le permet
                affecterASelect.disabled = statutSelect.value !== statusEnService;
            }
        }

        function toggleAffectedFields() {
            if (isAdmin) return;
            
            const currentStatut = statutSelect.value;
            const isEnService = currentStatut === statusEnService;
            const currentBureau = bureauSelect.value;
            
            // Activer/Désactiver les champs d'affectation en fonction du statut "En service"
            affectedFields.forEach(field => {
                field.disabled = !isEnService;
            });
            
            if (isEnService) {
                // Déclencher le chargement du staff si un bureau est déjà sélectionné
                loadStaffByOrganisation(currentBureau, initialAffected); 
                // Si aucun bureau n'est sélectionné, le select Affecter à est désactivé par loadStaffByOrganisation
                if (!currentBureau) {
                    affecterASelect.innerHTML = `<option value="">${textSelectFirst}</option>`;
                    affecterASelect.disabled = true;
                }
            } else {
                // Si pas en service
                affecterASelect.innerHTML = `<option value="">${textNotAssigned}</option>`;
                affecterASelect.disabled = true;
            }
            
            // Mettre à jour la validation du commentaire historique
            updateHistoryCommentRequired();
        }
        
        function updateHistoryCommentRequired() {
            if (isAdmin) return;
            
            const isStatusChanged = statutSelect.value !== initialStatut;
            
            const currentAffectedValue = affecterASelect.value;
            // On extrait le nom avant l'encodage de l'email
            const currentAffectedName = currentAffectedValue ? currentAffectedValue.split('|')[0] : ''; 
            const isAffectedChanged = currentAffectedName !== initialAffected;

            commentaireHistoriqueInput.required = isStatusChanged || isAffectedChanged;
        }

        // --- Événements ---
        statutSelect.addEventListener('change', toggleAffectedFields);
        
        // NOUVEAU: Le changement de bureau déclenche le chargement AJAX du staff
        bureauSelect.addEventListener('change', function() {
            const selectedOrganisation = this.value;
            // Charger le staff lorsque l'organisation change, en réinitialisant la sélection du staff
            if (statutSelect.value === statusEnService) { 
                loadStaffByOrganisation(selectedOrganisation, null); // null pour ne pas présélectionner
            }
            updateHistoryCommentRequired();
        });

        affecterASelect.addEventListener('change', updateHistoryCommentRequired);
        
        // --- Appels initiaux au chargement de la page ---
        // 1. Initialiser les champs d'affectation et charger le staff si un actif est déjà affecté
        toggleAffectedFields();
        
        // 2. Initialisation spécifique pour le staff s'il y a une valeur initiale
        if (initialBureau && initialStatut === statusEnService && !isAdmin) {
             // Charger le staff de l'organisation actuelle et présélectionner l'affectation actuelle
             loadStaffByOrganisation(initialBureau, initialAffected);
        } else if (!isAdmin) {
            // Si pas en service (ou stock), désactiver le champ Affecté à et afficher le message initial
             affecterASelect.innerHTML = `<option value="">${textSelectFirst}</option>`;
             affecterASelect.disabled = true;
        }
        
        // --- Validation Bootstrap (Inchangée) ---
        (function () {
          'use strict'
          const forms = document.querySelectorAll('.needs-validation')
          Array.prototype.slice.call(forms)
            .forEach(function (form) {
              form.addEventListener('submit', function (event) {
                   if (!isAdmin) {
                       // Mise à jour finale avant validation
                       updateHistoryCommentRequired(); 
                   }
                   
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
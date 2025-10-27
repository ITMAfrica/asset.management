<?php
// Inclure le fichier de connexion (Assurez-vous qu'il est correct et accessible)
require_once 'db_connexion.php'; 

// --- NOUVEAU : Inclure les classes PHPMailer (PRÉ-REQUIS) ---
// Vous devez avoir installé PHPMailer (ex: via Composer) ou inclure les fichiers manuellement

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Assurez-vous que le chemin vers les fichiers PHPMailer est correct par rapport à votre script
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';


// Démarrer la session pour récupérer l'utilisateur connecté
session_start();
$modifier_par = $_SESSION['user_nom'] ?? 'Système/Admin Inconnu';

$message = '';
$actifId = 0; // Initialisation

// --- Fonction utilitaire pour les badges de statut ---
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'En service': return 'bg-success';
        case 'En stock': return 'bg-info';
        case 'En réparation': return 'bg-warning text-dark';
        case 'Volé':
        case 'Déclassé':
        case 'Hors service': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

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
$entites = []; // Variable pour les entités (Bureaux)
$staffList = []; // NOUVEAU : Liste du staff

// --- 2. Récupération des données initiales (Lecture) ---
try {
    // 2.1 Charger la liste des types d'équipement
    $stmtTypes = $pdo->query("SELECT description FROM t_device ORDER BY description ASC");
    $deviceTypes = $stmtTypes->fetchAll(PDO::FETCH_COLUMN);

    // 2.2 Charger les entités (Bureaux)
    $stmtEntites = $pdo->query("SELECT description FROM t_entite ORDER BY description ASC"); 
    $entites = $stmtEntites->fetchAll(PDO::FETCH_COLUMN);

    // 2.3 Charger l'actif pour le pré-remplissage
    $stmtActif = $pdo->prepare("SELECT * FROM t_actif WHERE id_actif = ?");
    $stmtActif->execute([$actifId]);
    $actif = $stmtActif->fetch(PDO::FETCH_ASSOC);

    if (!$actif) {
        header("Location: liste_gest_actif.php");
        exit();
    }
    
    // 2.4 NOUVEAU : Charger la liste complète du staff pour le JS
    $stmtStaff = $pdo->query("SELECT nom_complet, bureau FROM t_staff ORDER BY nom_complet ASC");
    $staffList = $stmtStaff->fetchAll(PDO::FETCH_ASSOC);

    // 2.5 Charger l'historique (Utilisation de SELECT * pour récupérer toutes les colonnes disponibles)
    $stmtHist = $pdo->prepare("SELECT * FROM t_historique_actif WHERE Id_actif_original = ? ORDER BY date_historique DESC");
    $stmtHist->execute([$actifId]);
    $historique = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
    
    // Conserver le statut et l'affectation originaux pour la vérification POST
    $old_statut = $actif['statut'];
    $old_affecter_a = $actif['affecter_a'];

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// --- FONCTION D'ENVOI D'EMAIL (avec PHPMailer) ---
function sendNotificationEmail($pdo, $data, $numero_inventaire, $staff_email_cc = []) {
    
    // 1. Récupérer l'email du staff depuis t_staff
    $staff_email = null;
    $staff_name = trim($data['affecter_a']); 
    if (!empty($staff_name)) {
        try {
            $stmtEmail = $pdo->prepare("SELECT email FROM t_staff WHERE nom_complet = ?");
            $stmtEmail->execute([$staff_name]);
            $staff_email = $stmtEmail->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur DB lors de la récupération de l'email du staff: " . $e->getMessage());
        }
    }

    $mail = new PHPMailer(true);

    try {
        // Paramètres du serveur SMTP (À ADAPTER)
        $mail->isSMTP();
        $mail->Host       = 'mail.pag-tech.net'; // Ex: smtp.gmail.com
        $mail->SMTPAuth   = true;
        $mail->Username   = 'it.services@pag-tech.net'; 
        $mail->Password   = 'IT_service@2024'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587; 
        $mail->CharSet    = 'UTF-8';

        // Destinataires
        $mail->setFrom('it.services@pag-tech.net', 'Gestion des Actifs');
        
        // Destinataire principal: Email du staff (obligatoire)
        if ($staff_email) {
            $mail->addAddress($staff_email, $staff_name); 
        }
        
        // Copie (CC)
        // ADRESSES CC À DÉFINIR
        $mail->addCC('glodi.nsaka@kkd.com');
        $mail->addCC('diglogosen@gmail.com');

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = 'NOTIFICATION : Affectation d\'Actif ' . $numero_inventaire . ' (' . $staff_name . ')';
        
        $body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; background-color: #f4f4f4; padding: 20px;'>
                <div style='max-width: 600px; margin: auto; background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05);'>
                    <h2 style='color: #0d6efd;'>Nouvelle Affectation d'Actif</h2>
                    <p>Bonjour {$staff_name},</p>
                    <p>L'actif <strong>{$numero_inventaire}</strong> vous a été affecté et son statut est passé à <strong>'En service'</strong>. Voici les détails :</p>
                    <table style='border-collapse: collapse; width: 100%; margin-top: 15px; background: #fafafa;'>
                        <tr><td style='border: 1px solid #ddd; padding: 8px; font-weight: bold; width: 30%;'>Équipement :</td><td style='border: 1px solid #ddd; padding: 8px;'>{$data['type_equipement']} ({$data['nom_equipement']})</td></tr> <tr><td style='border: 1px solid #ddd; padding: 8px; font-weight: bold; width: 30%;'>N° Inventaire :</td><td style='border: 1px solid #ddd; padding: 8px;'>{$numero_inventaire}</td></tr>
                        <tr><td style='border: 1px solid #ddd; padding: 8px; font-weight: bold; (Utilisé pour l'affichage ici)>Bureau :</td><td style='border: 1px solid #ddd; padding: 8px;'>{$data['bureau']}</td></tr>
                        <tr><td style='border: 1px solid #ddd; padding: 8px; font-weight: bold; width: 30%;'>Date Service :</td><td style='border: 1px solid #ddd; padding: 8px;'>{$data['date_service']}</td></tr>
                    </table>
                    <p style='margin-top: 20px;'>Veuillez en accuser réception.</p>
                    <p style='font-size: 0.9em; color: #777;'>Ceci est un message automatique de l'outil de Gestion des Actifs. Merci de ne pas répondre.</p>
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
    
    $data = [
        'type_equipement' => $_POST['type_equipement'] ?? null,
        // *** CORRECTION 1: Retrait de l'usage direct de marque_equipement pour l'insertion ***
        // L'input s'appelle toujours marque_equipement, mais on l'insère dans nom_equipement.
        'nom_equipement' => $_POST['marque_equipement'] ?? null, // <-- C'EST LA CORRECTION DEMANDÉE
        'specification' => $_POST['specification'] ?? null,
        'numero_serie' => $_POST['numero_serie'] ?? null,
        'numero_inventaire' => $_POST['numero_inventaire'] ?? null,
        'adresse_mac' => $_POST['adresse_mac'] ?? null,
        'date_achat' => $_POST['date_achat'] ?? null,
        'duree_amortissement' => $_POST['duree_amortissement'] ?? null,
        'statut' => $_POST['statut'] ?? null,
        'date_service' => $_POST['date_service'] ?? null,
        'bureau' => $_POST['bureau'] ?? null,
        'affecter_a' => $_POST['affecter_a'] ?? null,
        'commentaire' => $_POST['commentaire'] ?? null,
        'commentaire_historique' => $_POST['commentaire_historique'] ?? null, 
        'modifier_par' => $modifier_par, 
    ];

    $new_statut = $data['statut'];
    $new_affecter_a = trim($data['affecter_a'] ?? '');
    
    // S'assurer que 'date_service', 'bureau', et 'affecter_a' sont NULL/vides s'ils ne sont pas 'En service'
    $final_date_service = ($new_statut == 'En service') ? $data['date_service'] : null;
    $final_bureau = ($new_statut == 'En service') ? $data['bureau'] : null;
    $final_affecter_a = ($new_statut == 'En service') ? $data['affecter_a'] : null;
    
    // --- VÉRIFICATION : Un seul ACTIF 'En service' par Staff (Règle Métier) ---
    // Cette vérification s'applique uniquement si le NOUVEAU statut est 'En service' et qu'il y a une affectation.
    $should_check_restriction = $new_statut == 'En service' && !empty($new_affecter_a);
    
    if ($should_check_restriction) {
        
        // Requête pour compter les autres actifs 'En service' affectés au même staff.
        // NOTE: Nous avons retiré la condition 'type_equipement ILIKE ...'
        $stmtCheck = $pdo->prepare("
            SELECT COUNT(*) 
            FROM t_actif 
            WHERE 
                affecter_a = :affecter_a 
                AND statut = 'En service' 
                AND id_actif != :actifId /* Exclusion de l'actif en cours de modification */
        ");
        $stmtCheck->execute([
            ':affecter_a' => $new_affecter_a,
            ':actifId' => $actifId
        ]);
        $count = $stmtCheck->fetchColumn();

        if ($count > 0) {
            // Blocage de la modification
            $message = "<div class='alert alert-danger'>🚫 **Erreur d'affectation :** Le staff **{$new_affecter_a}** détient déjà un autre actif dont le statut est 'En service'.</div>";
            // L'instruction `goto` stoppe le script avant la transaction SQL
            goto end_of_post_treatment; 
        }
    }

    // --- FIN VÉRIFICATION ---
    $statut_changed = ($new_statut != $old_statut);
    $affectation_changed = ($final_affecter_a != $old_affecter_a);
    $should_log_history = $statut_changed || $affectation_changed;

    try {
        $pdo->beginTransaction();

        // S'assurer que 'date_service', 'bureau', et 'affecter_a' sont NULL/vides s'ils ne sont pas 'En service'
        $final_date_service = ($new_statut == 'En service') ? $data['date_service'] : null;
        $final_bureau = ($new_statut == 'En service') ? $data['bureau'] : null;
        $final_affecter_a = ($new_statut == 'En service') ? $data['affecter_a'] : null;

        // *** CORRECTION 2: Requête SQL SANS marque_equipement, utilisant nom_equipement ***
        $sql_update = "
            UPDATE t_actif SET 
                type_equipement = :type_equipement, 
                nom_equipement = :nom_equipement, specification = :specification, /* <-- CORRIGÉ : nom_equipement */
                numero_serie = :numero_serie, numero_inventaire = :numero_inventaire, 
                adresse_mac = :adresse_mac, date_achat = :date_achat, 
                duree_amortissement = :duree_amortissement, statut = :statut, date_service = :date_service, 
                bureau = :bureau, affecter_a = :affecter_a, commentaire = :commentaire
            WHERE id_actif = :id_actif
        ";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
            ':type_equipement' => $data['type_equipement'], 
            ':nom_equipement' => $data['nom_equipement'],         /* <-- CORRIGÉ : lie nom_equipement */
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
        
        // --- Enregistrement dans l'historique (LOGIQUE DE SUIVI DES CHANGEMENTS) ---
        if ($should_log_history && !empty(trim($data['commentaire_historique'] ?? ''))) {
            
    // *** CORRECTION : Ajout de la colonne 'creer_par' dans la liste des colonnes et dans les valeurs. ***
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
        ':commentaire_changement' => $data['commentaire_historique'], 
        ':modifier_par' => $data['modifier_par']
    ]);
}
        
        $pdo->commit();

        $message = "<div class='alert alert-success'>✅ L'actif **{$data['numero_inventaire']}** a été mis à jour avec succès.</div>";
        
        // --- NOUVEAU : ENVOI D'EMAIL ---
        if ($new_statut == 'En service' && $statut_changed) {
            sendNotificationEmail($pdo, $data, $data['numero_inventaire']);
            $message .= "<div class='alert alert-info'>📧 Notification d'affectation envoyée par email au staff concerné et aux adresses en copie.</div>";
        }

        // Recharger les données après la modification pour l'affichage
        $stmtActif->execute([$actifId]);
        $actif = $stmtActif->fetch(PDO::FETCH_ASSOC);
        
        // Recharger l'historique
        $stmtHist = $pdo->prepare("SELECT * FROM t_historique_actif WHERE Id_actif_original = ? ORDER BY date_historique DESC");
        $stmtHist->execute([$actifId]);
        $historique = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger'>Erreur de transaction : " . $e->getMessage() . "</div>";
    }
}
// Étiquette pour le 'goto' en cas d'erreur de double affectation
end_of_post_treatment:
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Actif: <?= htmlspecialchars($actif['numero_inventaire'] ?? 'N/A') ?></title>
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
        <h1>Modification Actif: <?= htmlspecialchars($actif['numero_inventaire'] ?? 'N/A') ?></h1>
        <a href="liste_gest_actif.php" class="btn btn-secondary">Retour à la Liste</a>
    </div>

    <?= $message ?>

    <form method="POST" class="needs-validation" novalidate>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">Informations Générales</div>
            <div class="card-body">
                <div class="row g-3">
                    
                    <div class="col-md-4">
                        <label for="type_equipement" class="form-label required">Type d'Équipement</label>
                        <select class="form-select" id="type_equipement" name="type_equipement" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach ($deviceTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>" 
                                    <?= ($actif['type_equipement'] ?? '') == $type ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner le type d'équipement.</div>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="marque_equipement" class="form-label">Marque</label>
                        <input type="text" class="form-control" id="marque_equipement" name="marque_equipement" 
                               value="<?= htmlspecialchars($actif['nom_equipement'] ?? '') ?>"> </div>
                    
                    <div class="col-md-4">
                        <label for="specification" class="form-label">Spécification</label>
                        <input type="text" class="form-control" id="specification" name="specification" 
                               value="<?= htmlspecialchars($actif['specification'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="numero_inventaire" class="form-label required">N° Inventaire</label>
                        <input type="text" class="form-control" id="numero_inventaire" name="numero_inventaire" required
                               value="<?= htmlspecialchars($actif['numero_inventaire'] ?? '') ?>">
                        <div class="invalid-feedback">Le numéro d'inventaire est requis.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="numero_serie" class="form-label">N° Série</label>
                        <input type="text" class="form-control" id="numero_serie" name="numero_serie" 
                               value="<?= htmlspecialchars($actif['numero_serie'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label for="adresse_mac" class="form-label">Adresse MAC</label>
                        <input type="text" class="form-control" id="adresse_mac" name="adresse_mac" 
                               value="<?= htmlspecialchars($actif['adresse_mac'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">Gestion & Amortissement</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="date_achat" class="form-label">Date d'Achat</label>
                        <input type="date" class="form-control" id="date_achat" name="date_achat" 
                               value="<?= htmlspecialchars($actif['date_achat'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="duree_amortissement" class="form-label required">Durée Amortissement (Ans)</label>
                        <input type="number" class="form-control" id="duree_amortissement" name="duree_amortissement" required min="1"
                               value="<?= htmlspecialchars($actif['duree_amortissement'] ?? '') ?>">
                        <div class="invalid-feedback">La durée d'amortissement est requise.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-success text-white">Statut et Affectation</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="statut" class="form-label required">Statut Actuel</label>
                        <select class="form-select" id="statut" name="statut" required>
                            <option value="En service" <?= ($actif['statut'] ?? '') == 'En service' ? 'selected' : '' ?>>En service</option>
                            <option value="En stock" <?= ($actif['statut'] ?? '') == 'En stock' ? 'selected' : '' ?>>En stock</option>
                            <option value="En réparation" <?= ($actif['statut'] ?? '') == 'En réparation' ? 'selected' : '' ?>>En réparation</option>
                            <option value="Amorti" <?= ($actif['statut'] ?? '') == 'Amorti' ? 'selected' : '' ?>>Amorti</option>
                            <option value="Hors service" <?= ($actif['statut'] ?? '') == 'Hors service' ? 'selected' : '' ?>>Hors service</option>
                            <option value="Volé" <?= ($actif['statut'] ?? '') == 'Volé' ? 'selected' : '' ?>>Volé</option>
                            <option value="Déclassé" <?= ($actif['statut'] ?? '') == 'Déclassé' ? 'selected' : '' ?>>Déclassé</option>
                        </select>
                        <div class="invalid-feedback">Le statut est requis.</div>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="date_service" class="form-label">Date de Mise en Service</label>
                        <input type="date" class="form-control" id="date_service" name="date_service" 
                               value="<?= htmlspecialchars($actif['date_service'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="bureau" class="form-label">Bureau / Localité</label>
                        <select class="form-select" id="bureau" name="bureau">
                            <option value="">Non Affecté (Stock)</option>
                            <?php foreach ($entites as $entite): ?>
                                <option value="<?= htmlspecialchars($entite) ?>"
                                    <?= ($actif['bureau'] ?? '') == $entite ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($entite) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="affecter_a" class="form-label">Affecté à (Staff)</label>
                        <select class="form-select" id="affecter_a" name="affecter_a">
                            <option value="">Non Affecté</option>
                            <?php 
                            // Si l'affectation actuelle existe mais n'est pas dans la liste dynamique, on l'ajoute temporairement
                            $is_staff_in_list = false;
                            foreach ($staffList as $staff) {
                                if ($staff['nom_complet'] === ($actif['affecter_a'] ?? '')) {
                                    $is_staff_in_list = true;
                                    break;
                                }
                            }
                            if (!$is_staff_in_list && !empty($actif['affecter_a'])):
                            ?>
                                <option value="<?= htmlspecialchars($actif['affecter_a']) ?>" selected>
                                    <?= htmlspecialchars($actif['affecter_a']) ?>
                                </option>
                            <?php 
                            endif;
                            // Le reste des options sera chargé dynamiquement par JS selon le bureau
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="row g-3 mt-3">
                    <div class="col-12">
                        <label for="commentaire" class="form-label">Commentaire Actuel (Affiché sur la fiche)</label>
                        <textarea class="form-control" id="commentaire" name="commentaire" rows="2"><?= htmlspecialchars($actif['commentaire'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-dark">Commentaire pour l'Historique (Obligatoire si statut/affectation change)</div>
            <div class="card-body">
                <div class="col-12">
                    <label for="commentaire_historique" class="form-label">Décrivez brièvement le changement:</label>
                    <textarea class="form-control" id="commentaire_historique" name="commentaire_historique" rows="2"></textarea>
                    <div class="invalid-feedback">Ce champ est obligatoire si vous modifiez le statut ou l'affectation.</div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 mb-5">
            <button type="submit" class="btn btn-primary btn-lg">Enregistrer les Modifications</button>
        </div>
    </form>
    
    
    <h3 class="mt-5 mb-3">Historique des Changements (Actif N°: <?= htmlspecialchars($actif['numero_inventaire'] ?? 'N/A') ?>)</h3>
    <div class="card mb-5">
        <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white">
            <span>Détails de l'Historique (<?= count($historique) ?> entrées)</span>
            <a href="historique_pdf.php?id=<?= $actifId ?>" target="_blank" class="btn btn-sm btn-danger">
                <i class="bi bi-file-earmark-pdf-fill"></i> Télécharger en Pdf
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Affectation</th>
                            <th>Commentaire</th>
                            <th>Modifié par</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($historique) > 0): ?>
                            <?php foreach ($historique as $h): ?>
                            <tr>
                                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($h['date_historique'] ?? 'NOW'))) ?></td>
                                <td>
                                    <?php if (isset($h['ancien_statut'], $h['nouveau_statut'])): // Logique pour les nouvelles entrées (suivi des changements) ?>
                                        <?= htmlspecialchars($h['ancien_statut']) ?> → 
                                        <span class="badge <?= getStatusBadgeClass($h['nouveau_statut']) ?>"><?= htmlspecialchars($h['nouveau_statut']) ?></span>
                                    <?php else: // Logique de secours pour les anciennes entrées (simple snapshot) ?>
                                        <span class="badge <?= getStatusBadgeClass($h['statut'] ?? 'N/A') ?>">
                                            <?= htmlspecialchars($h['statut'] ?? 'Statut N/A') ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($h['ancien_affectation'], $h['nouvelle_affectation'])): // Logique pour les nouvelles entrées (suivi des changements) ?>
                                        <?= htmlspecialchars($h['ancien_affectation'] ?? 'N/A') ?> → 
                                        <strong><?= htmlspecialchars($h['nouvelle_affectation'] ?? 'N/A') ?></strong>
                                    <?php else: // Logique de secours pour les anciennes entrées (simple snapshot) ?>
                                        <strong><?= htmlspecialchars($h['affecter_a'] ?? 'N/A') ?></strong>
                                    <?php endif; ?>
                                </td>
                                
                                <td><?= htmlspecialchars($h['commentaire_changement'] ?? $h['commentaire'] ?? 'Aucun commentaire.') ?></td>
                                
                                <td><?= htmlspecialchars($h['modifier_par'] ?? $h['creer_par'] ?? 'Inconnu') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    Aucun historique enregistré pour cet actif.
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
        // Variables PHP pour le JS
        const initialStatut = "<?= htmlspecialchars($actif['statut'] ?? '') ?>";
        const initialAffected = "<?= htmlspecialchars($actif['affecter_a'] ?? '') ?>";
        const initialBureau = "<?= htmlspecialchars($actif['bureau'] ?? '') ?>";
        // Convertir les données PHP staff en JSON pour JS
        const staffData = <?= json_encode($staffList) ?>; 

        // Récupération des éléments
        const statutSelect = document.getElementById('statut');
        const bureauSelect = document.getElementById('bureau');
        const affecterASelect = document.getElementById('affecter_a');
        const dateServiceInput = document.getElementById('date_service');
        const commentaireHistoriqueInput = document.getElementById('commentaire_historique');
        
        const affectedFields = [
            dateServiceInput,
            bureauSelect,
            affecterASelect,
        ];
        
        // --- Logique de chargement dynamique du Staff ---
        function loadStaffByBureau(selectedBureau, initialValue = null) {
            
            affecterASelect.innerHTML = '<option value="">Non Affecté</option>'; 
            
            if (!selectedBureau) {
                 return;
            }

            const filteredStaff = staffData.filter(staff => staff.bureau === selectedBureau);

            if (filteredStaff.length > 0) {
                filteredStaff.forEach(staff => {
                    const option = document.createElement('option');
                    option.value = staff.nom_complet;
                    option.textContent = staff.nom_complet;
                    
                    // Pré-sélectionner l'ancienne valeur ou la valeur initiale
                    if (initialValue && staff.nom_complet === initialValue) {
                        option.selected = true;
                    }
                    affecterASelect.appendChild(option);
                });
            } 
            
            // Si l'affectation initiale existe mais n'est pas dans le staff filtré, on la remet pour ne pas la perdre
             if (initialValue && initialValue !== '' && !filteredStaff.some(staff => staff.nom_complet === initialValue)) {
                const option = document.createElement('option');
                option.value = initialValue;
                option.textContent = initialValue + " (Ancienne Affectation)";
                option.selected = true;
                affecterASelect.appendChild(option);
            }
        }

        // --- Logique de désactivation des champs et de validation (MIS À JOUR) ---
        function toggleAffectedFields() {
            const currentStatut = statutSelect.value;
            
            // Les champs sont actifs UNIQUEMENT si le statut est 'En service'
            const isEnService = currentStatut === 'En service';
            
            const isStatusChanged = currentStatut !== initialStatut;
            const isAffectedChanged = affecterASelect.value !== initialAffected;
            
            // Rendre le commentaire historique requis si un changement significatif est détecté
            commentaireHistoriqueInput.required = isStatusChanged || isAffectedChanged;
            
            if (isEnService) {
                // Activer les champs et les rendre requis
                affectedFields.forEach(field => {
                    field.disabled = false;
                    field.required = true;
                });
                
                // Recharger la liste du staff en fonction du bureau actuel (avec la valeur initiale)
                loadStaffByBureau(bureauSelect.value, affecterASelect.value);
                
            } else {
                // Désactiver les champs et les rendre non-requis
                affectedFields.forEach(field => {
                    field.disabled = true;
                    field.required = false; 
                });
                
                // Réinitialiser les valeurs des champs non-pertinents
                dateServiceInput.value = '';
                bureauSelect.value = ''; // Vider la valeur
                
                // L'affectation doit être réinitialisée pour les autres statuts
                affecterASelect.innerHTML = '<option value="">Non Affecté</option>';
                affecterASelect.value = ''; // S'assurer que la valeur soumise est vide
            }
        }

        // Événements
        statutSelect.addEventListener('change', toggleAffectedFields);
        
        bureauSelect.addEventListener('change', function() {
            // Recharger la liste du staff à chaque changement de bureau, uniquement si En service
            if (statutSelect.value === 'En service') {
                loadStaffByBureau(this.value, null); 
            }
        });
        
        affecterASelect.addEventListener('change', function() {
            // Un changement dans l'affectation peut rendre le commentaire historique requis
            const isStatusChanged = statutSelect.value !== initialStatut;
            const isAffectedChanged = this.value !== initialAffected;
            commentaireHistoriqueInput.required = isStatusChanged || isAffectedChanged;
        });


        // Appel initial : Déclencher la logique au chargement de la page
        toggleAffectedFields();
        
        // S'assurer que si l'actif est initialement 'En service', la liste du staff est chargée au démarrage
        if (initialBureau && initialStatut === 'En service') {
             loadStaffByBureau(initialBureau, initialAffected);
        }
        
        // Validation Bootstrap
        (function () {
          'use strict'
          const forms = document.querySelectorAll('.needs-validation')
          Array.prototype.slice.call(forms)
            .forEach(function (form) {
              form.addEventListener('submit', function (event) {
                  // Vérification si le champ historique doit être rempli
                   const isStatusChanged = statutSelect.value !== initialStatut;
                   const isAffectedChanged = affecterASelect.value !== initialAffected;

                   if ((isStatusChanged || isAffectedChanged) && !commentaireHistoriqueInput.value.trim()) {
                        commentaireHistoriqueInput.setCustomValidity("Le commentaire historique est requis lors d'un changement de statut ou d'affectation.");
                   } else {
                        commentaireHistoriqueInput.setCustomValidity(""); // Réinitialiser le message d'erreur
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
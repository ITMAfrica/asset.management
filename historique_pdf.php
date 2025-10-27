<?php
// Inclure le fichier de connexion à la base de données
require_once 'db_connexion.php'; 

// --- Configuration de Dompdf (Assurez-vous que le chemin est correct) ---
require_once 'dompdf/autoload.inc.php'; 

// Importe les classes Dompdf nécessaires
use Dompdf\Dompdf;
use Dompdf\Options;

// Démarrer la session et vérifier l'authentification (bonne pratique)
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirection si non connecté (le PDF ne devrait pas être accessible sans auth)
    exit('Accès non autorisé.');
}


// --- Fonction utilitaire pour la couleur des statuts (réutilisation de modifier_gest_actif.php) ---
function getStatusColor($status) {
    switch ($status) {
        case 'En service': return '#198754'; // Vert Bootstrap success
        case 'En stock': return '#0dcaf0';    // Bleu Bootstrap info
        case 'En réparation': return '#ffc107'; // Jaune Bootstrap warning
        case 'Volé':
        case 'Déclassé':
        case 'Hors service': return '#dc3545'; // Rouge Bootstrap danger
        default: return '#6c757d';           // Gris Bootstrap secondary
    }
}

// --- 1. Vérification et Récupération de l'ID ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    exit('ID de l\'actif manquant.');
}
$actifId = $_GET['id'];
$actif = [];
$historique = [];

try {
    // --- 2. Récupération des données de l'actif ---
    $stmtActif = $pdo->prepare("SELECT * FROM t_actif WHERE id_actif = ?");
    $stmtActif->execute([$actifId]);
    $actif = $stmtActif->fetch(PDO::FETCH_ASSOC);

    if (!$actif) {
        exit("Actif non trouvé avec l'ID: {$actifId}.");
    }

    // --- 3. Récupération de l'historique ---
    $stmtHist = $pdo->prepare("SELECT * FROM t_historique_actif WHERE Id_actif_original = ? ORDER BY date_historique DESC");
    $stmtHist->execute([$actifId]);
    $historique = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    exit("Erreur de base de données : " . $e->getMessage());
}


// --- 4. Génération du Contenu HTML pour le PDF ---

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Historique de l\'Actif ' . htmlspecialchars($actif['numero_inventaire'] ?? 'N/A') . '</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container { 
            padding: 20px; 
            max-width: 800px;
        }
        h1 { 
            color: #0d6efd; 
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 5px;
            font-size: 24px;
        }
        h2 {
            font-size: 18px;
            color: #555;
            margin-top: 20px;
        }
        .header-info table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-info table td {
            padding: 8px 0;
            font-size: 12px;
            border-bottom: 1px solid #eee;
        }
        .header-info table td strong {
            display: inline-block;
            width: 150px;
            color: #000;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 10px;
        }
        .history-table th, .history-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .history-table th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 4px;
            color: #fff;
            font-weight: bold;
            display: inline-block;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #999;
            padding: 10px 0;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fiche d\'Historique de l\'Actif</h1>
        
        <h2>Actif Principal : ' . htmlspecialchars($actif['type_equipement'] ?? '') . '</h2>
        <div class="header-info">
            <table>
                <tr>
                    <td><strong>N° Inventaire:</strong> ' . htmlspecialchars($actif['numero_inventaire'] ?? 'N/A') . '</td>
                    <td><strong>Marque / Modèle:</strong> ' . htmlspecialchars($actif['nom_equipement'] ?? $actif['nom_equipement'] ?? 'N/A') . '</td>
                </tr>
                <tr>
                    <td><strong>Statut Actuel:</strong> 
                        <span class="status-badge" style="background-color: ' . getStatusColor($actif['statut'] ?? 'N/A') . ';">
                            ' . htmlspecialchars($actif['statut'] ?? 'N/A') . '
                        </span>
                    </td>
                    <td><strong>Affecté à:</strong> ' . htmlspecialchars($actif['affecter_a'] ?? 'N/A') . '</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Spécification:</strong> ' . htmlspecialchars($actif['specification'] ?? 'N/A') . '</td>
                </tr>
            </table>
        </div>
        
        <h2>Historique des Changements</h2>
';

if (count($historique) > 0) {
    $html .= '
        <table class="history-table">
            <thead>
                <tr>
                    <th width="15%">Date</th>
                    <th width="20%">Statut Précédent → Nouveau</th>
                    <th width="20%">Ancienne → Nouvelle Affectation</th>
                    <th width="35%">Commentaire de Changement</th>
                    <th width="10%">Par</th>
                </tr>
            </thead>
            <tbody>
    ';
    
    foreach ($historique as $h) {
        $nouveau_statut = htmlspecialchars($h['nouveau_statut'] ?? $h['statut'] ?? 'N/A');
        $couleur_nouveau_statut = getStatusColor($nouveau_statut);
        
        $statut_display = htmlspecialchars($h['ancien_statut'] ?? 'N/A') . ' → 
            <span class="status-badge" style="background-color: ' . $couleur_nouveau_statut . ';">' . $nouveau_statut . '</span>';
        
        $affectation_display = htmlspecialchars($h['ancien_affectation'] ?? 'N/A') . ' → 
            <strong>' . htmlspecialchars($h['nouvelle_affectation'] ?? $h['affecter_a'] ?? 'N/A') . '</strong>';
        
        $html .= '
            <tr>
                <td>' . htmlspecialchars(date('d/m/Y H:i', strtotime($h['date_historique'] ?? 'NOW'))) . '</td>
                <td>' . $statut_display . '</td>
                <td>' . $affectation_display . '</td>
                <td>' . htmlspecialchars($h['commentaire_changement'] ?? $h['commentaire'] ?? 'Aucun commentaire.') . '</td>
                <td>' . htmlspecialchars($h['modifier_par'] ?? $h['creer_par'] ?? 'Inconnu') . '</td>
            </tr>
        ';
    }

    $html .= '
            </tbody>
        </table>
    ';
} else {
    $html .= '<p>Aucun historique enregistré pour cet actif.</p>';
}

$html .= '
    </div>
    <div class="footer">
        Généré le ' . date('d/m/Y H:i:s') . ' par ' . htmlspecialchars($_SESSION['user_nom'] ?? 'Système') . '.
    </div>
</body>
</html>
';

// --- 5. Configuration et Génération du PDF avec Dompdf ---

// Configuration des options
$options = new Options();
$options->set('defaultFont', 'Arial');
// Dompdf ne gère pas parfaitement toutes les fonctionnalités CSS (comme box-shadow ou certaines polices)
// Nous devons ajuster les options si nécessaire pour l'encodage et le rendu des images
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true); // Pour le support de PHP dans l'HTML si vous en avez besoin (souvent non requis)

// Instanciation de Dompdf
$dompdf = new Dompdf($options);

// Charger l'HTML dans Dompdf
$dompdf->loadHtml($html);

// (Optionnel) Définir la taille du papier et l'orientation
$dompdf->setPaper('A4', 'portrait');

// Rendre le HTML en PDF
$dompdf->render();

// --- 6. Sortie du PDF (Stream) ---

// Nom du fichier pour le téléchargement
$filename = 'Historique_Actif_' . htmlspecialchars($actif['numero_inventaire'] ?? 'N/A') . '.pdf';

// Streamer le fichier au navigateur
$dompdf->stream($filename, [
    'Attachment' => true // true pour forcer le téléchargement, false pour afficher dans le navigateur
]);
<?php
session_start();

// --- GESTION DE LA LANGUE (Récupération de la session) ---
// La langue est définie dans menu.php. On la récupère ici.
$current_lang = $_SESSION['lang'] ?? 'fr'; // Utiliser 'fr' par défaut si la session n'existe pas

// Dictionnaire de traduction pour dashboard.php
$texts = [
    'fr' => [
        'title' => 'Tableau de Bord - Gestion des Actifs',
        'dashboard_title' => 'Tableau de Bord',
        'general_totals' => 'Totaux Généraux',
        'total_assets' => 'Actifs Totaux',
        'staff_personnel' => 'Utilisateurs Totaux (Staff)', // Titre ajusté
        'equipment_types' => 'Types d\'Équipement', // Gardé mais sa valeur sera 0 ou basé sur une autre table
        'asset_status' => 'Statut des Actifs',
        'in_service' => 'En Service',
        'in_stock' => 'En Stock',
        'in_repair' => 'En Réparation',
        'out_of_service' => 'Hors Service / Volé',
        'decommissioned' => 'Déclassés',
        'user_profiles' => 'Profils d\'Utilisateurs',
        'profile_it' => 'Profil IT',
        'profile_admin' => 'Profil Administration',
        'profile_finance' => 'Profil Finance',
        'db_error' => 'Erreur de base de données lors de la récupération des statistiques : '
    ],
    'en' => [
        'title' => 'Dashboard - Asset Management',
        'dashboard_title' => 'Dashboard',
        'general_totals' => 'General Totals',
        'total_assets' => 'Total Assets',
        'staff_personnel' => 'Total Users (Staff)', // Titre ajusté
        'equipment_types' => 'Equipment Types', // Gardé mais sa valeur sera 0 ou basé sur une autre table
        'asset_status' => 'Asset Status',
        'in_service' => 'In Service',
        'in_stock' => 'In Stock',
        'in_repair' => 'In Repair',
        'out_of_service' => 'Out of Service / Stolen',
        'decommissioned' => 'Decommissioned',
        'user_profiles' => 'User Profiles',
        'profile_it' => 'IT Profile',
        'profile_admin' => 'Administration Profile',
        'profile_finance' => 'Finance Profile',
        'db_error' => 'Database error while retrieving statistics: '
    ]
];

// Sélectionner les textes dans la langue actuelle
$T = $texts[$current_lang];

// Vérifier si l'utilisateur est connecté, sinon le rediriger
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connexion.php'; // Votre connexion PDO PostgreSQL

// Initialiser les variables pour stocker les totaux
$total_staff = 0; // Sera le total des t_users
$total_entite = 0; // Mis à 0 car la table n'existe plus
$total_actif = 0;

$total_en_service = 0;
$total_en_stock = 0;
$total_en_reparation = 0;
$total_hors_service = 0;
$total_declasse = 0;

$total_profil_it = 0; 
$total_profil_administration = 0;
$total_profil_finance = 0;
$error_message = null; // Initialisation du message d'erreur

try {
    // --- 1. Statistiques des Utilisateurs (t_users) ---
    
    // Total des Utilisateurs (Remplacant l'ancien "Total du Personnel")
    $stmt_users = $pdo->prepare("SELECT COUNT(*) FROM t_users");
    $stmt_users->execute();
    $total_staff = $stmt_users->fetchColumn(); // total_staff = total users

    // Total des Types d'Entité (Remplacé par 0 car la table n'existe plus)
    // Nous conservons la variable $total_entite mais la mettons à 0 pour éviter une erreur SQL.
    $total_entite = 0; 
    
    // Total des Profils (t_users)
    // Nous comptons tous les utilisateurs par profil
    $stmt_profils = $pdo->prepare("SELECT profil, COUNT(*) as count FROM t_users GROUP BY profil");
    $stmt_profils->execute();
    $profil_counts = $stmt_profils->fetchAll(PDO::FETCH_KEY_PAIR); // Récupère ['profil' => count]

    $total_profil_it = $profil_counts['IT'] ?? 0;
    $total_profil_administration = $profil_counts['Administration'] ?? 0;
    $total_profil_finance = $profil_counts['Finance'] ?? 0;


    // --- 2. Statistiques des Actifs (t_actif) ---
    // Total des Actifs
    $stmt_actif = $pdo->prepare("SELECT COUNT(*) FROM t_actif");
    $stmt_actif->execute();
    $total_actif = $stmt_actif->fetchColumn();

    // Statistiques par Statut (t_actif)
    $stmt_statuts = $pdo->prepare("SELECT statut, COUNT(*) as count FROM t_actif GROUP BY statut");
    $stmt_statuts->execute();
    $statut_counts = $stmt_statuts->fetchAll(PDO::FETCH_KEY_PAIR);

    $total_en_service = $statut_counts['En service'] ?? 0;
    $total_en_stock = $statut_counts['En stock'] ?? 0;
    $total_en_reparation = $statut_counts['En réparation'] ?? 0;
    $total_hors_service = ($statut_counts['Volé'] ?? 0) + ($statut_counts['Hors service'] ?? 0); // Regroupe
    $total_declasse = $statut_counts['Déclassé'] ?? 0;

} catch (PDOException $e) {
    // Gérer l'erreur de connexion ou de requête
    $error_message = $T['db_error'] . $e->getMessage();
}

// Fonction utilitaire pour le design (badge de statut) - Inchangée
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'En service': return 'bg-success';
        case 'En stock': return 'bg-info';
        case 'En réparation': return 'bg-warning text-dark';
        case 'Déclassé':
        case 'Hors service': return 'bg-danger';
        default: return 'bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $T['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .dashboard-card {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.3s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        .card-icon {
            font-size: 3rem;
            color: #007bff; /* Couleur principale */
            margin-right: 20px;
            padding: 10px;
            border-radius: 50%;
            background-color: rgba(0, 123, 255, 0.1);
        }
        .card-title {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
        }
        .card-value {
            font-size: 2rem;
            font-weight: 700;
            color: #343a40;
        }
    </style>
</head>
<body>

<?php 
// Affichage du message d'erreur si une erreur BD est survenue
if (isset($error_message) && $error_message !== null): ?>
    <div class="container mt-4">
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error_message) ?>
        </div>
    </div>
<?php endif; ?>

<div class="container mt-5">
    
    <h1 class="mb-4"><i class="bi bi-speedometer2"></i> <?php echo $T['dashboard_title']; ?></h1>

    <div class="row">
        <h3 class="mb-3 text-secondary"><i class="bi bi-grid-fill"></i> <?php echo $T['general_totals']; ?></h3>
        
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card">
                <i class="bi bi-laptop card-icon"></i>
                <div>
                    <div class="card-title"><?php echo $T['total_assets']; ?></div>
                    <div class="card-value"><?php echo htmlspecialchars($total_actif); ?></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card">
                <i class="bi bi-people-fill card-icon" style="color:#28a745; background-color: rgba(40, 167, 69, 0.1);"></i>
                <div>
                    <div class="card-title"><?php echo $T['staff_personnel']; ?></div>
                    <div class="card-value"><?php echo htmlspecialchars($total_staff); ?></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card">
                <i class="bi bi-box-seam card-icon" style="color:#ffc107; background-color: rgba(255, 193, 7, 0.1);"></i>
                <div>
                    <div class="card-title"><?php echo $T['equipment_types']; ?></div>
                    <div class="card-value"><?php echo htmlspecialchars($total_entite); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <h3 class="mb-3 text-secondary"><i class="bi bi-bar-chart-fill"></i> <?php echo $T['asset_status']; ?></h3>
        
        <div class="col-md-6 col-lg-3">
            <div class="dashboard-card">
                <i class="bi bi-check-circle-fill card-icon" style="color:#28a745; background-color: rgba(40, 167, 69, 0.1);"></i>
                <div>
                    <div class="card-title"><?php echo $T['in_service']; ?></div>
                    <div class="card-value"><?php echo htmlspecialchars($total_en_service); ?></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="dashboard-card">
                <i class="bi bi-box-fill card-icon" style="color:#17a2b8; background-color: rgba(23, 162, 184, 0.1);"></i>
                <div>
                    <div class="card-title"><?php echo $T['in_stock']; ?></div>
                    <div class="card-value"><?php echo htmlspecialchars($total_en_stock); ?></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="dashboard-card">
                <i class="bi bi-tools card-icon" style="color:#ffc107; background-color: rgba(255, 193, 7, 0.1);"></i>
                <div>
                    <div class="card-title"><?php echo $T['in_repair']; ?></div>
                    <div class="card-value"><?php echo htmlspecialchars($total_en_reparation); ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="dashboard-card">
                <i class="bi bi-slash-circle-fill card-icon" style="color:#dc3545; background-color: rgba(220, 53, 69, 0.1);"></i>
                <div>
                    <div class="card-title"><?php echo $T['out_of_service']; ?></div>
                    <div class="card-value"><?php echo htmlspecialchars($total_hors_service); ?></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="dashboard-card">
                <i class="bi bi-archive-fill card-icon" style="color:#6c757d; background-color: rgba(108, 117, 125, 0.1);"></i>
                <div>
                    <div class="card-title"><?php echo $T['decommissioned']; ?></div>
                    <div class="card-value"><?php echo htmlspecialchars($total_declasse); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <h3 class="mb-3 text-secondary"><i class="bi bi-person-lines-fill"></i> <?php echo $T['user_profiles']; ?></h3>
        
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card">
                <i class="bi bi-headset-vr card-icon" style="color:#0d6efd; background-color: rgba(13, 110, 253, 0.1);"></i>
                <div>
                    <div class="card-title"><?php echo $T['profile_it']; ?></div>
                    <div class="card-value"><?php echo htmlspecialchars($total_profil_it); ?></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card">
                <i class="bi bi-gear-fill card-icon" style="color:#6f42c1; background-color: rgba(111, 66, 193, 0.1);"></i>
                <div>
                    <div class="card-title"><?php echo $T['profile_admin']; ?></div>
                    <div class="card-value"><?php echo htmlspecialchars($total_profil_administration); ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card">
                <i class="bi bi-cash card-icon" style="color:#fd7e14; background-color: rgba(253, 126, 20, 0.1);"></i>
                <div>
                    <div class="card-title"><?php echo $T['profile_finance']; ?></div>
                    <div class="card-value"><?php echo htmlspecialchars($total_profil_finance); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
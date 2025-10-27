<?php
// Démarrer la session
session_start();

// --- GESTION DE LA LANGUE ---
// Définir la langue par défaut si elle n'est pas déjà définie
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'fr'; // Français par défaut
}

// Changer la langue si un formulaire a été soumis
if (isset($_POST['lang_switch'])) {
    $new_lang = $_POST['lang_switch'] === 'en' ? 'en' : 'fr';
    $_SESSION['lang'] = $new_lang;
    
    // ***************************************************************
    // CORRECTION MAJEURE: Suppression de la redirection PHP (cause du 404).
    // La mise à jour de la session est la seule action ici.
    // ***************************************************************
    exit(); 
}

$current_lang = $_SESSION['lang'];

// Dictionnaire de traduction
$texts = [
    'fr' => [
        'welcome' => 'Bienvenue, ',
        'disconnect' => 'Déconnexion',
        'confirm_disconnect' => 'Êtes-vous sûr(e) de vouloir vous déconnecter ?',
        'dashboard' => 'Tableau de bord',
        'gest_appro' => 'Gest. Approvisionnements',
        'equipement' => 'Équipement',
        'actifs' => 'Actifs',
        'localite' => 'Localité',
        'staff' => 'Staff',
        'utilisateur' => 'Utilisateur',
        'rapport' => 'Rapport',
        'notification' => 'Notification',
        'menu_title' => 'Menu Principal',
        'change_password' => 'Changer le mot de passe',
        'deconnexion_php' => 'deconnexion',
        'password_reset' => 'Réinitialisation Mot de Passe',
        'confirm_password_change' => 'Confirmer le changement de mot de passe',
        // --- NOUVEAUX TEXTES POUR LA DÉCONNEXION AUTOMATIQUE ---
        'modal_title' => 'Avertissement d\'inactivité',
        'modal_message' => 'Vous n\'avez effectué aucune action. Vous serez déconnecté dans',
        'modal_continue' => 'Cliquez ici pour continuer',
    ],
    'en' => [
        'welcome' => 'Welcome, ',
        'disconnect' => 'Logout',
        'confirm_disconnect' => 'Are you sure you want to log out?',
        'dashboard' => 'Dashboard',
        'gest_appro' => 'Supply Management',
        'equipement' => 'Equipment',
        'actifs' => 'Assets',
        'localite' => 'Location',
        'staff' => 'Staff',
        'utilisateur' => 'User',
        'rapport' => 'Report',
        'notification' => 'Notification',
        'menu_title' => 'Main Menu',
        'change_password' => 'Change Password',
        'deconnexion_php' => 'deconnexion', 
        'password_reset' => 'Password Reset',
        'confirm_password_change' => 'Confirm password change',
        // --- NOUVEAUX TEXTES POUR LA DÉCONNEXION AUTOMATIQUE ---
        'modal_title' => 'Inactivity Warning',
        'modal_message' => 'You have been inactive. You will be logged out in',
        'modal_continue' => 'Click here to continue',
    ]
];

// Sélectionner les textes dans la langue actuelle
$T = $texts[$current_lang];

// Vérification de la connexion
if (!isset($_SESSION['user_id']) || !isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    session_unset();
    session_destroy();
    header("Location: index");
    exit();
}

// Empêcher le retour à la page précédente via le navigateur
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Récupérer le profil de l'utilisateur
$user_profil = isset($_SESSION['user_profil']) ? $_SESSION['user_profil'] : '';

// Fonction pour vérifier l'autorisation.
function estAutorise($profils_autorises) {
    global $user_profil;
    return in_array($user_profil, $profils_autorises);
}

// Définir les autorisations
$autorisations = [
    'dashboard' => ['Administration', 'Finance', 'IT'],
    'gest_appro' => ['Administration', 'IT'], 
    'equipement' => ['IT'], 
    'actif' => ['Administration', 'Finance', 'IT'], 
    'utilisateur' => ['IT'], 
    'localite' => ['IT'], 
    'staff' => ['IT'],
    'rapport' => ['Administration', 'Finance', 'IT'], 
    'notification' => ['Administration', 'Finance', 'IT'], 
    'change_password' => ['Administration', 'Finance', 'IT'],
    
    // Sous-menus et pages de rapport spécifiques
    'rapport_stock' => ['Administration', 'IT'],
    'rapport_utilisation' => ['IT'],
    'rapport_amorti' => ['Finance', 'IT'],
    'rapport_declasse' => ['Administration', 'IT'],
    'rapport_vole' => ['Administration', 'IT'],
    'notification_amortissement' => ['IT'],
];

?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $T['menu_title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Styles CSS (Inchangé) */
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f7f9; }
        .sidebar { height: 100%; width: 250px; position: fixed; top: 0; left: 0; background-color: #0d243a; padding-top: 20px; color: white; transition: all 0.3s; overflow-y: auto; }
        .sidebar-header { text-align: center; padding: 10px; margin-bottom: 20px; }
        .sidebar-header h3 { font-size: 1.5rem; margin-bottom: 5px; color: #ecf0f1; }
        .sidebar-header p { font-size: 0.9rem; color: #bdc3c7; margin-bottom: 10px; }
        .lang-switcher { margin-bottom: 15px; display: inline-block; background-color: #1c3b57; padding: 5px 10px; border-radius: 5px; }
        .lang-switcher button { background: none; border: none; color: #ecf0f1; padding: 5px; cursor: pointer; font-weight: bold; transition: color 0.2s; }
        .lang-switcher button.active { color: #3498db; text-decoration: underline; }
        .lang-switcher button:not(.active):hover { color: #bdc3c7; }
        .sidebar ul { list-style-type: none; padding: 0; margin: 0; }
        .sidebar ul li { padding: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.3s; }
        .sidebar ul li a { color: #ecf0f1; text-decoration: none; display: block; font-size: 1rem; }
        .sidebar ul li a i { margin-right: 10px; font-size: 1.2rem; width: 25px; text-align: center; }
        .sidebar ul li:hover { background-color: #1c3b57; cursor: pointer; }
        .sidebar .active { background-color: #3498db; border-left: 5px solid #2980b9; }
        .content { margin-left: 250px; padding: 0; height: 100vh; }
        iframe { width: 100%; height: 100%; border: none; }
        .logout-btn { background-color: #e74c3c; color: white; border-radius: 5px; padding: 10px; width: 80%; margin: 20px auto; text-align: center; font-size: 1rem; transition: background-color 0.3s; text-decoration: none; display: block; }
        .logout-btn:hover { background-color: #c0392b; color: white; }
        .menu-parent { position: relative; }
        .menu-parent .bi-chevron-right { float: right; transition: transform 0.3s ease-in-out; }
        .menu-parent.expanded .bi-chevron-right { transform: rotate(90deg); }
        .submenu { display: none; list-style-type: none; background-color: #1c3b57; padding: 0; margin-top: 10px; }
        .submenu li { padding: 10px 15px 10px 30px; border-bottom: none; }
        
        /* NOUVEAU STYLE: Modale d'inactivité toujours au-dessus */
        #inactivityModal {
            pointer-events: none; /* Permet aux clics de passer à l'arrière-plan */
            z-index: 1050; /* S'assurer qu'elle est au-dessus */
        }
        #inactivityModal .modal-dialog {
            pointer-events: all; /* Rétablit les événements pour la modale elle-même */
            margin-top: 15vh; /* Positionner la modale un peu plus haut */
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h3><?php echo $T['welcome'] . htmlspecialchars($_SESSION['user_nom']); ?></h3>
            <p><?php echo htmlspecialchars($_SESSION['user_profil']); ?></p>
            
            <form method="POST" class="lang-switcher" id="lang-form">
                <button type="submit" name="lang_switch" value="fr" class="<?php echo ($current_lang === 'fr' ? 'active' : ''); ?>">FR</button>
                |
                <button type="submit" name="lang_switch" value="en" class="<?php echo ($current_lang === 'en' ? 'active' : ''); ?>">EN</button>
            </form>
        </div>
        <ul>
            <?php if (estAutorise($autorisations['dashboard'])): ?>
            <li class="active">
                <a href="dashboard" target="mainFrame"><i class="bi bi-speedometer2"></i> <?php echo $T['dashboard']; ?></a>
            </li> 
            <?php endif; ?>

            <?php if (estAutorise($autorisations['equipement'])): ?>
            <li class="">
                <a href="liste_actif" target="mainFrame"><i class="bi bi-pc-display-horizontal"></i> <?php echo $T['equipement']; ?></a>
            </li> 
            <?php endif; ?>

            <?php if (estAutorise($autorisations['actif'])): ?>
            <li class="">
                <a href="liste_gest_actif" target="mainFrame"><i class="bi bi-archive-fill"></i> <?php echo $T['actifs']; ?></a>
            </li> 
            <?php endif; ?>


            <?php if (estAutorise($autorisations['utilisateur'])): ?>
            <li class="">
                <a href="liste_utilisateur" target="mainFrame"><i class="bi bi-person-circle"></i> <?php echo $T['utilisateur']; ?></a>
            </li> 
            <?php endif; ?>


            <?php if (estAutorise($autorisations['rapport'])): ?>
            <li class="">
                <a href="liste_rapport" target="mainFrame"><i class="bi bi-file-earmark-bar-graph-fill"></i> <?php echo $T['rapport']; ?></a>
            </li> 
            <?php endif; ?>

            <?php if (estAutorise($autorisations['notification'])): ?>
            <li class="">
                <a href="notification" target="mainFrame"><i class="bi bi-bell-fill"></i> <?php echo $T['notification']; ?></a>
            </li> 
            <?php endif; ?>

            <?php if (estAutorise($autorisations['change_password'])): ?>
            <li class="">
                <a href="changer_mot_de_passe" target="mainFrame" title="<?php echo $T['confirm_password_change']; ?>">
                    <i class="bi bi-key-fill"></i> <?php echo $T['change_password']; ?>
                </a>
            </li> 
            <?php endif; ?>
        </ul>
        <a href="<?php echo $T['deconnexion_php']; ?>" class="logout-btn" onclick="return confirm('<?php echo $T['confirm_disconnect']; ?>');">
            <i class="bi bi-box-arrow-right"></i> <?php echo $T['disconnect']; ?>
        </a>
    </div>

    <div class="content">
        <iframe name="mainFrame" id="mainFrame" src="dashboard"></iframe>
    </div>

    <div class="modal fade" id="inactivityModal" tabindex="-1" aria-labelledby="inactivityModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-danger border-5">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="inactivityModalLabel"><i class="bi bi-clock-history me-2"></i> <?php echo $T['modal_title']; ?></h5>
                </div>
                <div class="modal-body text-center">
                    <p class="fs-5"><?php echo $T['modal_message']; ?></p>
                    <h1 class="display-1 text-danger fw-bold" id="countdownTimer">60</h1>
                    <p class="text-muted">(secondes)</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-success btn-lg" id="continueButton">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $T['modal_continue']; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // Initialisation des éléments et de la logique de base (changement de langue, navigation)
            const langForm = document.getElementById('lang-form');
            const mainFrame = document.getElementById('mainFrame'); 
            const inactivityModal = new bootstrap.Modal(document.getElementById('inactivityModal'));
            const countdownTimerDisplay = document.getElementById('countdownTimer');
            const logoutUrl = '<?php echo $T['deconnexion_php']; ?>';

            // --- PARAMÈTRES DE DÉCONNEXION ---
            const INACTIVITY_TIME = 300000; // 5 minutes d'inactivité avant d'afficher l'alerte
            const COUNTDOWN_TIME = 60; // 60 secondes de compte à rebours
            let timeoutId; // ID du minuteur principal
            let countdownIntervalId; // ID de l'intervalle de compte à rebours

            // Fonction de déconnexion forcée
            function forceLogout() {
                // S'assurer que tous les minuteurs sont effacés
                clearTimeout(timeoutId);
                clearInterval(countdownIntervalId);
                // Redirection vers la page de déconnexion
                window.location.href = logoutUrl;
            }

            // Fonction de démarrage/redémarrage du compte à rebours principal (inactivité)
            function startInactivityTimer() {
                // 1. Annuler l'ancien minuteur s'il existe
                clearTimeout(timeoutId); 
                
                // 2. Démarrer un nouveau minuteur
                timeoutId = setTimeout(() => {
                    // 3. Après INACTIVITY_TIME (30s), afficher la modale et démarrer le compte à rebours
                    inactivityModal.show();
                    startCountdown();
                }, INACTIVITY_TIME);
            }

            // Fonction de démarrage du compte à rebours de déconnexion (une fois la modale affichée)
            function startCountdown() {
                let timeLeft = COUNTDOWN_TIME;
                countdownTimerDisplay.textContent = timeLeft;
                
                // Effacer l'intervalle précédent pour éviter les superpositions
                clearInterval(countdownIntervalId);

                countdownIntervalId = setInterval(() => {
                    timeLeft--;
                    countdownTimerDisplay.textContent = timeLeft;

                    if (timeLeft <= 0) {
                        forceLogout();
                    }
                }, 1000); // Mise à jour toutes les secondes
            }

            // Fonction pour réinitialiser la session (appelée lors d'une activité)
            function resetSession() {
                // 1. Cacher la modale si elle est visible
                inactivityModal.hide();
                // 2. Annuler le compte à rebours
                clearInterval(countdownIntervalId); 
                // 3. Redémarrer le minuteur d'inactivité
                startInactivityTimer();
            }

            // --- GESTION DES ÉVÉNEMENTS D'ACTIVITÉ (SOURIS, CLAVIER, CLIC) ---
            const activityEvents = ['mousemove', 'keypress', 'click'];
            activityEvents.forEach(event => {
                document.body.addEventListener(event, resetSession, true); // true = capture phase
                // On attache aussi l'événement à l'iframe pour capturer l'activité à l'intérieur
                mainFrame.contentWindow.document.body.addEventListener(event, resetSession, true);
            });
            
            // Gérer le clic sur le bouton "Continuer" de la modale
            document.getElementById('continueButton').addEventListener('click', function() {
                resetSession();
            });

            // Démarrez le minuteur principal au chargement de la page
            startInactivityTimer();


            // --- 1. Gérer la soumission du formulaire de langue (LOGIQUE PRÉCÉDENTE) ---
            if (langForm && mainFrame) {
                // ... (Votre code pour le changement de langue) ...
                langForm.addEventListener('submit', function(e) {
                    // Empêcher l'envoi normal du formulaire qui cause un rechargement inutile du parent
                    e.preventDefault(); 

                    // Récupérer l'URL actuelle de l'iframe avant la soumission
                    let iframeSrc = mainFrame.getAttribute('src');
                    try {
                        // Utilisation du chemin dans l'iframe si possible, sinon l'attribut src
                        const iframeLocation = mainFrame.contentWindow.location.pathname.substring(1);
                        if (iframeLocation !== '' && iframeLocation !== '/') {
                            iframeSrc = iframeLocation;
                        }
                    } catch (error) {
                        console.warn("Same-Origin Policy empêche d'obtenir l'URL exacte. Utilisation de l'attribut src par défaut.");
                    }
                    
                    // Récupérer la langue sélectionnée (via le bouton cliqué)
                    const submittedButton = e.submitter;
                    const newLang = submittedButton ? submittedButton.value : null;

                    if (!newLang) return; // Ne rien faire si aucune langue n'est soumise

                    // 1. Envoyer la requête au PHP pour mettre à jour la session
                    const formData = new FormData();
                    formData.append('lang_switch', newLang);

                    fetch('menu.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.ok) {
                            // 2. Si la session a été mise à jour avec succès (PHP a fait un exit):
                            
                            // Recharger la page parent (menu.php) pour mettre à jour les textes du menu
                            // Ceci est critique pour traduire les boutons du menu.
                            window.location.href = window.location.pathname; 
                            
                            // 3. Forcer l'iframe à recharger la page où l'utilisateur était
                            // Ceci est critique pour que la page enfant lise la nouvelle session
                            mainFrame.src = iframeSrc; 
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors du changement de langue:', error);
                        alert('Une erreur est survenue lors du changement de langue.');
                    });
                });
            }
            
            // --- 2. Gérer les clics sur les liens du menu (pour la persistance future - LOGIQUE PRÉCÉDENTE) ---
            const sidebarLinks = document.querySelectorAll('.sidebar ul a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    
                    // Gestion du surlignage (inchangée)
                    document.querySelectorAll('.sidebar ul li').forEach(item => item.classList.remove('active'));
                    let parentLi = this.closest('li');
                    if (parentLi) {
                        parentLi.classList.add('active');
                        let grandParentLi = this.closest('.submenu')?.closest('.menu-parent');
                        if (grandParentLi) {
                            grandParentLi.classList.add('active');
                        }
                    }
                    
                    // Mettre à jour l'iframe
                    const targetFrame = document.querySelector('iframe[name="mainFrame"]');
                    const newHref = this.getAttribute('href');

                    if (targetFrame && newHref) {
                        targetFrame.src = newHref;
                        e.preventDefault(); 
                    }
                });
            });
            
            // --- 3. Logique de sous-menu (conservée - LOGIQUE PRÉCÉDENTE) ---
            const menuParents = document.querySelectorAll('.menu-parent > a');
            menuParents.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const parentLi = this.parentElement;
                    const submenu = parentLi.querySelector('.submenu');
                    if (submenu) {
                        if (submenu.style.display === 'block') {
                            submenu.style.display = 'none';
                            parentLi.classList.remove('expanded');
                        } else {
                            document.querySelectorAll('.submenu').forEach(sub => sub.style.display = 'none');
                            document.querySelectorAll('.menu-parent').forEach(p => p.classList.remove('expanded'));
                            submenu.style.display = 'block';
                            parentLi.classList.add('expanded');
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
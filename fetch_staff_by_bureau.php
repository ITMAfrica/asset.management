<?php

session_start();

// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}


// 1. Définir le type de contenu de la réponse en JSON
header('Content-Type: application/json; charset=utf-8');

// 2. Inclure le fichier de connexion à la base de données
require_once 'db_connexion.php'; 

// Fonction pour envoyer une réponse d'erreur en JSON et terminer le script
function send_json_error($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['error' => $message]);
    exit();
}

// 3. Vérifier si le paramètre 'bureau' a été envoyé via POST
if (!isset($_POST['bureau']) || empty(trim($_POST['bureau']))) {
    send_json_error('Le paramètre "bureau" est manquant ou vide.');
}

$selectedBureau = $_POST['bureau'];
$staffList = [];

try {
    // 4. Préparer la requête SQL pour récupérer les noms complets du personnel
    // attachés au bureau sélectionné, triés par ordre alphabétique.
    $sql = "SELECT nom_complet 
            FROM t_staff 
            WHERE bureau = ? 
            ORDER BY nom_complet ASC";
            
    $stmt = $pdo->prepare($sql);
    
    // 5. Exécuter la requête avec le paramètre bureau
    $stmt->execute([$selectedBureau]);
    
    // 6. Récupérer tous les résultats sous forme d'un tableau simple (une seule colonne)
    $staffList = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 7. Envoyer la liste du personnel en format JSON
    echo json_encode($staffList);

} catch (PDOException $e) {
    // En cas d'erreur de base de données, envoyer un message d'erreur
    // Il est préférable de ne pas afficher $e->getMessage() en production pour des raisons de sécurité.
    send_json_error('Erreur de base de données.', 500);
}
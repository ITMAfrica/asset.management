<?php

// Paramètres de connexion
$host = 'localhost'; // L'adresse de votre serveur de base de données
$dbname = 'actif'; // Le nom de votre base de données
$user = 'postgres'; // Votre nom d'utilisateur PostgreSQL
$password = '2025'; // Votre mot de passe PostgreSQL

try {
    // Création de la chaîne de connexion DSN (Data Source Name)
    $dsn = "pgsql:host=$host;dbname=$dbname";

    // Création d'une nouvelle instance PDO
    $pdo = new PDO($dsn, $user, $password);

    // Définition des options pour la gestion des erreurs
    // Cela garantit que PDO lance des exceptions en cas d'erreur SQL, ce qui est très utile pour le débogage
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Afficher un message de succès (facultatif)
    //echo "Connexion à la base de données réussie ! 🎉";

} catch (PDOException $e) {
    // Si la connexion échoue, afficher l'erreur
    // Utilisation de die() pour arrêter l'exécution du script
    die("Erreur de connexion : " . $e->getMessage());
}

?>
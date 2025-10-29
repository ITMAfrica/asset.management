<?php

// // Configuration pour Render
// $host = getenv('DB_HOST') ?: 'localhost';
// $dbname = getenv('DB_NAME') ?: 'actif';
// $user = getenv('DB_USER') ?: 'postgres';
// $password = getenv('DB_PASSWORD') ?: '2025';

// try {
//     // Création de la chaîne de connexion DSN (Data Source Name)
//     $dsn = "pgsql:host=$host;dbname=$dbname";

//     // Création d'une nouvelle instance PDO
//     $pdo = new PDO($dsn, $user, $password);

//     // Définition des options pour la gestion des erreurs
//     // Cela garantit que PDO lance des exceptions en cas d'erreur SQL, ce qui est très utile pour le débogage
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//     // Afficher un message de succès (facultatif)
//     //echo "Connexion à la base de données réussie ! 🎉";

// } catch (PDOException $e) {
//     // Si la connexion échoue, afficher l'erreur
//     // Utilisation de die() pour arrêter l'exécution du script
//     die("Erreur de connexion : " . $e->getMessage());
// }

// --------------new logique for render -------------------------

// db_connexion.php

// Configuration pour l'affichage des erreurs PHP (pour le DÉBOGAGE UNIQUEMENT - À RETIRER EN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connexion à PostgreSQL via DATABASE_URL (compatible Render, Heroku, etc.)
$dsn_env = getenv("DATABASE_URL");

try {
    if ($dsn_env) {
        // Render fournit DATABASE_URL au format postgres://user:pass@host:port/dbname
        $url = parse_url($dsn_env);
        if ($url && isset($url['host'], $url['user'], $url['pass'], $url['path'])) {
            $host = $url['host'];
            $user = $url['user'];
            $pass = $url['pass'];
            $db   = ltrim($url['path'], '/');
            $port = isset($url['port']) ? $url['port'] : 5432;
            $dsn = "pgsql:host=$host;port=$port;dbname=$db";
            $pdo = new PDO($dsn, $user, $pass);
        } else {
            throw new Exception("DATABASE_URL mal formée");
        }
    } else {
        // Fallback local - utiliser Render même en local
        $host = "dpg-d40ubdqli9vc73bvjbs0-a.frankfurt-postgres.render.com";
        $user = "asset_r13v_user";
        $pass = "U9NFYIT9oeeRu0ov2kEJMrbJ7gGdow4Y";
        $db   = "asset_r13v";
        $port = 5432;
        $dsn = "pgsql:host=$host;port=$port;dbname=$db";
        $pdo = new PDO($dsn, $user, $pass);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
} catch (Exception $e) {
    die("Erreur de configuration de la base de données : " . $e->getMessage());
}

?>
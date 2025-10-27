<?php

// ----------------------------------------------------
// 1. Configuration des Paramètres
// ----------------------------------------------------

// Paramètres de l'URL pour filtrer les utilisateurs
$queryParams = [
    'name' => 'KAT',
    'firstName' => 'PRECIEUX',
    'organisation' => 'PRE',
    'lastName' => 'M',
];

// Token d'authentification (à remplacer par votre token réel)
// ATTENTION : C'est ici que vous devez insérer le jeton valide.
$authToken = '9f800a7213bf7292f0e0d05156f4e906e176a032c99abb59c027511246df721e023b0aa19494c38abfbb5eda155bdab7522041cead0524d8f6804eaa47b4365d7995620f67306bf031cd8139be9927707e3bf453264b306bd285bca017e95080e4faaa1892bc0ea0c76d158fc1b05fb8d94f142d599eda2512597ce58e11c2e8'; 

// ----------------------------------------------------
// 2. Construction de l'URL et des En-têtes
// ----------------------------------------------------

$queryString = http_build_query($queryParams);
$apiUrl = "https://api.kazipro.app/api/authentification/getUsersForInventory?name=KAT&firstName=PRECIEUX&organisation=PRE&lastName=M" . $queryString;

// Préparation des en-têtes HTTP
$headers = [
    // Indique que nous attendons une réponse JSON
    'Accept: application/json', 
    // AJOUT DE L'AUTHENTIFICATION BEARER DANS L'EN-TÊTE
    "Authorization: Bearer $authToken" 
];


// ----------------------------------------------------
// 3. Exécution de la Requête GET avec cURL
// ----------------------------------------------------

$ch = curl_init();

// Configuration des options de base
curl_setopt($ch, CURLOPT_URL, $apiUrl);
// Pour récupérer la réponse comme une chaîne de caractères
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
// Pour inclure les en-têtes personnalisés (y compris le Token)
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
// Temps d'attente maximum
curl_setopt($ch, CURLOPT_TIMEOUT, 15); 

// Exécution de la requête
$response = curl_exec($ch);

// Récupération du statut HTTP et des erreurs cURL
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

// Fermeture de la session cURL
curl_close($ch);

// ----------------------------------------------------
// 4. Gestion de la Réponse
// ----------------------------------------------------

if ($curlError) {
    // Si une erreur de connexion cURL se produit (ex: timeout, URL invalide)
    echo "ERREUR cURL : " . $curlError . "\n";
    
} elseif ($httpCode !== 200) {
    // Si l'API renvoie un statut d'erreur (4xx ou 5xx)
    
    echo "ERREUR HTTP : Code $httpCode\n";
    
    if ($httpCode === 403) {
        // Cas spécifique du 403 Forbidden
        echo "L'accès est INTERDIT (403). Cela signifie que le Token est probablement invalide, expiré, ou que l'utilisateur n'a pas les droits pour cette ressource.\n";
    } elseif ($httpCode === 401) {
        // Cas du 401 Unauthorized (souvent similaire au 403 pour un jeton invalide)
        echo "NON AUTORISÉ (401). Vérifiez la validité de votre Token Bearer.\n";
    }
    
    // Afficher la réponse brute du serveur pour le débogage si elle est disponible
    echo "Réponse du serveur (brute) : " . ($response ?: "Aucune réponse reçue.") . "\n";
    
} else {
    // Cas de succès (Code 200 OK)
    
    // Tentative de décodage de la réponse JSON
    $data = json_decode($response, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "SUCCÈS (200 OK) : Données récupérées et décodées.\n";
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    } else {
        echo "SUCCÈS (200 OK), mais ERREUR de décodage JSON.\n";
        echo "Réponse reçue (brute) : " . $response . "\n";
    }
}

?>
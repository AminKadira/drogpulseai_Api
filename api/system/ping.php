<?php
// Fichier : api/system/ping.php

// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclure la configuration de la base de données
include_once '../config/database.php';


// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Méthode non autorisée", 405);
}
// Initialiser la réponse
$response = [
    "success" => true,
    "message" => "Serveur connecté",
    "timestamp" => time(),
    "database" => [
        "status" => false,
        "message" => "Non vérifié"
    ]
];

// Tester la connexion à la base de données
try {
    // Créer une instance de la classe Database
    $database = new Database();
    $db = $database->getConnection();
    
    // Si nous arrivons ici, c'est que la connexion est réussie
    $response["database"]["status"] = true;
    $response["database"]["message"] = "Connexion à la base de données réussie";
    
    // Effectuer une requête simple pour vérifier que la base fonctionne
    $query = "SELECT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // Vérifier la version de MySQL
    $versionQuery = "SELECT VERSION() as version";
    $versionStmt = $db->prepare($versionQuery);
    $versionStmt->execute();
    $versionResult = $versionStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($versionResult) {
        $response["database"]["version"] = $versionResult["version"];
    }
    
} catch (PDOException $e) {
    // En cas d'erreur de connexion
    $response["database"]["status"] = false;
    $response["database"]["message"] = "Erreur de connexion à la base de données: " . $e->getMessage();
    
    // Optionnellement, définir success à false si la connexion à la base est critique
    // $response["success"] = false;
    // $response["message"] = "Serveur connecté mais problème avec la base de données";
}

// Envoyer la réponse
echo json_encode($response);
?>
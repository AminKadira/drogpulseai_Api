<?php
// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclure les fichiers de configuration et d'utilitaires
include_once '../config/database.php';
include_once '../utils/response.php';

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Méthode non autorisée", 405);
}

// Récupération des données soumises
$data = json_decode(file_get_contents("php://input"));

// Vérification des données requises
if (empty($data->nom) || empty($data->prenom) || empty($data->telephone) || 
    empty($data->email) || empty($data->password)) {
    Response::error("Données incomplètes");
}

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Vérifier si l'email existe déjà
    $query = "SELECT id FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        Response::error("Cet email est déjà utilisé");
    }
    
    // Préparation de la requête d'insertion
    $query = "INSERT INTO users (nom, prenom, telephone, email, latitude, longitude,  password) 
              VALUES (:nom, :prenom, :telephone, :email, :latitude, :longitude, :password)";
    
    $stmt = $db->prepare($query);
    
    // Nettoyer et sécuriser les données
    $data->nom = htmlspecialchars(strip_tags($data->nom));
    $data->prenom = htmlspecialchars(strip_tags($data->prenom));
    $data->telephone = htmlspecialchars(strip_tags($data->telephone));
    $data->email = htmlspecialchars(strip_tags($data->email));
    
    // Hachage du mot de passe
    $password_hash = password_hash($data->password, PASSWORD_DEFAULT);
    
    // Liaison des paramètres
    $stmt->bindParam(":nom", $data->nom);
    $stmt->bindParam(":prenom", $data->prenom);
    $stmt->bindParam(":telephone", $data->telephone);
    $stmt->bindParam(":latitude", $data->latitude);
    $stmt->bindParam(":longitude", $data->longitude);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password", $password_hash);
    
    // Exécution de la requête
    if ($stmt->execute()) {
        Response::success("Compte créé avec succès");
    } else {
        Response::error("Impossible de créer le compte");
    }
} catch (Exception $e) {
    Response::error("Erreur : " . $e->getMessage());
}

?>
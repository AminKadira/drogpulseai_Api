<?php
// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclure les fichiers de configuration et d'utilitaires
include_once '../config/database.php';
include_once '../utils/response.php';

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Méthode non autorisée", 405);
}

// Récupération de l'ID du contact
if (!isset($_GET['id']) || empty($_GET['id'])) {
    Response::error("ID du contact requis");
}

$id = intval($_GET['id']);

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Préparation de la requête
    $query = "SELECT * FROM contacts WHERE id = :id";
    
    $stmt = $db->prepare($query);
    
    // Liaison des paramètres
    $stmt->bindParam(":id", $id);
    
    // Exécution de la requête
    $stmt->execute();
    
    // Vérification si le contact existe
    if ($stmt->rowCount() > 0) {
        // Récupération du résultat
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Créer le tableau de contact
        $contact = array(
            "id" => $row['id'],
            "nom" => $row['nom'],
            "prenom" => $row['prenom'],
            "telephone" => $row['telephone'],
            "email" => $row['email'],
            "type" => $row['type'], // Ajout du nouveau champ
            "notes" => $row['notes'],
            "latitude" => $row['latitude'],
            "longitude" => $row['longitude'],
            "user_id" => $row['user_id']
        );
        
        // Réponse avec les détails du contact
        Response::json($contact);
    } else {
        // Contact non trouvé
        Response::error("Contact non trouvé", 404);
    }
} catch (Exception $e) {
    Response::error("Erreur : " . $e->getMessage());
}
?>
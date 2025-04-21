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

// Vérification des données requises
if (!isset($_GET['user_id']) || empty($_GET['user_id']) || !isset($_GET['query']) || empty($_GET['query'])) {
    Response::error("ID utilisateur et terme de recherche requis");
}

$user_id = intval($_GET['user_id']);
$search_term = $_GET['query'];

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Préparation de la requête
    $query = "SELECT * FROM contacts 
              WHERE user_id = :user_id 
              AND (nom LIKE :search_term 
              OR prenom LIKE :search_term 
              OR telephone LIKE :search_term)
              ORDER BY nom ASC";
    
    $stmt = $db->prepare($query);
    
    // Paramètre de recherche avec jokers
    $search_param = "%" . $search_term . "%";
    
    // Liaison des paramètres
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":search_term", $search_param);
    
    // Exécution de la requête
    $stmt->execute();
    
    // Vérification si des contacts existent
    if ($stmt->rowCount() > 0) {
        // Tableau pour stocker les contacts
        $contacts_arr = array();
        
        // Récupération des résultats
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $contact_item = array(
                "id" => $row['id'],
                "nom" => $row['nom'],
                "prenom" => $row['prenom'],
                "telephone" => $row['telephone'],
                "latitude" => $row['latitude'],
                "longitude" => $row['longitude'],
                "user_id" => $row['user_id']
            );
            
            array_push($contacts_arr, $contact_item);
        }
        
        // Réponse avec la liste des contacts
        Response::json($contacts_arr);
    } else {
        // Aucun contact trouvé
        Response::json(array());
    }
} catch (Exception $e) {
    Response::error("Erreur : " . $e->getMessage());
}
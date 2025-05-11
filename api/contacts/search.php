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

// Activer la journalisation pour le débogage
$debug_file = "../logs/search_debug.log";
file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Début du traitement de search.php\n", FILE_APPEND);

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Méthode non autorisée: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
    Response::error("Méthode non autorisée", 405);
    exit;
}

// Vérification de l'ID utilisateur
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - ID utilisateur requis\n", FILE_APPEND);
    Response::error("ID utilisateur requis");
    exit;
}

$user_id = intval($_GET['user_id']);
$search_term = isset($_GET['query']) ? $_GET['query'] : '';
$type = isset($_GET['type']) && !empty($_GET['type']) ? $_GET['type'] : null;

// Log des paramètres reçus
file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Paramètres reçus - user_id: $user_id, query: $search_term, type: " . ($type ?? "null") . "\n", FILE_APPEND);

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Erreur de connexion à la base de données\n", FILE_APPEND);
    Response::error("Erreur de connexion à la base de données", 500);
    exit;
}

try {
    // Base de la requête pour récupérer les contacts de l'utilisateur
    $query = "SELECT * FROM contacts WHERE user_id = :user_id";
    
    // Ajouter des conditions de recherche si un terme est fourni
    if (!empty($search_term)) {
        $query .= " AND (nom LIKE :search_term 
                  OR prenom LIKE :search_term 
                  OR telephone LIKE :search_term)";
    }
    
    // Ajouter la condition de type si fournie
    if ($type !== null) {
        $query .= " AND type = :type";
    }
    
    // Ajouter le tri
    $query .= " ORDER BY nom ASC";
    
    // Préparation de la requête
    $stmt = $db->prepare($query);
    
    // Liaison des paramètres de base
    $stmt->bindParam(":user_id", $user_id);
    
    // Lier le paramètre de recherche si fourni
    if (!empty($search_term)) {
        $search_param = "%" . $search_term . "%";
        $stmt->bindParam(":search_term", $search_param);
    }
    
    // Lier le paramètre de type si fourni
    if ($type !== null) {
        $stmt->bindParam(":type", $type);
    }
    
    // Log de la requête finale
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Requête SQL: $query\n", FILE_APPEND);
    if ($type !== null) {
        file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Type lié: $type\n", FILE_APPEND);
    }
    
    // Exécution de la requête
    $stmt->execute();
    
    // Log du nombre de résultats
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Nombre de résultats: " . $stmt->rowCount() . "\n", FILE_APPEND);
    
    // Tableau pour stocker les contacts
    $contacts_arr = array();
    
    // Récupération des résultats
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $contact_item = array(
            "id" => $row['id'],
            "nom" => $row['nom'],
            "prenom" => $row['prenom'],
            "telephone" => $row['telephone'],
            "email" => $row['email'],
            "type" => $row['type'], 
            "notes" => $row['notes'],
            "latitude" => $row['latitude'],
            "longitude" => $row['longitude'],
            "user_id" => $row['user_id']
        );
        
        array_push($contacts_arr, $contact_item);
    }
    
    // Réponse avec la liste des contacts (même si vide)
    Response::json($contacts_arr);
    
} catch (Exception $e) {
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Erreur: " . $e->getMessage() . "\n", FILE_APPEND);
    Response::error("Erreur : " . $e->getMessage());
}
?>
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
    exit;
}

// Vérification des données requises
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    Response::error("ID utilisateur requis");
    exit;
}

$user_id = intval($_GET['user_id']);

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Préparation de la requête
    $query = "SELECT * FROM expenses WHERE user_id = :user_id ORDER BY date DESC, created_at DESC";
    
    $stmt = $db->prepare($query);
    
    // Liaison des paramètres
    $stmt->bindParam(":user_id", $user_id);
    
    // Exécution de la requête
    $stmt->execute();
    
    // Vérification si des frais existent
    if ($stmt->rowCount() > 0) {
        // Tableau pour stocker les frais
        $expenses_arr = array();
        
        // Récupération des résultats
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $expense_item = array(
                "id" => $row['id'],
                "type" => $row['type'],
                "amount" => floatval($row['amount']),
                "date" => $row['date'],
                "description" => $row['description'],
                "receipt_photo_url" => $row['receipt_photo_url'],
                "user_id" => $row['user_id'],
                "created_at" => $row['created_at'],
                "updated_at" => $row['updated_at']
            );
            
            array_push($expenses_arr, $expense_item);
        }
        
        // Réponse avec la liste des frais
        Response::json($expenses_arr);
    } else {
        // Aucun frais trouvé
        Response::json(array());
    }
} catch (PDOException $e) {
    // Log l'erreur mais ne pas exposer les détails techniques
    error_log("Database error: " . $e->getMessage());
    Response::error("Erreur de base de données", 500);
} catch (Exception $e) {
    // Log l'erreur mais ne pas exposer les détails techniques
    error_log("Error: " . $e->getMessage());
    Response::error("Une erreur est survenue", 500);
}
?>
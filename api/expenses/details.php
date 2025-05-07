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

// Récupération de l'ID du frais
if (!isset($_GET['id']) || empty($_GET['id'])) {
    Response::error("ID du frais requis");
    exit;
}

$id = intval($_GET['id']);

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Préparation de la requête
    $query = "SELECT * FROM expenses WHERE id = :id";
    
    $stmt = $db->prepare($query);
    
    // Liaison des paramètres
    $stmt->bindParam(":id", $id);
    
    // Exécution de la requête
    $stmt->execute();
    
    // Vérification si le frais existe
    if ($stmt->rowCount() > 0) {
        // Récupération du résultat
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Créer l'objet frais
        $expense = array(
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
        
        // Réponse avec les détails du frais
        Response::json($expense);
    } else {
        // Frais non trouvé
        Response::error("Frais non trouvé", 404);
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
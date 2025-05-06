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

// Récupération de l'ID du produit
if (!isset($_GET['id']) || empty($_GET['id'])) {
    Response::error("ID du produit requis");
    exit;
}

$id = intval($_GET['id']);

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Préparation de la requête
    $query = "SELECT * FROM products WHERE id = :id";
    
    $stmt = $db->prepare($query);
    
    // Liaison des paramètres
    $stmt->bindParam(":id", $id);
    
    // Exécution de la requête
    $stmt->execute();
    
    // Vérification si le produit existe
    if ($stmt->rowCount() > 0) {
        // Récupération du résultat
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Créer l'objet produit
        $product = array(
            "id" => $row['id'],
            "reference" => $row['reference'],
            "label" => $row['label'],
            "name" => $row['name'],
            "description" => $row['description'],
            "photo_url" => $row['photo_url'],
            "barcode" => $row['barcode'],
            "quantity" => $row['quantity'],
            "price" => $row['price'],
            "user_id" => $row['user_id'],
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at']
        );
        
        // Réponse avec les détails du produit
        Response::json($product);
    } else {
        // Produit non trouvé
        Response::error("Produit non trouvé", 404);
    }
} catch (PDOException $e) {
    // Log l'erreur en interne mais ne pas exposer les détails techniques
    error_log("Database error in product details: " . $e->getMessage());
    Response::error("Erreur de base de données", 500);
} catch (Exception $e) {
    // Log l'erreur en interne mais ne pas exposer les détails techniques
    error_log("General error in product details: " . $e->getMessage());
    Response::error("Une erreur est survenue", 500);
}
?>
<?php
// api/products/get_supplier_products.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclure les fichiers nécessaires
include_once '../config/database.php';
include_once '../utils/response.php';

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Méthode non autorisée", 405);
    exit;
}

// Vérification des données requises
if (!isset($_GET['contact_id']) || empty($_GET['contact_id']) || !isset($_GET['user_id']) || empty($_GET['user_id'])) {
    Response::error("ID du contact et ID de l'utilisateur requis");
    exit;
}

$contact_id = intval($_GET['contact_id']);
$user_id = intval($_GET['user_id']);

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Requête pour obtenir tous les produits associés à ce fournisseur
    $query = "SELECT ps.product_id, ps.price, ps.is_primary, ps.delivery_time, 
                     p.reference, p.name, p.quantity
              FROM product_suppliers ps
              JOIN products p ON ps.product_id = p.id
              WHERE ps.contact_id = :contact_id 
              AND p.user_id = :user_id
              ORDER BY ps.is_primary DESC, ps.price ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":contact_id", $contact_id);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    
    $products_arr = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $product_item = array(
            "product_id" => $row['product_id'],
            "price" => $row['price'],
            "is_primary" => (bool)$row['is_primary'],
            "delivery_time" => $row['delivery_time'],
            "reference" => $row['reference'],
            "name" => $row['name'],
            "quantity" => $row['quantity']
        );
        
        array_push($products_arr, $product_item);
    }
    
    echo json_encode($products_arr);
    
} catch (PDOException $e) {
    Response::error("Erreur de base de données: " . $e->getMessage(), 500);
} catch (Exception $e) {
    Response::error("Une erreur est survenue: " + $e->getMessage(), 500);
}
?>
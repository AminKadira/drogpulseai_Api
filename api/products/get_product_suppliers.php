<?php
// api/products/get_product_suppliers.php
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
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    Response::error("ID du produit requis");
    exit;
}

$product_id = intval($_GET['product_id']);

// Paramètre optionnel pour filtrer par statut actif
$only_active = isset($_GET['only_active']) && $_GET['only_active'] === 'true';

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Construction de la requête
    $query = "SELECT ps.id, ps.contact_id, ps.is_primary, ps.price, ps.notes, 
                     ps.delivery_conditions, ps.delivery_time, ps.is_active,
                     c.nom, c.prenom, c.telephone, c.email, c.latitude, c.longitude
              FROM product_suppliers ps
              JOIN contacts c ON ps.contact_id = c.id
              WHERE ps.product_id = :product_id";
    
    // Ajouter le filtre pour les fournisseurs actifs si demandé
    if ($only_active) {
        $query .= " AND ps.is_active = 1";
    }
    
    // Tri par fournisseur principal puis par prix
    $query .= " ORDER BY ps.is_primary DESC, ps.price ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":product_id", $product_id);
    $stmt->execute();
    
    $suppliers_arr = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $supplier_item = array(
            "id" => $row['id'],             // ID de l'association product_suppliers
            "contact_id" => $row['contact_id'],
            "is_primary" => (bool)$row['is_primary'],
            "price" => $row['price'],
            "notes" => $row['notes'],
            "delivery_conditions" => $row['delivery_conditions'],
            "delivery_time" => $row['delivery_time'],
            "is_active" => (bool)$row['is_active'],
            "nom" => $row['nom'],
            "prenom" => $row['prenom'],
            "telephone" => $row['telephone'],
            "email" => $row['email'],
            "latitude" => $row['latitude'],
            "longitude" => $row['longitude'],
            "full_name" => $row['prenom'] . ' ' . $row['nom']
        );
        
        array_push($suppliers_arr, $supplier_item);
    }
    
    // Récupérer les informations du produit
    $product_query = "SELECT reference, name, barcode, quantity 
                      FROM products 
                      WHERE id = :product_id";
    $product_stmt = $db->prepare($product_query);
    $product_stmt->bindParam(":product_id", $product_id);
    $product_stmt->execute();
    
    $product_info = null;
    if ($product_stmt->rowCount() > 0) {
        $product_info = $product_stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Préparer la réponse finale
    $response = array(
        "product_id" => $product_id,
        "product_info" => $product_info,
        "suppliers" => $suppliers_arr,
        "total_suppliers" => count($suppliers_arr),
        "has_primary" => false
    );
    
    // Vérifier si un fournisseur principal existe
    foreach ($suppliers_arr as $supplier) {
        if ($supplier['is_primary']) {
            $response["has_primary"] = true;
            $response["primary_supplier"] = $supplier;
            break;
        }
    }
    
    // Réponse avec la liste des fournisseurs
    Response::json($response);
    
} catch (PDOException $e) {
    // Log l'erreur mais ne pas exposer les détails techniques
    error_log("Database error in get_product_suppliers: " . $e->getMessage());
    Response::error("Erreur de base de données", 500);
} catch (Exception $e) {
    // Log l'erreur mais ne pas exposer les détails techniques
    error_log("General error in get_product_suppliers: " . $e->getMessage());
    Response::error("Une erreur est survenue", 500);
}
?>
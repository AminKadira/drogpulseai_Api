<?php
// api/products/product_suppliers.php
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

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Requête pour récupérer les fournisseurs d'un produit
    $query = "SELECT ps.id, ps.product_id, ps.contact_id, ps.is_primary, ps.price, 
                     c.nom, c.prenom, c.telephone, c.email 
              FROM product_suppliers ps
              JOIN contacts c ON ps.contact_id = c.id
              WHERE ps.product_id = :product_id
              ORDER BY ps.is_primary DESC, c.nom ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":product_id", $product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $suppliers_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $supplier_item = [
                "id" => $row['id'],
                "product_id" => $row['product_id'],
                "contact_id" => $row['contact_id'],
                "is_primary" => (bool)$row['is_primary'],
                "price" => $row['price'],
                "contact" => [
                    "nom" => $row['nom'],
                    "prenom" => $row['prenom'],
                    "telephone" => $row['telephone'],
                    "email" => $row['email']
                ]
            ];
            array_push($suppliers_arr, $supplier_item);
        }
        Response::json($suppliers_arr);
    } else {
        Response::json([]);
    }
} catch (PDOException $e) {
    Response::error("Erreur de base de données: " . $e->getMessage(), 500);
} catch (Exception $e) {
    Response::error("Une erreur est survenue: " . $e->getMessage(), 500);
}
?>
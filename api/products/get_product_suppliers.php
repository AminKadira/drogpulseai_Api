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

// Paramètres de pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = ($page - 1) * $limit;

// Paramètres de filtrage
$only_active = isset($_GET['only_active']) ? (bool)$_GET['only_active'] : false;
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$max_delivery_time = isset($_GET['max_delivery_time']) ? intval($_GET['max_delivery_time']) : null;

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Construction de la requête avec filtres dynamiques
    $query = "SELECT ps.id, ps.product_id, ps.contact_id, ps.is_primary, ps.price, ps.notes, 
                     ps.delivery_conditions, ps.delivery_time, ps.is_active,
                     c.nom, c.prenom, c.telephone, c.email 
              FROM product_suppliers ps
              JOIN contacts c ON ps.contact_id = c.id
              WHERE ps.product_id = :product_id";
    
    // Ajout des conditions de filtrage
    if ($only_active) {
        $query .= " AND ps.is_active = 1";
    }
    
    if ($min_price !== null) {
        $query .= " AND ps.price >= :min_price";
    }
    
    if ($max_price !== null) {
        $query .= " AND ps.price <= :max_price";
    }
    
    if ($max_delivery_time !== null) {
        $query .= " AND (ps.delivery_time <= :max_delivery_time OR ps.delivery_time IS NULL)";
    }
    
    // Ajout du tri et de la pagination
    $query .= " ORDER BY ps.is_primary DESC, ps.price ASC, c.nom ASC
               LIMIT :limit OFFSET :offset";
    
    // Préparation de la requête
    $stmt = $db->prepare($query);
    $stmt->bindParam(":product_id", $product_id);
    
    // Liaison des paramètres de filtrage
    if ($min_price !== null) {
        $stmt->bindParam(":min_price", $min_price);
    }
    
    if ($max_price !== null) {
        $stmt->bindParam(":max_price", $max_price);
    }
    
    if ($max_delivery_time !== null) {
        $stmt->bindParam(":max_delivery_time", $max_delivery_time);
    }
    
    // Liaison des paramètres de pagination
    $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
    $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
    
    // Exécution de la requête
    $stmt->execute();
    
    // Requête pour le nombre total d'items (pour la pagination)
    $count_query = "SELECT COUNT(*) as total FROM product_suppliers WHERE product_id = :product_id";
    if ($only_active) {
        $count_query .= " AND is_active = 1";
    }
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindParam(":product_id", $product_id);
    $count_stmt->execute();
    $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Construction de la réponse
    $suppliers_arr = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $supplier_item = [
            "id" => $row['id'],
            "product_id" => $row['product_id'],
            "contact_id" => $row['contact_id'],
            "is_primary" => (bool)$row['is_primary'],
            "price" => $row['price'],
            "notes" => $row['notes'],
            "delivery_conditions" => $row['delivery_conditions'],
            "delivery_time" => $row['delivery_time'],
            "is_active" => (bool)$row['is_active'],
            "contact" => [
                "nom" => $row['nom'],
                "prenom" => $row['prenom'],
                "telephone" => $row['telephone'],
                "email" => $row['email']
            ]
        ];
        array_push($suppliers_arr, $supplier_item);
    }
    
    // Construire les métadonnées de pagination
    $pagination = [
        "total_items" => $total_count,
        "items_per_page" => $limit,
        "current_page" => $page,
        "total_pages" => ceil($total_count / $limit)
    ];
    
    // Réponse finale avec pagination
    $response = [
        "suppliers" => $suppliers_arr,
        "pagination" => $pagination
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    Response::error("Erreur de base de données: " . $e->getMessage(), 500);
} catch (Exception $e) {
    Response::error("Une erreur est survenue: " . $e->getMessage(), 500);
}
?>
<?php
// api/products/add_product_supplier.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Gestion des requêtes OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Inclure les fichiers nécessaires
require_once '../config/database.php';
require_once '../utils/response.php';

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Méthode non autorisée", 405);
    exit;
}

// Récupération des données soumises
$data = json_decode(file_get_contents("php://input"));

// Vérification des données requises
if (!isset($data->product_id) || !isset($data->contact_id)) {
    Response::error("ID du produit et ID du contact requis");
    exit;
}

$product_id = intval($data->product_id);
$contact_id = intval($data->contact_id);
$is_primary = isset($data->is_primary) ? (bool)$data->is_primary : false;
$price = isset($data->price) ? floatval($data->price) : 0.00;

// Nouveaux champs
$notes = isset($data->notes) ? trim($data->notes) : null;
$delivery_conditions = isset($data->delivery_conditions) ? trim($data->delivery_conditions) : null;
$delivery_time = isset($data->delivery_time) ? intval($data->delivery_time) : null;
$is_active = isset($data->is_active) ? (bool)$data->is_active : true;

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Vérification que le produit et le contact existent
    $check_query = "SELECT 
                      (SELECT COUNT(*) FROM products WHERE id = :product_id) as product_exists,
                      (SELECT COUNT(*) FROM contacts WHERE id = :contact_id) as contact_exists";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":product_id", $product_id);
    $check_stmt->bindParam(":contact_id", $contact_id);
    $check_stmt->execute();
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['product_exists'] == 0) {
        Response::error("Produit inexistant", 404);
        exit;
    }
    
    if ($result['contact_exists'] == 0) {
        Response::error("Contact inexistant", 404);
        exit;
    }
    
    // Si le fournisseur est défini comme principal, mettre à jour les autres
    if ($is_primary) {
        $update_query = "UPDATE product_suppliers SET is_primary = 0 WHERE product_id = :product_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":product_id", $product_id);
        $update_stmt->execute();
    }
    
    // Vérifier si l'association existe déjà
    $exists_query = "SELECT id FROM product_suppliers WHERE product_id = :product_id AND contact_id = :contact_id";
    $exists_stmt = $db->prepare($exists_query);
    $exists_stmt->bindParam(":product_id", $product_id);
    $exists_stmt->bindParam(":contact_id", $contact_id);
    $exists_stmt->execute();
    
    if ($exists_stmt->rowCount() > 0) {
        // Association existe déjà, mise à jour
        $row = $exists_stmt->fetch(PDO::FETCH_ASSOC);
        $supplier_id = $row['id'];
        
        $update_query = "UPDATE product_suppliers 
                        SET is_primary = :is_primary, 
                            price = :price, 
                            notes = :notes,
                            delivery_conditions = :delivery_conditions,
                            delivery_time = :delivery_time,
                            is_active = :is_active,
                            updated_at = NOW() 
                        WHERE id = :id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":is_primary", $is_primary, PDO::PARAM_BOOL);
        $update_stmt->bindParam(":price", $price);
        $update_stmt->bindParam(":notes", $notes);
        $update_stmt->bindParam(":delivery_conditions", $delivery_conditions);
        $update_stmt->bindParam(":delivery_time", $delivery_time);
        $update_stmt->bindParam(":is_active", $is_active, PDO::PARAM_BOOL);
        $update_stmt->bindParam(":id", $supplier_id);
        
        if ($update_stmt->execute()) {
            Response::success(null, "Association produit-fournisseur mise à jour avec succès");
        } else {
            Response::error("Impossible de mettre à jour l'association produit-fournisseur");
        }
    } else {
        // Nouvelle association, insertion
        $insert_query = "INSERT INTO product_suppliers 
                        (product_id, contact_id, is_primary, price, notes, delivery_conditions, delivery_time, is_active) 
                        VALUES 
                        (:product_id, :contact_id, :is_primary, :price, :notes, :delivery_conditions, :delivery_time, :is_active)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(":product_id", $product_id);
        $insert_stmt->bindParam(":contact_id", $contact_id);
        $insert_stmt->bindParam(":is_primary", $is_primary, PDO::PARAM_BOOL);
        $insert_stmt->bindParam(":price", $price);
        $insert_stmt->bindParam(":notes", $notes);
        $insert_stmt->bindParam(":delivery_conditions", $delivery_conditions);
        $insert_stmt->bindParam(":delivery_time", $delivery_time);
        $insert_stmt->bindParam(":is_active", $is_active, PDO::PARAM_BOOL);
        
        if ($insert_stmt->execute()) {
            $supplier_id = $db->lastInsertId();
            Response::success(["id" => $supplier_id], "Association produit-fournisseur créée avec succès");
        } else {
            Response::error("Impossible de créer l'association produit-fournisseur");
        }
    }
} catch (PDOException $e) {
    Response::error("Erreur de base de données: " . $e->getMessage(), 500);
} catch (Exception $e) {
    Response::error("Une erreur est survenue: " . $e->getMessage(), 500);
}
?>
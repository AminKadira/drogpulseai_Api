<?php
// api/products/update_product_supplier.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT, POST, OPTIONS");
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
if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Méthode non autorisée", 405);
    exit;
}

// Récupération des données soumises
$data = json_decode(file_get_contents("php://input"));

// Vérification des données requises
if (!isset($data->id) || empty($data->id)) {
    Response::error("ID de l'association requis");
    exit;
}

$id = intval($data->id);
$is_primary = isset($data->is_primary) ? (bool)$data->is_primary : null;
$price = isset($data->price) ? (float)$data->price : null;
$notes = isset($data->notes) ? $data->notes : null;
$delivery_conditions = isset($data->delivery_conditions) ? $data->delivery_conditions : null;
$delivery_time = isset($data->delivery_time) ? intval($data->delivery_time) : null;
$is_active = isset($data->is_active) ? (bool)$data->is_active : null;

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Débuter une transaction
    $db->beginTransaction();
    
    // Si le fournisseur est défini comme principal et que le flag est défini à true
    if ($is_primary !== null && $is_primary) {
        // Récupérer le product_id pour cette association
        $product_query = "SELECT product_id FROM product_suppliers WHERE id = :id";
        $product_stmt = $db->prepare($product_query);
        $product_stmt->bindParam(":id", $id);
        $product_stmt->execute();
        
        if ($product_stmt->rowCount() > 0) {
            $product_id = $product_stmt->fetch(PDO::FETCH_ASSOC)['product_id'];
            
            // Mettre à jour tous les autres fournisseurs pour ce produit
            $update_others = "UPDATE product_suppliers SET is_primary = 0 
                             WHERE product_id = :product_id AND id != :id";
            $others_stmt = $db->prepare($update_others);
            $others_stmt->bindParam(":product_id", $product_id);
            $others_stmt->bindParam(":id", $id);
            $others_stmt->execute();
        }
    }
    
    // Construire la requête de mise à jour dynamiquement
    $update_fields = array();
    $params = array();
    
    // Ajouter les champs à mettre à jour s'ils sont définis
    if ($is_primary !== null) {
        $update_fields[] = "is_primary = :is_primary";
        $params[':is_primary'] = $is_primary ? 1 : 0;
    }
    
    if ($price !== null) {
        $update_fields[] = "price = :price";
        $params[':price'] = $price;
    }
    
    if ($notes !== null) {
        $update_fields[] = "notes = :notes";
        $params[':notes'] = $notes;
    }
    
    if ($delivery_conditions !== null) {
        $update_fields[] = "delivery_conditions = :delivery_conditions";
        $params[':delivery_conditions'] = $delivery_conditions;
    }
    
    if ($delivery_time !== null) {
        $update_fields[] = "delivery_time = :delivery_time";
        $params[':delivery_time'] = $delivery_time;
    }
    
    if ($is_active !== null) {
        $update_fields[] = "is_active = :is_active";
        $params[':is_active'] = $is_active ? 1 : 0;
    }
    
    // Ajouter la date de mise à jour
    $update_fields[] = "updated_at = NOW()";
    
    // Si aucun champ à mettre à jour, retourner une erreur
    if (empty($update_fields)) {
        Response::error("Aucun champ à mettre à jour");
        exit;
    }
    
    // Construire la requête complète
    $query = "UPDATE product_suppliers SET " . implode(", ", $update_fields) . " WHERE id = :id";
    $params[':id'] = $id;
    
    // Préparer et exécuter la requête
    $stmt = $db->prepare($query);
    
    foreach ($params as $param => $value) {
        if (is_int($value)) {
            $stmt->bindValue($param, $value, PDO::PARAM_INT);
        } elseif (is_bool($value)) {
            $stmt->bindValue($param, $value, PDO::PARAM_BOOL);
        } else {
            $stmt->bindValue($param, $value);
        }
    }
    
    if ($stmt->execute()) {
        // Valider la transaction
        $db->commit();
        
        if ($stmt->rowCount() > 0) {
            Response::success(null, "Association produit-fournisseur mise à jour avec succès");
        } else {
            Response::error("Aucune modification ou association non trouvée", 404);
        }
    } else {
        // Annuler la transaction en cas d'erreur
        $db->rollBack();
        Response::error("Impossible de mettre à jour l'association", 500);
    }
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    Response::error("Erreur de base de données: " . $e->getMessage(), 500);
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    Response::error("Une erreur est survenue: " . $e->getMessage(), 500);
}
?>
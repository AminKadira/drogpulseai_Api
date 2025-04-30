<?php
// Fichier: api/products/batch_update.php

// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclure les fichiers de configuration et d'utilitaires
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
if (!isset($data->userId) || !isset($data->products) || !is_array($data->products)) {
    Response::error("Format de données invalide");
    exit;
}

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

// Démarrer une transaction
$db->beginTransaction();

try {
    $successCount = 0;
    $products = $data->products;
    $userId = intval($data->userId);
    
    // Vérifier que l'utilisateur existe
    $user_check = "SELECT COUNT(*) FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_check);
    $user_stmt->bindParam(":user_id", $userId);
    $user_stmt->execute();
    
    if ($user_stmt->fetchColumn() == 0) {
        Response::error("Utilisateur inexistant", 404);
        exit;
    }
    
    foreach ($products as $product) {
        // Vérifier les données requises pour chaque produit
        if (empty($product->id) || empty($product->reference)) {
            continue;
        }
        
        // Vérifier que le produit existe et appartient à l'utilisateur
        $check_query = "SELECT id FROM products WHERE id = :id AND user_id = :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":id", $product->id);
        $check_stmt->bindParam(":user_id", $userId);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() === 0) {
            // Produit non trouvé ou utilisateur non autorisé, passer au suivant
            continue;
        }
        
        // Requête de mise à jour
        $update_query = "UPDATE products SET ";
        $update_params = array();
        
        // Construire dynamiquement la requête en fonction des champs fournis
        if (!empty($product->reference)) {
            $update_query .= "reference = :reference, ";
            $update_params[':reference'] = htmlspecialchars(strip_tags($product->reference));
        }
        
        if (!empty($product->label)) {
            $update_query .= "label = :label, ";
            $update_params[':label'] = htmlspecialchars(strip_tags($product->label));
        }
        
        if (!empty($product->name)) {
            $update_query .= "name = :name, ";
            $update_params[':name'] = htmlspecialchars(strip_tags($product->name));
        }
        
        if (isset($product->description)) {
            $update_query .= "description = :description, ";
            $update_params[':description'] = !empty($product->description) ? 
                htmlspecialchars(strip_tags($product->description)) : null;
        }
        
        if (isset($product->photo_url)) {
            $update_query .= "photo_url = :photo_url, ";
            $update_params[':photo_url'] = !empty($product->photo_url) ? 
                htmlspecialchars(strip_tags($product->photo_url)) : null;
        }
        
        if (isset($product->barcode)) {
            $update_query .= "barcode = :barcode, ";
            $update_params[':barcode'] = !empty($product->barcode) ? 
                htmlspecialchars(strip_tags($product->barcode)) : null;
        }
        
        if (isset($product->quantity)) {
            $update_query .= "quantity = :quantity, ";
            $update_params[':quantity'] = intval($product->quantity);
        }
        
        if (isset($product->price)) {
            $update_query .= "price = :price, ";
            $update_params[':price'] = floatval($product->price);
        }
        
        // Ajouter l'horodatage de mise à jour
        $update_query .= "updated_at = NOW() WHERE id = :id AND user_id = :user_id";
        
        // Ajouter les paramètres d'identification
        $update_params[':id'] = intval($product->id);
        $update_params[':user_id'] = $userId;
        
        // Préparer et exécuter la requête
        $stmt = $db->prepare($update_query);
        
        foreach ($update_params as $param => $value) {
            if (is_int($value)) {
                $stmt->bindParam($param, $value, PDO::PARAM_INT);
            } elseif (is_null($value)) {
                $stmt->bindParam($param, $value, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam($param, $value);
            }
        }
        
        if ($stmt->execute()) {
            $successCount++;
        }
    }
    
    // Valider la transaction
    $db->commit();
    
    Response::success([
        "updated" => $successCount,
        "total" => count($products)
    ], "Mise à jour par lots terminée");
    
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    $db->rollBack();
    
    // Journaliser l'erreur mais ne pas exposer les détails techniques
    error_log("Database error in batch update: " . $e->getMessage());
    Response::error("Erreur de base de données", 500);
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $db->rollBack();
    
    // Journaliser l'erreur mais ne pas exposer les détails techniques
    error_log("General error in batch update: " . $e->getMessage());
    Response::error("Une erreur est survenue", 500);
}
catch (Error $e) {
    // Annuler la transaction en cas d'erreur
    $db->rollBack();
    
    // Journaliser l'erreur mais ne pas exposer les détails techniques
    error_log("Fatal error in batch update: " . $e->getMessage());
    Response::error("Erreur fatale", 500);
}
catch (TypeError $e) {
    // Annuler la transaction en cas d'erreur
    $db->rollBack();
    
    // Journaliser l'erreur mais ne pas exposer les détails techniques
    error_log("Type error in batch update: " . $e->getMessage());
    Response::error("Erreur de type", 500);
}
catch (ParseError $e) {
    // Annuler la transaction en cas d'erreur
    $db->rollBack();
    
    // Journaliser l'erreur mais ne pas exposer les détails techniques
    error_log("Parse error in batch update: " . $e->getMessage());
    Response::error("Erreur de syntaxe", 500);
}

?>
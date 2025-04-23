<?php
// api/products/create.php
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
require_once '../models/Product.php';
require_once '../utils/response.php';

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Méthode non autorisée", 405);
    exit;
}

try {
    // Connexion à la base de données
    $database = new Database();
    $db = $database->getConnection();
    
    // Récupération des données soumises
    $data = json_decode(file_get_contents("php://input"));
    
    // Si on utilise des paramètres de formulaire au lieu de JSON
    if (empty($data) || !is_object($data)) {
        $data = new stdClass();
        $data->reference = filter_input(INPUT_POST, 'reference', FILTER_SANITIZE_STRING);
        $data->label = filter_input(INPUT_POST, 'label', FILTER_SANITIZE_STRING);
        $data->name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $data->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $data->photo_url = filter_input(INPUT_POST, 'photo_url', FILTER_SANITIZE_STRING);
        $data->barcode = filter_input(INPUT_POST, 'barcode', FILTER_SANITIZE_STRING);
        $data->quantity = filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_INT);
        $data->userId = filter_input(INPUT_POST, 'userId', FILTER_SANITIZE_NUMBER_INT);
    }
    
    // Création d'un nouvel objet Product
    $product = new Product($db);
    $product->reference = $data->reference;
    $product->label = $data->label;
    $product->name = $data->name;
    $product->description = $data->description ?? null;
    $product->photo_url = $data->photo_url ?? null;
    $product->barcode = $data->barcode ?? null;
    $product->quantity = $data->quantity ?? 0;
    $product->user_id = $data->userId;
    
    // Validation des données
    $errors = $product->validate();
    if (!empty($errors)) {
        Response::error(implode(", ", $errors), 400);
        exit;
    }
    
    // Vérifier si l'utilisateur existe
    $user_check = "SELECT COUNT(*) FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_check);
    $user_stmt->bindParam(":user_id", $product->user_id);
    $user_stmt->execute();
    
    if ($user_stmt->fetchColumn() == 0) {
        Response::error("Utilisateur inexistant", 404);
        exit;
    }
    
    // Création du produit
    if ($product->create()) {
        // Récupérer le produit créé
        $product->read($product->id);
        
        // Construire la réponse
        $product_data = [
            "id" => $product->id,
            "reference" => $product->reference,
            "label" => $product->label,
            "name" => $product->name,
            "description" => $product->description,
            "photo_url" => $product->photo_url,
            "barcode" => $product->barcode,
            "quantity" => $product->quantity,
            "user_id" => $product->user_id
        ];
        
        Response::success(['product' => $product_data], "Produit créé avec succès");
    } else {
        Response::error("Cette référence de produit existe déjà ou impossible de créer le produit", 409);
    }
} catch (PDOException $e) {
    error_log("Database error in product creation: " . $e->getMessage());
    Response::error("Erreur de base de données", 500);
} catch (Exception $e) {
    error_log("General error in product creation: " . $e->getMessage());
    Response::error("Une erreur est survenue", 500);
}
?>
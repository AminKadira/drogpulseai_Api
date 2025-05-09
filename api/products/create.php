<?php
// Désactiver tous les affichages d'erreur qui contamineraient le JSON
ini_set('display_errors', 0);

// Headers requis
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

// Si on utilise des paramètres de formulaire au lieu de JSON
if (empty($data) || !is_object($data)) {
    $data = new stdClass();
    $data->reference = filter_input(INPUT_POST, 'reference', FILTER_SANITIZE_STRING);
    $data->label = filter_input(INPUT_POST, 'label', FILTER_SANITIZE_STRING);
    $data->name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $data->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $data->photo_url = filter_input(INPUT_POST, 'photo_url', FILTER_SANITIZE_STRING);
    $data->photo_url2 = filter_input(INPUT_POST, 'photo_url2', FILTER_SANITIZE_STRING);
    $data->photo_url3 = filter_input(INPUT_POST, 'photo_url3', FILTER_SANITIZE_STRING);
    $data->barcode = filter_input(INPUT_POST, 'barcode', FILTER_SANITIZE_STRING);
    $data->quantity = filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_INT);
    $data->price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $data->userId = filter_input(INPUT_POST, 'userId', FILTER_SANITIZE_NUMBER_INT);
}

// Vérification des données requises avec détail des champs manquants
$missingFields = [];
if (empty($data->reference)) $missingFields[] = "reference";
if (empty($data->label)) $missingFields[] = "label";
if (empty($data->name)) $missingFields[] = "name";
// Vérifier plusieurs variations possibles du champ userId
$hasUserId = false;
if (!empty($data->userId)) $hasUserId = true;
else if (!empty($data->user_id)) {
    $hasUserId = true;
    $data->userId = $data->user_id;  // Normaliser pour utilisation ultérieure
}
else if (!empty($data->userID)) {
    $hasUserId = true;
    $data->userId = $data->userID;  // Normaliser pour utilisation ultérieure
}
else if (!empty($data->userid)) {
    $hasUserId = true;
    $data->userId = $data->userid;  // Normaliser pour utilisation ultérieure
}

if (!$hasUserId) $missingFields[] = "userId";

if (!empty($missingFields)) {
    $errorMessage = "Données incomplètes pour créer un produit. Champs manquants: " . implode(", ", $missingFields);
    Response::error($errorMessage);
    exit;
}

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    Response::error("Erreur de connexion à la base de données", 500);
    exit;
}

try {
    // Vérification que l'utilisateur existe
    $user_check = "SELECT COUNT(*) FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_check);
    $user_id = intval($data->userId);
    $user_stmt->bindParam(":user_id", $user_id);
    $user_stmt->execute();
    
    if ($user_stmt->fetchColumn() == 0) {
        Response::error("Utilisateur inexistant", 404);
        exit;
    }
    
    // Vérifier si la référence existe déjà
    $ref_check = "SELECT COUNT(*) FROM products WHERE reference = :reference";
    $ref_stmt = $db->prepare($ref_check);
    $ref_stmt->bindParam(":reference", $data->reference);
    $ref_stmt->execute();
    
    if ($ref_stmt->fetchColumn() > 0) {
        Response::error("Cette référence de produit existe déjà", 409);
        exit;
    }
    
    // Préparation de la requête d'insertion
    $query = "INSERT INTO products (reference, label, name, description, photo_url, photo_url2, photo_url3, 
                                   barcode, quantity, price, user_id)
              VALUES (:reference, :label, :name, :description, :photo_url, :photo_url2, :photo_url3, 
                     :barcode, :quantity, :price, :user_id)";
   
    $stmt = $db->prepare($query);
   
    // Nettoyer et sécuriser les données
    $reference = htmlspecialchars(strip_tags($data->reference));
    $label = htmlspecialchars(strip_tags($data->label));
    $name = htmlspecialchars(strip_tags($data->name));
    $description = !empty($data->description) ? htmlspecialchars(strip_tags($data->description)) : null;
    $photo_url = !empty($data->photo_url) ? htmlspecialchars(strip_tags($data->photo_url)) : null;
    $photo_url2 = !empty($data->photo_url2) ? htmlspecialchars(strip_tags($data->photo_url2)) : null;
    $photo_url3 = !empty($data->photo_url3) ? htmlspecialchars(strip_tags($data->photo_url3)) : null;
    $barcode = !empty($data->barcode) ? htmlspecialchars(strip_tags($data->barcode)) : null;
    $quantity = !empty($data->quantity) ? intval($data->quantity) : 0;
    $price = !empty($data->price) ? floatval($data->price) : 0.00;
    
    // Liaison des paramètres
    $stmt->bindParam(":reference", $reference);
    $stmt->bindParam(":label", $label);
    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":description", $description);
    $stmt->bindParam(":photo_url", $photo_url);
    $stmt->bindParam(":photo_url2", $photo_url2);
    $stmt->bindParam(":photo_url3", $photo_url3);
    $stmt->bindParam(":barcode", $barcode);
    $stmt->bindParam(":quantity", $quantity);
    $stmt->bindParam(":price", $price);
    $stmt->bindParam(":user_id", $user_id);
   
    // Exécution de la requête
    if ($stmt->execute()) {
        $product_id = $db->lastInsertId();
        
        // Récupérer le produit créé pour confirmation
        $product_query = "SELECT id, reference, label, name, description, photo_url, photo_url2, photo_url3, 
                          barcode, quantity, price, user_id
                          FROM products WHERE id = :id";
        $product_stmt = $db->prepare($product_query);
        $product_stmt->bindParam(":id", $product_id);
        $product_stmt->execute();
        $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
        
        Response::success(['product' => $product], "Produit créé avec succès");
    } else {
        Response::error("Impossible de créer le produit", 500);
    }
} catch (PDOException $e) {
    Response::error("Erreur de base de données: " . $e->getMessage(), 500);
} catch (Exception $e) {
    Response::error("Une erreur est survenue: " . $e->getMessage(), 500);
}
?>
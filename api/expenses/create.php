<?php
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
    $data->type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
    $data->amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $data->date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $data->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $data->receipt_photo_url = filter_input(INPUT_POST, 'receipt_photo_url', FILTER_SANITIZE_STRING);
    $data->userId = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
}

// Vérification des données requises
$missingFields = [];
if (empty($data->type)) $missingFields[] = "type";
if (empty($data->amount)) $missingFields[] = "amount";
if (empty($data->date)) $missingFields[] = "date";
// Vérifier plusieurs variantes pour userId
$hasUserId = false;
if (!empty($data->userId)) {
    $hasUserId = true;
    $data->user_id = $data->userId;
} else if (!empty($data->user_id)) {
    $hasUserId = true;
}

if (!$hasUserId) $missingFields[] = "user_id/userId";

if (!empty($missingFields)) {
    $errorMessage = "Données incomplètes pour créer un frais. Champs manquants: " . implode(", ", $missingFields);
    Response::error($errorMessage);
    exit;
}

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Vérification que l'utilisateur existe
    $user_check = "SELECT COUNT(*) FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_check);
    $user_id = intval($data->user_id);
    $user_stmt->bindParam(":user_id", $user_id);
    $user_stmt->execute();
    
    if ($user_stmt->fetchColumn() == 0) {
        Response::error("Utilisateur inexistant", 404);
        exit;
    }
    
    // Préparation de la requête d'insertion
    $query = "INSERT INTO expenses (type, amount, date, description, receipt_photo_url, user_id)
              VALUES (:type, :amount, :date, :description, :receipt_photo_url, :user_id)";
    
    $stmt = $db->prepare($query);
    
    // Nettoyer et sécuriser les données
    $type = htmlspecialchars(strip_tags($data->type));
    $amount = floatval($data->amount);
    $date = htmlspecialchars(strip_tags($data->date));
    $description = !empty($data->description) ? htmlspecialchars(strip_tags($data->description)) : null;
    $receipt_photo_url = !empty($data->receipt_photo_url) ? htmlspecialchars(strip_tags($data->receipt_photo_url)) : null;
    
    // Liaison des paramètres
    $stmt->bindParam(":type", $type);
    $stmt->bindParam(":amount", $amount);
    $stmt->bindParam(":date", $date);
    $stmt->bindParam(":description", $description);
    $stmt->bindParam(":receipt_photo_url", $receipt_photo_url);
    $stmt->bindParam(":user_id", $user_id);
    
    // Exécution de la requête
    if ($stmt->execute()) {
        $expense_id = $db->lastInsertId();
        
        // Récupérer le frais créé pour confirmation
        $expense_query = "SELECT * FROM expenses WHERE id = :id";
        $expense_stmt = $db->prepare($expense_query);
        $expense_stmt->bindParam(":id", $expense_id);
        $expense_stmt->execute();
        $expense = $expense_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Convertir le montant en nombre à virgule flottante
        $expense['amount'] = floatval($expense['amount']);
        
        Response::success(['product' => $expense], "Frais créé avec succès");
    } else {
        Response::error("Impossible de créer le frais", 500);
    }
} catch (PDOException $e) {
    // Log l'erreur mais ne pas exposer les détails techniques
    error_log("Database error: " . $e->getMessage());
    Response::error("Erreur de base de données", 500);
} catch (Exception $e) {
    // Log l'erreur mais ne pas exposer les détails techniques
    error_log("Error: " . $e->getMessage());
    Response::error("Une erreur est survenue", 500);
}
?>
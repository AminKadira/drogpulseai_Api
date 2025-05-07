<?php
// Headers requis
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

// Inclure les fichiers de configuration et d'utilitaires
require_once '../config/database.php';
require_once '../utils/response.php';

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Méthode non autorisée", 405);
    exit;
}

// Récupération des données soumises
$data = json_decode(file_get_contents("php://input"));

// Si on utilise des paramètres de formulaire au lieu de JSON
if (empty($data) || !is_object($data)) {
    $data = new stdClass();
    $data->id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $data->type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
    $data->amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $data->date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $data->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $data->receipt_photo_url = filter_input(INPUT_POST, 'receipt_photo_url', FILTER_SANITIZE_STRING);
    $data->userId = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
}

// Vérification des données requises
$missingFields = [];
if (empty($data->id)) $missingFields[] = "id";
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
    $errorMessage = "Données incomplètes pour mettre à jour un frais. Champs manquants: " . implode(", ", $missingFields);
    Response::error($errorMessage);
    exit;
}

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Vérification de l'existence du frais et du droit de modification
    $check_query = "SELECT id FROM expenses WHERE id = :id AND user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    
    $id = intval($data->id);
    $user_id = intval($data->user_id);
    
    $check_stmt->bindParam(":id", $id);
    $check_stmt->bindParam(":user_id", $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() === 0) {
        Response::error("Frais non trouvé ou vous n'avez pas les droits nécessaires", 403);
        exit;
    }
    
    // Préparation de la requête de mise à jour
    $query = "UPDATE expenses
              SET type = :type,
                  amount = :amount,
                  date = :date,
                  description = :description,
                  receipt_photo_url = :receipt_photo_url
              WHERE id = :id AND user_id = :user_id";
    
    $stmt = $db->prepare($query);
    
    // Nettoyer et sécuriser les données
    $type = htmlspecialchars(strip_tags($data->type));
    $amount = floatval($data->amount);
    $date = htmlspecialchars(strip_tags($data->date));
    $description = !empty($data->description) ? htmlspecialchars(strip_tags($data->description)) : null;
    $receipt_photo_url = !empty($data->receipt_photo_url) ? htmlspecialchars(strip_tags($data->receipt_photo_url)) : null;
    
    // Liaison des paramètres
    $stmt->bindParam(":id", $id);
    $stmt->bindParam(":type", $type);
    $stmt->bindParam(":amount", $amount);
    $stmt->bindParam(":date", $date);
    $stmt->bindParam(":description", $description);
    $stmt->bindParam(":receipt_photo_url", $receipt_photo_url);
    $stmt->bindParam(":user_id", $user_id);
    
    // Exécution de la requête
    if ($stmt->execute()) {
        // Récupérer le frais mis à jour pour confirmation
        $get_query = "SELECT * FROM expenses WHERE id = :id";
        $get_stmt = $db->prepare($get_query);
        $get_stmt->bindParam(":id", $id);
        $get_stmt->execute();
        $expense = $get_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Convertir le montant en nombre à virgule flottante
        $expense['amount'] = floatval($expense['amount']);
        
        Response::success(['product' => $expense], "Frais mis à jour avec succès");
    } else {
        Response::error("Impossible de mettre à jour le frais", 500);
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
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
    $data->nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $data->prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING);
    $data->telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING);
    $data->email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $data->notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    $data->latitude = filter_input(INPUT_POST, 'latitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $data->longitude = filter_input(INPUT_POST, 'longitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $data->userId = filter_input(INPUT_POST, 'userId', FILTER_SANITIZE_NUMBER_INT);
    
    // Vérifier aussi user_id au cas où c'est ce format qui est utilisé
    if (empty($data->userId)) {
        $data->userId = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    }
}

// Vérification des données requises

$missingFields = [];
if (empty($data->nom)) $missingFields[] = "nom";
if (empty($data->prenom)) $missingFields[] = "prenom";
if (empty($data->telephone)) $missingFields[] = "telephone";
if (empty($data->latitude)) $missingFields[] = "latitude";
if (empty($data->longitude)) $missingFields[] = "longitude";


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
    $errorMessage = "Données incomplètes pour mettre à jour un produit. Champs manquants: " . implode(", ", $missingFields);
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
    // Vérification de l'existence du contact et du droit de modification
    $check_query = "SELECT id FROM contacts WHERE id = :id AND user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    
    $id = intval($data->id);
    $user_id = intval($data->userId);
    
    $check_stmt->bindParam(":id", $id);
    $check_stmt->bindParam(":user_id", $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() === 0) {
        Response::error("Contact (".$user_id.") non trouvé ou vous n'avez pas les droits nécessaires", 403);
        exit;
    }
    
    // Validation de l'email si fourni
    if (!empty($data->email) && !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        Response::error("Format d'email invalide");
        exit;
    }
    
    // Validation des coordonnées
    $latitude = is_numeric($data->latitude) ? floatval($data->latitude) : 0;
    $longitude = is_numeric($data->longitude) ? floatval($data->longitude) : 0;
    
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        Response::error("Coordonnées géographiques invalides");
        exit;
    }
    
    // Préparation de la requête de mise à jour
    $query = "UPDATE contacts
              SET nom = :nom, prenom = :prenom, telephone = :telephone,
                  email = :email, notes = :notes, latitude = :latitude, longitude = :longitude,
                  updated_at = NOW()
              WHERE id = :id AND user_id = :user_id";
    
    $stmt = $db->prepare($query);
    
    // Nettoyer et sécuriser les données
    $nom = htmlspecialchars(strip_tags($data->nom));
    $prenom = htmlspecialchars(strip_tags($data->prenom));
    $telephone = htmlspecialchars(strip_tags($data->telephone));
    $email = !empty($data->email) ? filter_var($data->email, FILTER_SANITIZE_EMAIL) : null;
    $notes = !empty($data->notes) ? htmlspecialchars(strip_tags($data->notes)) : null;
    
    // Liaison des paramètres
    $stmt->bindParam(":id", $id);
    $stmt->bindParam(":nom", $nom);
    $stmt->bindParam(":prenom", $prenom);
    $stmt->bindParam(":telephone", $telephone);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":notes", $notes);
    $stmt->bindParam(":latitude", $latitude);
    $stmt->bindParam(":longitude", $longitude);
    $stmt->bindParam(":user_id", $user_id);
    
    // Exécution de la requête
    if ($stmt->execute()) {
        // Récupérer le contact mis à jour pour confirmation
        $get_query = "SELECT id, nom, prenom, telephone, email, notes, latitude, longitude, user_id
                      FROM contacts WHERE id = :id";
        $get_stmt = $db->prepare($get_query);
        $get_stmt->bindParam(":id", $id);
        $get_stmt->execute();
        $contact = $get_stmt->fetch(PDO::FETCH_ASSOC);
        
        Response::success(['contact' => $contact], "Contact mis à jour avec succès");
    } else {
        Response::error("Impossible de mettre à jour le contact", 500);
    }
} catch (PDOException $e) {
    // Log l'erreur en interne mais ne pas exposer les détails techniques
    // writeLog('error', 'PDO Error: ' . $e->getMessage());
    Response::error("Erreur de base de données", 500);
} catch (Exception $e) {
    // Log l'erreur en interne mais ne pas exposer les détails techniques
    // writeLog('error', 'Error: ' . $e->getMessage());
    Response::error("Une erreur est survenue", 500);
}
?>
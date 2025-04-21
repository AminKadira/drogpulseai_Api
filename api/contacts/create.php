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
    $data->nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $data->prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING);
    $data->telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING);
    $data->email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $data->notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    $data->latitude = filter_input(INPUT_POST, 'latitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $data->longitude = filter_input(INPUT_POST, 'longitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $data->userId = filter_input(INPUT_POST, 'userId', FILTER_SANITIZE_NUMBER_INT);
}

// Vérification des données requises
if (empty($data->nom) || empty($data->prenom) || empty($data->telephone) ||
    !isset($data->latitude) || !isset($data->longitude) ) {
    Response::error("Données incomplètes pour créer un contact");
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
    
    // Préparation de la requête d'insertion
    $query = "INSERT INTO contacts (nom, prenom, telephone, email, notes, latitude, longitude, user_id, created_at)
              VALUES (:nom, :prenom, :telephone, :email, :notes, :latitude, :longitude, :user_id, NOW())";
   
    $stmt = $db->prepare($query);
   
    // Nettoyer et sécuriser les données
    $nom = htmlspecialchars(strip_tags($data->nom));
    $prenom = htmlspecialchars(strip_tags($data->prenom));
    $telephone = htmlspecialchars(strip_tags($data->telephone));
    $email = !empty($data->email) ? filter_var($data->email, FILTER_VALIDATE_EMAIL) : null;
    $notes = !empty($data->notes) ? htmlspecialchars(strip_tags($data->notes)) : null;
    $latitude = is_numeric($data->latitude) ? floatval($data->latitude) : 0;
    $longitude = is_numeric($data->longitude) ? floatval($data->longitude) : 0;
   
    // Validation de l'email si fourni
    if (!empty($data->email) && $email === false) {
        Response::error("Format d'email invalide");
        exit;
    }
    
    // Validation des coordonnées
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        Response::error("Coordonnées géographiques invalides");
        exit;
    }
    
    // Liaison des paramètres
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
        $contact_id = $db->lastInsertId();
        
        // Récupérer le contact créé pour confirmation
        $contact_query = "SELECT id, nom, prenom, telephone, email, notes, latitude, longitude, user_id
                          FROM contacts WHERE id = :id";
        $contact_stmt = $db->prepare($contact_query);
        $contact_stmt->bindParam(":id", $contact_id);
        $contact_stmt->execute();
        $contact = $contact_stmt->fetch(PDO::FETCH_ASSOC);
        
        Response::success(['contact' => $contact], "Contact créé avec succès");
    } else {
        Response::error("Impossible de créer le contact", 500);
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
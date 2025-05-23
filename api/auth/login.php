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
    // Ajouter du log pour débogage
    error_log("Méthode incorrecte reçue: " . $_SERVER['REQUEST_METHOD']);
    
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Méthode non autorisée. Utilisez POST."
    ]);
    exit;
}

// Récupération des données soumises
$data = json_decode(file_get_contents("php://input"));

// Si on utilise des paramètres de formulaire au lieu de JSON
if (empty($data) || !is_object($data)) {
    $data = new stdClass();
    $data->email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $data->password = filter_input(INPUT_POST, 'password');
}

// Vérification des données requises
if (empty($data->email) || empty($data->password)) {
    Response::error("Données incomplètes : email et mot de passe requis");
    exit;
}

// Validation de l'email
if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    Response::error("Format d'email invalide");
    exit;
}

try {
    // Connexion à la base de données
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    // Préparation de la requête - AJOUT du champ type_user
    $query = "SELECT id, nom, prenom, telephone, latitude, longitude, email, type_user, password
              FROM users
              WHERE email = :email
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    
    // Liaison des paramètres
    $stmt->bindParam(":email", $data->email);
    
    // Exécution de la requête
    $stmt->execute();
    
    // Vérification de l'existence de l'utilisateur
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Vérification du mot de passe avec délai constant pour éviter les timing attacks
        if (password_verify($data->password, $row['password'])) {
            // Création de l'objet utilisateur (sans le mot de passe pour la sécurité)
            $user = [
                'id' => (int)$row['id'],
                'nom' => htmlspecialchars($row['nom']),
                'prenom' => htmlspecialchars($row['prenom']),
                'telephone' => htmlspecialchars($row['telephone']),
                'latitude' => (float)$row['latitude'],
                'longitude' => (float)$row['longitude'],
                'email' => $row['email'],
                'typeUser' => $row['type_user'] // AJOUT du type d'utilisateur
            ];
            
            // Journaliser la connexion réussie avec le type d'utilisateur
            error_log('Connexion réussie pour: ' . $data->email . ' (Type: ' . $row['type_user'] . ')');
            
            // Réponse de succès avec les données utilisateur incluant le type
            Response::success(['user' => $user], "Connexion réussie");
        } else {
            // Mot de passe incorrect - utiliser un message générique pour la sécurité
            Response::error("Identifiants invalides");
        }
    } else {
        // Utilisateur non trouvé - utiliser un message générique pour la sécurité
        Response::error("Identifiants invalides");
    }
} catch (PDOException $e) {
    // Journaliser l'erreur en interne mais ne pas exposer les détails techniques
    error_log('PDO Error: ' . $e->getMessage());
    Response::error("Erreur de connexion à la base de données", 500);
} catch (Exception $e) {
    // Journaliser l'erreur en interne mais ne pas exposer les détails techniques
    error_log('Error: ' . $e->getMessage());
    Response::error("Une erreur est survenue", 500);
}
?>
<?php
// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclure les fichiers de configuration et d'utilitaires
include_once '../config/database.php';
include_once '../utils/response.php';

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Méthode non autorisée", 405);
}

// Récupération des données soumises
$data = json_decode(file_get_contents("php://input"));

// Vérification des données requises
if (empty($data->nom) || empty($data->prenom) || empty($data->telephone) || 
    empty($data->email) || empty($data->password)) {
    Response::error("Données incomplètes");
}

// Validation du type d'utilisateur
$valid_user_types = ['Admin', 'Commercial', 'Vendeur', 'Invité', 'Manager'];
$user_type = isset($data->typeUser) ? $data->typeUser : 'Commercial';

if (!in_array($user_type, $valid_user_types)) {
    Response::error("Type d'utilisateur invalide");
}

// Validation de l'email
if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    Response::error("Format d'email invalide");
}

// Validation du mot de passe
if (strlen($data->password) < 6) {
    Response::error("Le mot de passe doit contenir au moins 6 caractères");
}

// Validation du téléphone (optionnel - format basique)
if (!preg_match('/^[0-9+\-\s()]{10,}$/', $data->telephone)) {
    Response::error("Format de téléphone invalide");
}

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Vérifier si l'email existe déjà
    $query = "SELECT id FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        Response::error("Cet email est déjà utilisé");
    }
    
    // Préparation de la requête d'insertion avec le nouveau champ type_user
    $query = "INSERT INTO users (nom, prenom, telephone, email, type_user, latitude, longitude, password) 
              VALUES (:nom, :prenom, :telephone, :email, :type_user, :latitude, :longitude, :password)";
    
    $stmt = $db->prepare($query);
    
    // Nettoyer et sécuriser les données
    $data->nom = htmlspecialchars(strip_tags($data->nom));
    $data->prenom = htmlspecialchars(strip_tags($data->prenom));
    $data->telephone = htmlspecialchars(strip_tags($data->telephone));
    $data->email = htmlspecialchars(strip_tags($data->email));
    $user_type = htmlspecialchars(strip_tags($user_type));
    
    // Valeurs par défaut pour la géolocalisation si non fournies
    $latitude = isset($data->latitude) ? (float)$data->latitude : 0.0;
    $longitude = isset($data->longitude) ? (float)$data->longitude : 0.0;
    
    // Hachage du mot de passe
    $password_hash = password_hash($data->password, PASSWORD_DEFAULT);
    
    // Liaison des paramètres
    $stmt->bindParam(":nom", $data->nom);
    $stmt->bindParam(":prenom", $data->prenom);
    $stmt->bindParam(":telephone", $data->telephone);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":type_user", $user_type);
    $stmt->bindParam(":latitude", $latitude);
    $stmt->bindParam(":longitude", $longitude);
    $stmt->bindParam(":password", $password_hash);
    
    // Exécution de la requête
    if ($stmt->execute()) {
        // Récupérer l'ID du nouvel utilisateur
        $user_id = $db->lastInsertId();
        
        // Optionnel : Récupérer les données du nouvel utilisateur (sans le mot de passe)
        $query_user = "SELECT id, nom, prenom, telephone, email, type_user, latitude, longitude, created_at 
                       FROM users WHERE id = :user_id";
        $stmt_user = $db->prepare($query_user);
        $stmt_user->bindParam(":user_id", $user_id);
        $stmt_user->execute();
        
        if ($stmt_user->rowCount() > 0) {
            $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
            
            // Journaliser la création du compte (optionnel)
            error_log("Nouveau compte créé: " . $data->email . " (Type: " . $user_type . ")");
            
            // Réponse de succès avec les données utilisateur
            Response::success([
                'user' => $user_data,
                'user_id' => (int)$user_id
            ], "Compte créé avec succès");
        } else {
            Response::success(['user_id' => (int)$user_id], "Compte créé avec succès");
        }
    } else {
        Response::error("Impossible de créer le compte");
    }
} catch (PDOException $e) {
    // Journaliser l'erreur en détail
    error_log("Erreur PDO lors de l'inscription: " . $e->getMessage());
    
    // Vérifier si c'est une violation de contrainte d'unicité
    if ($e->getCode() == 23000) {
        Response::error("Cette adresse email est déjà utilisée");
    } else {
        Response::error("Erreur de base de données", 500);
    }
} catch (Exception $e) {
    // Journaliser l'erreur générale
    error_log("Erreur générale lors de l'inscription: " . $e->getMessage());
    Response::error("Une erreur est survenue lors de la création du compte", 500);
}
?>
<?php
// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclure les fichiers de configuration et d'utilitaires
include_once '../config/database.php';
include_once '../utils/response.php';

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Méthode non autorisée", 405);
    exit;
}

// Vérification des données requises
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    Response::error("ID utilisateur requis");
    exit;
}

$user_id = intval($_GET['user_id']);

// Paramètres de pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$offset = ($page - 1) * $limit;

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Requête pour récupérer les paniers avec informations supplémentaires
    $query = "SELECT c.*, 
              CONCAT(co.prenom, ' ', co.nom) as contact_name,
              co.telephone as contact_telephone,
              co.email as contact_email,
              (SELECT COUNT(*) FROM cart_items ci WHERE ci.cart_id = c.id) as items_count,
              (SELECT SUM(ci.quantity) FROM cart_items ci WHERE ci.cart_id = c.id) as total_quantity,
              (SELECT SUM(ci.quantity * ci.price) FROM cart_items ci WHERE ci.cart_id = c.id) as total_amount
              FROM carts c
              JOIN contacts co ON c.contact_id = co.id
              WHERE c.user_id = :user_id
              ORDER BY c.created_at DESC
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    // Liaison des paramètres
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    
    // Requête pour compter le nombre total de paniers
    $count_query = "SELECT COUNT(*) as total FROM carts WHERE user_id = :user_id";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $count_stmt->execute();
    $count_row = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $total_carts = $count_row['total'];
    
    // Construction de la réponse
    $carts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'data' => [
            'carts' => $carts,
            'pagination' => [
                'total' => $total_carts,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total_carts / $limit)
            ]
        ]
    ];
    
    // Réponse avec le résultat
    echo json_encode($response);
    
} catch (PDOException $e) {
    // Log l'erreur en interne mais ne pas exposer les détails techniques
    error_log("Database error in cart listing: " . $e->getMessage());
    Response::error("Erreur de base de données", 500);
} catch (Exception $e) {
    // Log l'erreur en interne mais ne pas exposer les détails techniques
    error_log("General error in cart listing: " . $e->getMessage());
    Response::error("Une erreur est survenue", 500);
}
?>
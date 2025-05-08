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

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Préparation de la requête
    $query = "SELECT * FROM products WHERE user_id = :user_id ORDER BY reference ASC";
    
    $stmt = $db->prepare($query);
    
    // Liaison des paramètres
    $stmt->bindParam(":user_id", $user_id);
    
    // Exécution de la requête
    $stmt->execute();
    
    // Vérification si des produits existent
    if ($stmt->rowCount() > 0) {
        // Tableau pour stocker les produits
        $products_arr = array();
        
        // Récupération des résultats
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $product_item = array(
                "id" => $row['id'],
                "reference" => $row['reference'],
                "label" => $row['label'],
                "name" => $row['name'],
                "description" => $row['description'],
                "photo_url" => $row['photo_url'],
                "barcode" => $row['barcode'],
                "quantity" => $row['quantity'],
                "price" => $row['price'],
                "prix_min_vente" => $row['prix_min_vente'],
                "prix_vente_conseille" => $row['prix_vente_conseille'],
                "user_id" => $row['user_id']
            );
            
            array_push($products_arr, $product_item);
        }
        
        // Réponse avec la liste des produits
        Response::json($products_arr);
    } else {
        // Aucun produit trouvé
        Response::json(array());
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
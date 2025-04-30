
<?php
// Fichier: api/products/list_cached.php
// Version optimisée de list.php avec mise en cache Redis

// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclure les fichiers de configuration et d'utilitaires
include_once '../config/database.php';
include_once '../utils/response.php';
include_once '../utils/CacheManager.php';

// Initialiser le gestionnaire de cache
$cacheConfig = include('../config/cache.php');
$cache = new CacheManager($cacheConfig);

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
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;

// Générer la clé de cache
$cacheKey = $cache->generateProductsKey($user_id, [
    'page' => $page,
    'limit' => $limit
]);

// Essayer de récupérer depuis le cache
if ($cache->isEnabled()) {
    $cachedProducts = $cache->get($cacheKey);
    
    if ($cachedProducts !== null) {
        // Définir un en-tête indiquant que c'est un hit du cache
        header('X-Cache: HIT');
        echo json_encode($cachedProducts);
        exit;
    }
}

// Si nous sommes ici, pas de hit de cache - marquer comme un miss
if ($cache->isEnabled()) {
    header('X-Cache: MISS');
}

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Préparation de la requête avec pagination
    $query = "SELECT id, reference, label, name, description, photo_url, 
              barcode, quantity, price, user_id
              FROM products 
              WHERE user_id = :user_id 
              ORDER BY reference ASC
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    // Liaison des paramètres
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
    $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
    
    // Exécution de la requête
    $stmt->execute();
    
    // Récupération des résultats
    $products_arr = array();
    
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
            "user_id" => $row['user_id']
        );
        
        array_push($products_arr, $product_item);
    }
    
    // Mettre en cache le résultat
    if ($cache->isEnabled()) {
        $cache->set($cacheKey, $products_arr, $cacheConfig['ttl']['products']);
    }
    
    // Réponse avec la liste des produits
    echo json_encode($products_arr);
    
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
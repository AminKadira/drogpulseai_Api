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
include_once '../config/database.php';
include_once '../utils/response.php';

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Méthode non autorisée", 405);
    exit;
}

// Récupération des données soumises
$data = json_decode(file_get_contents("php://input"), true);

// Vérification des données requises
if (!isset($data['user_id']) || empty($data['user_id'])) {
    Response::error("ID utilisateur requis");
    exit;
}

$user_id = intval($data['user_id']);

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Construction de la requête de base
    $query = "SELECT c.*, 
              CONCAT(co.prenom, ' ', co.nom) as contact_name,
              co.telephone as contact_telephone,
              co.email as contact_email,
              (SELECT COUNT(*) FROM cart_items ci WHERE ci.cart_id = c.id) as items_count,
              (SELECT SUM(ci.quantity) FROM cart_items ci WHERE ci.cart_id = c.id) as total_quantity,
              (SELECT SUM(ci.quantity * ci.price) FROM cart_items ci WHERE ci.cart_id = c.id) as total_amount
              FROM carts c
              JOIN contacts co ON c.contact_id = co.id
              WHERE c.user_id = :user_id";
    
    // Tableaux pour stocker les paramètres et les clauses WHERE supplémentaires
    $params = [':user_id' => $user_id];
    $whereClauses = [];
    
    // Filtre par statut(s)
    if (isset($data['status']) && !empty($data['status'])) {
        $statuses = $data['status'];
        if (!is_array($statuses)) {
            $statuses = [$statuses]; // Convertir en tableau si c'est une chaîne
        }
        
        if (count($statuses) > 0) {
            $statusPlaceholders = [];
            foreach ($statuses as $key => $status) {
                $placeholder = ":status_" . $key;
                $statusPlaceholders[] = $placeholder;
                $params[$placeholder] = $status;
            }
            
            $whereClauses[] = "c.status IN (" . implode(", ", $statusPlaceholders) . ")";
        }
    }
    
    // Filtre par date
    if (isset($data['date_filter']) && !empty($data['date_filter'])) {
        $date_filter = $data['date_filter'];
        $now = new \DateTime();
        $today = $now->format('Y-m-d');
        
        switch ($date_filter) {
            case 'today':
                $whereClauses[] = "DATE(c.created_at) = :today";
                $params[':today'] = $today;
                break;
                
            case 'yesterday':
                $yesterday = (new \DateTime())->modify('-1 day')->format('Y-m-d');
                $whereClauses[] = "DATE(c.created_at) = :yesterday";
                $params[':yesterday'] = $yesterday;
                break;
                
            case 'this_week':
                $start_of_week = (new \DateTime())->modify('monday this week')->format('Y-m-d');
                $whereClauses[] = "DATE(c.created_at) >= :start_of_week";
                $params[':start_of_week'] = $start_of_week;
                break;
                
            case 'last_week':
                $start_of_last_week = (new \DateTime())->modify('monday last week')->format('Y-m-d');
                $end_of_last_week = (new \DateTime())->modify('sunday last week')->format('Y-m-d');
                $whereClauses[] = "DATE(c.created_at) >= :start_of_last_week AND DATE(c.created_at) <= :end_of_last_week";
                $params[':start_of_last_week'] = $start_of_last_week;
                $params[':end_of_last_week'] = $end_of_last_week;
                break;
                
            case 'this_month':
                $start_of_month = (new \DateTime())->modify('first day of this month')->format('Y-m-d');
                $whereClauses[] = "DATE(c.created_at) >= :start_of_month";
                $params[':start_of_month'] = $start_of_month;
                break;
                
            case 'last_month':
                $start_of_last_month = (new \DateTime())->modify('first day of last month')->format('Y-m-d');
                $end_of_last_month = (new \DateTime())->modify('last day of last month')->format('Y-m-d');
                $whereClauses[] = "DATE(c.created_at) >= :start_of_last_month AND DATE(c.created_at) <= :end_of_last_month";
                $params[':start_of_last_month'] = $start_of_last_month;
                $params[':end_of_last_month'] = $end_of_last_month;
                break;
                
            case 'this_year':
                $start_of_year = (new \DateTime())->modify('first day of january this year')->format('Y-m-d');
                $whereClauses[] = "DATE(c.created_at) >= :start_of_year";
                $params[':start_of_year'] = $start_of_year;
                break;
        }
    }
    
    // Filtre par plage de dates personnalisée
    if (isset($data['start_date']) && !empty($data['start_date'])) {
        $whereClauses[] = "DATE(c.created_at) >= :start_date";
        $params[':start_date'] = $data['start_date'];
    }
    
    if (isset($data['end_date']) && !empty($data['end_date'])) {
        $whereClauses[] = "DATE(c.created_at) <= :end_date";
        $params[':end_date'] = $data['end_date'];
    }
    
    // Ajouter les clauses WHERE à la requête principale
    if (!empty($whereClauses)) {
        $query .= " AND " . implode(" AND ", $whereClauses);
    }
    
    // Ordre de tri
    $query .= " ORDER BY c.created_at DESC";
    
    // Pagination (par défaut: page 1, limite 20)
    $page = isset($data['page']) ? intval($data['page']) : 1;
    $limit = isset($data['limit']) ? intval($data['limit']) : 20;
    $offset = ($page - 1) * $limit;
    
    // Ajouter la limite à la requête
    $query .= " LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    // Préparation et exécution de la requête
    $stmt = $db->prepare($query);
    
    // Liaison des paramètres
    foreach ($params as $key => $value) {
        if (strpos($key, 'limit') !== false || strpos($key, 'offset') !== false) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    
    // Récupération des résultats
    $carts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Requête pour compter le nombre total de paniers (sans pagination)
    $count_query = str_replace("SELECT c.*", "SELECT COUNT(*) as total", $query);
    $count_query = preg_replace("/LIMIT.+$/", "", $count_query);
    
    $count_stmt = $db->prepare($count_query);
    
    // Liaison des paramètres (sans limit et offset)
    foreach ($params as $key => $value) {
        if (strpos($key, 'limit') === false && strpos($key, 'offset') === false) {
            $count_stmt->bindValue($key, $value);
        }
    }
    
    $count_stmt->execute();
    $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $total_carts = $count_result['total'];
    
    // Construction de la réponse
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
    
    // Envoyer la réponse JSON
    echo json_encode($response);
    
} catch (PDOException $e) {
    // Log l'erreur en interne
    error_log("Database error in cart filtering: " . $e->getMessage());
    Response::error("Erreur de base de données", 500);
} catch (Exception $e) {
    // Log l'erreur en interne
    error_log("General error in cart filtering: " . $e->getMessage());
    Response::error("Une erreur est survenue", 500);
}
?>
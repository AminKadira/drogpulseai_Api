<?php
// api/products/compare_supplier_prices.php

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

// Récupération de l'ID du produit
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    Response::error("ID du produit requis");
    exit;
}

$product_id = intval($_GET['product_id']);

// Paramètres optionnels
$only_active = isset($_GET['only_active']) ? (bool)$_GET['only_active'] : false;
$with_history = isset($_GET['with_history']) ? (bool)$_GET['with_history'] : false;
$history_months = isset($_GET['history_months']) ? intval($_GET['history_months']) : 12;
$supplier_ids = isset($_GET['supplier_ids']) ? explode(',', $_GET['supplier_ids']) : [];

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Construction de la requête en fonction des filtres
    $query = "SELECT ps.id, ps.product_id, ps.contact_id, ps.notes, ps.price, ps.delivery_time,
                    c.nom, c.prenom, c.telephone, c.email, ps.is_active
              FROM product_suppliers ps
              JOIN contacts c ON ps.contact_id = c.id
              WHERE ps.product_id = :product_id AND ps.price IS NOT NULL";
    
    // Filtrage par fournisseurs spécifiques
    if (!empty($supplier_ids)) {
        $placeholders = implode(',', array_fill(0, count($supplier_ids), '?'));
        $query .= " AND ps.contact_id IN ($placeholders)";
    }
    
    // Filtrer par statut actif
    if ($only_active) {
        $query .= " AND ps.is_active = 1";
    }
    
    $query .= " ORDER BY ps.price ASC"; // Tri par prix croissant
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":product_id", $product_id);
    
    // Lier les paramètres de fournisseurs
    if (!empty($supplier_ids)) {
        $param_index = 1;
        foreach ($supplier_ids as $supplier_id) {
            $stmt->bindValue($param_index++, intval($supplier_id), PDO::PARAM_INT);
        }
    }
    
    $stmt->execute();
    
    // Tableau pour stocker les résultats
    $suppliers = array();
    
    // Récupération des résultats
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $supplier = array(
            "id" => $row['id'],
            "product_id" => $row['product_id'],
            "contact_id" => $row['contact_id'],
            "notes" => $row['notes'],
            "prix" => $row['price'],
            "delivery_time" => $row['delivery_time'],
            "supplier_name" => $row['prenom'] . ' ' . $row['nom'],
            "telephone" => $row['telephone'],
            "email" => $row['email'],
            "is_active" => (bool)$row['is_active']
        );
        
        array_push($suppliers, $supplier);
    }
    
    // Historique des prix si demandé
    $price_history = array();
    if ($with_history) {
        $history_query = "SELECT ps.contact_id, ps.price, ps.updated_at, 
                               c.nom, c.prenom
                          FROM product_suppliers ps
                          JOIN contacts c ON ps.contact_id = c.id
                          WHERE ps.product_id = :product_id
                          AND ps.updated_at >= DATE_SUB(NOW(), INTERVAL :months MONTH)
                          ORDER BY ps.contact_id ASC, ps.updated_at DESC";
        
        $history_stmt = $db->prepare($history_query);
        $history_stmt->bindParam(":product_id", $product_id);
        $history_stmt->bindParam(":months", $history_months, PDO::PARAM_INT);
        $history_stmt->execute();
        
        $current_supplier = null;
        $supplier_history = array();
        
        while ($row = $history_stmt->fetch(PDO::FETCH_ASSOC)) {
            $supplier_id = $row['contact_id'];
            $supplier_name = $row['prenom'] . ' ' . $row['nom'];
            
            if ($current_supplier !== $supplier_id) {
                if ($current_supplier !== null) {
                    $price_history[$current_supplier] = $supplier_history;
                    $supplier_history = array();
                }
                $current_supplier = $supplier_id;
            }
            
            $supplier_history[] = array(
                "price" => $row['price'],
                "date" => $row['updated_at'],
                "supplier_name" => $supplier_name
            );
        }
        
        // Ajouter le dernier fournisseur
        if ($current_supplier !== null) {
            $price_history[$current_supplier] = $supplier_history;
        }
    }
    
    // Calculer les statistiques sur les prix
    $price_stats = array(
        "count" => 0,
        "min_price" => null,
        "max_price" => null,
        "avg_price" => null,
        "best_supplier" => null,
        "price_range" => null,
        "delivery_stats" => array(
            "min_time" => null,
            "max_time" => null,
            "avg_time" => null
        ),
        "recommendations" => array()
    );
    
    // Si des fournisseurs avec prix sont trouvés
    if (!empty($suppliers)) {
        $price_stats["count"] = count($suppliers);
        
        // Calculer min, max et moyenne
        $total_price = 0;
        $min_price = PHP_FLOAT_MAX;
        $max_price = 0;
        $best_supplier = null;
        
        $delivery_times = array();
        $min_delivery_time = PHP_INT_MAX;
        $max_delivery_time = 0;
        $fastest_supplier = null;
        
        foreach ($suppliers as $supplier) {
            $price = floatval($supplier['prix']);
            $total_price += $price;
            
            if ($price < $min_price) {
                $min_price = $price;
                $best_supplier = $supplier;
            }
            
            if ($price > $max_price) {
                $max_price = $price;
            }
            
            // Statistiques sur les délais de livraison
            if ($supplier['delivery_time'] !== null) {
                $delivery_time = intval($supplier['delivery_time']);
                $delivery_times[] = $delivery_time;
                
                if ($delivery_time < $min_delivery_time) {
                    $min_delivery_time = $delivery_time;
                    $fastest_supplier = $supplier;
                }
                
                if ($delivery_time > $max_delivery_time) {
                    $max_delivery_time = $delivery_time;
                }
            }
        }
        
        $price_stats["min_price"] = $min_price;
        $price_stats["max_price"] = $max_price;
        $price_stats["avg_price"] = $total_price / count($suppliers);
        $price_stats["price_range"] = $max_price - $min_price;
        $price_stats["best_supplier"] = $best_supplier;
        
        // Statistiques de délai de livraison
        if (!empty($delivery_times)) {
            $price_stats["delivery_stats"]["min_time"] = $min_delivery_time;
            $price_stats["delivery_stats"]["max_time"] = $max_delivery_time;
            $price_stats["delivery_stats"]["avg_time"] = array_sum($delivery_times) / count($delivery_times);
            $price_stats["delivery_stats"]["fastest_supplier"] = $fastest_supplier;
        }
        
        // Générer des recommandations
        // 1. Meilleur rapport qualité-prix (si le délai de livraison est fourni)
        if (!empty($delivery_times)) {
            // Score = (prix_min / prix) * (délai_min / délai) * 100
            $max_score = 0;
            $best_value_supplier = null;
            
            foreach ($suppliers as $supplier) {
                if ($supplier['delivery_time'] !== null) {
                    $price_ratio = $min_price / floatval($supplier['prix']);
                    $delivery_ratio = $min_delivery_time / intval($supplier['delivery_time']);
                    $score = $price_ratio * $delivery_ratio * 100;
                    
                    if ($score > $max_score) {
                        $max_score = $score;
                        $best_value_supplier = $supplier;
                    }
                }
            }
            
            if ($best_value_supplier !== null) {
                $price_stats["recommendations"][] = array(
                    "type" => "best_value",
                    "message" => "Meilleur rapport qualité-prix",
                    "supplier" => $best_value_supplier,
                    "score" => round($max_score, 2)
                );
            }
        }
        
        // 2. Recommandation basée sur le prix
        if ($price_stats["price_range"] > 0) {
            $threshold = $price_stats["avg_price"] * 0.05; // 5% de la moyenne
            
            // Si le prix minimum est significativement inférieur à la moyenne
            if ($min_price < ($price_stats["avg_price"] - $threshold)) {
                $price_stats["recommendations"][] = array(
                    "type" => "price_saving",
                    "message" => "Économie significative par rapport au prix moyen",
                    "supplier" => $best_supplier,
                    "saving_percent" => round(100 - ($min_price / $price_stats["avg_price"] * 100), 2)
                );
            }
        }
        
        // 3. Analyse des tendances si l'historique est disponible
        if ($with_history && !empty($price_history)) {
            foreach ($price_history as $supplier_id => $history) {
                if (count($history) > 1) {
                    $oldest_price = $history[count($history) - 1]['price'];
                    $newest_price = $history[0]['price'];
                    $price_change = $newest_price - $oldest_price;
                    
                    if ($price_change < 0) {
                        // Prix en baisse
                        $price_stats["recommendations"][] = array(
                            "type" => "price_trend",
                            "message" => "Tendance à la baisse des prix",
                            "supplier_id" => $supplier_id,
                            "supplier_name" => $history[0]['supplier_name'],
                            "change_percent" => round(($price_change / $oldest_price) * 100, 2)
                        );
                    }
                }
            }
        }
    }
    
    // Réponse avec la liste des fournisseurs, les statistiques et l'historique
    $response = [
        "suppliers" => $suppliers,
        "price_stats" => $price_stats
    ];
    
    if ($with_history) {
        $response["price_history"] = $price_history;
    }
    
    Response::success($response, "Comparaison des prix des fournisseurs effectuée avec succès");
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    Response::error("Erreur de base de données", 500);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    Response::error("Une erreur est survenue", 500);
}
?>
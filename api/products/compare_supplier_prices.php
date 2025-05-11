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

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Requête pour obtenir les fournisseurs du produit avec les prix
    $query = "SELECT ps.id, ps.product_id, ps.contact_id, ps.notes, ps.prix,
                    c.nom, c.prenom, c.telephone, c.email
              FROM product_suppliers ps
              JOIN contacts c ON ps.contact_id = c.id
              WHERE ps.product_id = :product_id AND ps.prix IS NOT NULL
              ORDER BY ps.prix ASC"; // Tri par prix croissant
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":product_id", $product_id);
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
            "prix" => $row['prix'],
            "supplier_name" => $row['prenom'] . ' ' . $row['nom'],
            "telephone" => $row['telephone'],
            "email" => $row['email']
        );
        
        array_push($suppliers, $supplier);
    }
    
    // Calculer les statistiques sur les prix
    $price_stats = array(
        "count" => 0,
        "min_price" => null,
        "max_price" => null,
        "avg_price" => null,
        "best_supplier" => null,
        "price_range" => null
    );
    
    // Si des fournisseurs avec prix sont trouvés
    if (!empty($suppliers)) {
        $price_stats["count"] = count($suppliers);
        
        // Calculer min, max et moyenne
        $total = 0;
        $min_price = PHP_FLOAT_MAX;
        $max_price = 0;
        $best_supplier = null;
        
        foreach ($suppliers as $supplier) {
            $price = floatval($supplier['prix']);
            $total += $price;
            
            if ($price < $min_price) {
                $min_price = $price;
                $best_supplier = $supplier;
            }
            
            if ($price > $max_price) {
                $max_price = $price;
            }
        }
        
        $price_stats["min_price"] = $min_price;
        $price_stats["max_price"] = $max_price;
        $price_stats["avg_price"] = $total / count($suppliers);
        $price_stats["price_range"] = $max_price - $min_price;
        $price_stats["best_supplier"] = $best_supplier;
    }
    
    // Réponse avec la liste des fournisseurs et les statistiques
    Response::success([
        "suppliers" => $suppliers,
        "price_stats" => $price_stats
    ], "Comparaison des prix des fournisseurs effectuée avec succès");
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    Response::error("Erreur de base de données", 500);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    Response::error("Une erreur est survenue", 500);
}
?>
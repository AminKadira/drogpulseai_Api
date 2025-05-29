<?php
// catalogue_global.php - Version adaptée du catalogue affichant tous les produits sans restriction user_id

// Inclusion des fichiers nécessaires
require_once '../api/config/database.php';

// Paramètres du catalogue
$items_per_page = 12; // Nombre de produits par page
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Paramètres de tri et filtres
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'reference';
$sort_dir = isset($_GET['dir']) && $_GET['dir'] === 'desc' ? 'DESC' : 'ASC';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$stock_filter = isset($_GET['stock']) ? $_GET['stock'] : '';

// Connexion à la base de données en utilisant la classe Database
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Construire la requête SQL avec les filtres - SANS la condition user_id
    $sql = "SELECT * FROM products WHERE 1=1"; // 1=1 est un placeholder pour faciliter l'ajout de conditions
    $params = [];
    
    // Ajouter le filtre de recherche
    if (!empty($search)) {
        $sql .= " AND (reference LIKE :search OR name LIKE :search OR label LIKE :search OR description LIKE :search OR barcode LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Ajouter le filtre de prix
    if ($min_price !== null) {
        $sql .= " AND prix_vente_conseille >= :min_price";
        $params[':min_price'] = $min_price;
    }
    
    if ($max_price !== null) {
        $sql .= " AND prix_vente_conseille <= :max_price";
        $params[':max_price'] = $max_price;
    }
    
    // Ajouter le filtre de stock
    if ($stock_filter === 'in_stock') {
        $sql .= " AND quantity > 0";
    } elseif ($stock_filter === 'out_of_stock') {
        $sql .= " AND quantity <= 0";
    } elseif ($stock_filter === 'low_stock') {
        $sql .= " AND quantity > 0 AND quantity <= 5";
    }
    
    // Compter le nombre total de produits pour la pagination
    $count_sql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_products = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_products / $items_per_page);
    
    // Compléter la requête avec ORDER BY et LIMIT
    $valid_sort_fields = ['reference', 'name', 'label', 'prix_vente_conseille', 'quantity', 'created_at'];
    $sort_by = in_array($sort_by, $valid_sort_fields) ? $sort_by : 'reference';
    
    $sql .= " ORDER BY $sort_by $sort_dir LIMIT :offset, :limit";
    $params[':offset'] = $offset;
    $params[':limit'] = $items_per_page;
    
    // Exécuter la requête
    $stmt = $pdo->prepare($sql);
    
    // Lier les paramètres (besoin de traiter différemment les paramètres d'entier pour LIMIT)
    foreach ($params as $key => $value) {
        if ($key === ':offset' || $key === ':limit') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Fonction pour générer l'URL de pagination avec les paramètres actuels
function paginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

// Fonction pour générer l'URL de tri
function sortUrl($field) {
    $params = $_GET;
    $current_sort = isset($_GET['sort']) ? $_GET['sort'] : 'reference';
    $current_dir = isset($_GET['dir']) ? $_GET['dir'] : 'asc';
    
    if ($field === $current_sort) {
        $params['dir'] = ($current_dir === 'asc') ? 'desc' : 'asc';
    } else {
        $params['sort'] = $field;
        $params['dir'] = 'asc';
    }
    
    // Réinitialiser la page à 1 lors du changement de tri
    $params['page'] = 1;
    
    return '?' . http_build_query($params);
}

// Fonction pour afficher une icône de tri
function getSortIcon($field) {
    $current_sort = isset($_GET['sort']) ? $_GET['sort'] : 'reference';
    $current_dir = isset($_GET['dir']) ? $_GET['dir'] : 'asc';
    
    if ($field !== $current_sort) {
        return '<i class="bi bi-arrow-down-up text-muted"></i>';
    }
    
    return ($current_dir === 'asc') 
        ? '<i class="bi bi-sort-down-alt"></i>' 
        : '<i class="bi bi-sort-up"></i>';
}

// Fonction pour vérifier si un filtre est actif
function isFilterActive() {
    return !empty($_GET['search']) || 
           isset($_GET['min_price']) || 
           isset($_GET['max_price']) || 
           !empty($_GET['stock']);
}

// Fonction pour formater le prix
function formatPrice($price) {
    return number_format($price, 2, ',', ' ');
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue Complet des Produits</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Fancybox pour les galeries d'images -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css">
    <!-- CSS personnalisé -->
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
        }
        
        body {
            background-color: var(--light-bg);
            color: var(--secondary-color);
        }
        
        .header-banner {
            background-color: var(--secondary-color);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .product-card {
            height: 100%;
            transition: all 0.3s ease;
            border: none;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 16px rgba(0,0,0,0.1);
        }
        
        .product-image-container {
            height: 220px;
            overflow: hidden;
            position: relative;
            background-color: white;
        }
        
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        
        .card-img-overlay {
            background: rgba(0,0,0,0.6);
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .product-card:hover .card-img-overlay {
            opacity: 1;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .product-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            height: 2.6rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .product-reference {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.75rem;
        }
        
        .product-price {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--accent-color);
            margin-bottom: 0.5rem;
        }
        
        .product-stock {
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }
        
        .badge.bg-success {
            background-color: #28a745 !important;
        }
        
        .badge.bg-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }
        
        .badge.bg-danger {
            background-color: #dc3545 !important;
        }
        
        .action-button {
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
            background-color: white;
            color: var(--secondary-color);
            border: none;
            transition: all 0.2s;
        }
        
        .action-button:hover {
            background-color: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }
        
        .filters-card {
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .pagination .page-link {
            color: var(--secondary-color);
            border-color: #dee2e6;
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .pagination .page-link:hover {
            color: var(--primary-color);
            background-color: #e9ecef;
        }
        
        .sort-link {
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
        }
        
        .sort-link:hover {
            color: var(--primary-color);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 0;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #adb5bd;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #6c757d;
        }
        
        /* Adaptation responsive */
        @media (max-width: 767.98px) {
            .product-card {
                margin-bottom: 1rem;
            }
            .filters-container {
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body>
   <!-- En-tête amélioré avec logo -->
<header class="header-banner">
    <div class="container">
        <div class="header-content">
            <!-- Logo à gauche -->
            <div class="header-logo">
                <a href="index.php" class="d-flex align-items-center">
                    <img src="logo.png" alt="Logo DrogPulseAI" class="logo-img" onerror="this.src='https://placehold.co/200x80?text=LOGO'; this.onerror=null;">
                    <span class="logo-text d-none d-md-inline ms-2">DrogPulseAI</span>
                </a>
            </div>
            
            <!-- Texte du header à droite -->
            <div class="header-text">
                <h1 class="header-title">Catalogue Complet des Produits</h1>
                <p class="header-subtitle">Explorez notre inventaire de produits sans restriction</p>
                
                <!-- Barre de recherche rapide intégrée au header -->
                <div class="quick-search d-none d-md-block">
                    <form action="catalogue_global.php" method="GET" class="search-form">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Rechercher un produit..." aria-label="Rechercher">
                            <button class="btn btn-light" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Styles CSS pour le header -->
<style>
    .header-banner {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        padding: 1.5rem 0;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    
    .header-logo {
        flex: 0 0 auto;
        margin-right: 1rem;
        transition: transform 0.2s ease;
    }
    
    .header-logo:hover {
        transform: scale(1.02);
    }
    
    .logo-img {
        height: 60px;
        width: auto;
        max-width: 200px;
        object-fit: contain;
        border-radius: 4px;
    }
    
    .logo-text {
        font-weight: 700;
        font-size: 1.4rem;
        letter-spacing: -0.5px;
        color: white;
        margin-left: 0.5rem;
    }
    
    .header-text {
        flex: 1 1 auto;
        text-align: right;
    }
    
    .header-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.2rem;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
    }
    
    .header-subtitle {
        font-size: 1rem;
        opacity: 0.9;
        margin-bottom: 0.75rem;
    }
    
    .quick-search {
        max-width: 400px;
        margin-left: auto;
    }
    
    .search-form .input-group {
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
        padding: 2px;
    }
    
    .search-form .form-control {
        background-color: rgba(255, 255, 255, 0.9);
        border: none;
        color: #333;
    }
    
    .search-form .btn {
        color: #2c3e50;
        border-color: transparent;
    }
    
    /* Styles responsives */
    @media (max-width: 991.98px) {
        .header-title {
            font-size: 1.75rem;
        }
        
        .header-subtitle {
            font-size: 0.9rem;
        }
    }
    
    @media (max-width: 767.98px) {
        .header-content {
            flex-direction: column;
            text-align: center;
        }
        
        .header-logo {
            margin-right: 0;
            margin-bottom: 1rem;
        }
        
        .header-text {
            text-align: center;
        }
        
        .header-title {
            font-size: 1.5rem;
        }
        
        .logo-img {
            height: 50px;
        }
    }
    
    @media (max-width: 575.98px) {
        .header-banner {
            padding: 1rem 0;
        }
        
        .header-title {
            font-size: 1.25rem;
        }
        
        .header-subtitle {
            font-size: 0.8rem;
        }
    }
</style>
    
    <div class="container mb-5">
        <!-- Filtres -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card filters-card">
                    <div class="card-body">
                        <form action="" method="GET" id="filterForm">
                            <input type="hidden" name="sort" value="<?php echo isset($_GET['sort']) ? htmlspecialchars($_GET['sort']) : 'reference'; ?>">
                            <input type="hidden" name="dir" value="<?php echo isset($_GET['dir']) ? htmlspecialchars($_GET['dir']) : 'asc'; ?>">
                            
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Recherche</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" class="form-control" id="search" name="search" placeholder="Référence, nom, description..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="min_price" class="form-label">Prix min</label>
                                    <input type="number" class="form-control" id="min_price" name="min_price" placeholder="Min" min="0" step="0.01" value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="max_price" class="form-label">Prix max</label>
                                    <input type="number" class="form-control" id="max_price" name="max_price" placeholder="Max" min="0" step="0.01" value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="stock" class="form-label">Disponibilité</label>
                                    <select class="form-select" id="stock" name="stock">
                                        <option value="">Tous les produits</option>
                                        <option value="in_stock" <?php echo ($stock_filter === 'in_stock') ? 'selected' : ''; ?>>En stock</option>
                                        <option value="low_stock" <?php echo ($stock_filter === 'low_stock') ? 'selected' : ''; ?>>Stock faible</option>
                                        <option value="out_of_stock" <?php echo ($stock_filter === 'out_of_stock') ? 'selected' : ''; ?>>Rupture de stock</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-funnel me-1"></i> Filtrer
                                        </button>
                                        <?php if (isFilterActive()): ?>
                                        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-circle me-1"></i> Réinitialiser
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Informations et tri -->
        <div class="row mb-4">
            <div class="col-md-6">
                <p class="mb-0">
                    <strong><?php echo $total_products; ?></strong> produits trouvés
                    <?php if (isFilterActive()): ?>
                    <span class="text-muted">(filtres appliqués)</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-sort-alpha-down me-1"></i> Trier par
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo sortUrl('reference'); ?>">Référence <?php echo $sort_by === 'reference' ? ($sort_dir === 'asc' ? '↑' : '↓') : ''; ?></a></li>
                        <li><a class="dropdown-item" href="<?php echo sortUrl('name'); ?>">Nom <?php echo $sort_by === 'name' ? ($sort_dir === 'asc' ? '↑' : '↓') : ''; ?></a></li>
                        <li><a class="dropdown-item" href="<?php echo sortUrl('prix_vente_conseille'); ?>">Prix <?php echo $sort_by === 'prix_vente_conseille' ? ($sort_dir === 'asc' ? '↑' : '↓') : ''; ?></a></li>
                        <li><a class="dropdown-item" href="<?php echo sortUrl('quantity'); ?>">Stock <?php echo $sort_by === 'quantity' ? ($sort_dir === 'asc' ? '↑' : '↓') : ''; ?></a></li>
                        <li><a class="dropdown-item" href="<?php echo sortUrl('created_at'); ?>">Date d'ajout <?php echo $sort_by === 'created_at' ? ($sort_dir === 'asc' ? '↑' : '↓') : ''; ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Liste des produits -->
        <?php if (empty($products)): ?>
        <div class="empty-state">
            <i class="bi bi-search"></i>
            <h3>Aucun produit trouvé</h3>
            <p class="text-muted">Essayez de modifier vos critères de recherche ou de réinitialiser les filtres.</p>
            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-primary">Voir tous les produits</a>
        </div>
        <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
            <?php foreach ($products as $product): ?>
                <?php
                // Déterminer le statut du stock
                $stock_status = '';
                $stock_badge_class = '';
                
                if ($product['quantity'] <= 0) {
                    $stock_status = 'Rupture de stock';
                    $stock_badge_class = 'bg-danger';
                } elseif ($product['quantity'] <= 5) {
                    $stock_status = 'Stock faible';
                    $stock_badge_class = 'bg-warning';
                } else {
                    $stock_status = 'En stock';
                    $stock_badge_class = 'bg-success';
                }
                
                // Préparer l'URL de l'image
                $image_url = !empty("../api/".$product['photo_url']) 
                    ? htmlspecialchars("../api/".$product['photo_url']) 
                    : 'assets/images/no-image.png';
                ?>
                <div class="col">
                    <div class="card product-card">
                        <div class="product-image-container">
                            <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                            <div class="card-img-overlay">
                                <div class="d-flex">
                                    <button type="button" class="action-button view-details" data-id="<?php echo $product['id']; ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <a href="<?php echo $image_url; ?>" class="action-button" data-fancybox="gallery-<?php echo $product['id']; ?>">
                                        <i class="bi bi-zoom-in"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="product-reference">Réf: <?php echo htmlspecialchars($product['reference']); ?></p>
                            <div class="product-price"><?php echo formatPrice($product['prix_vente_conseille']); ?> €</div>
                            <div class="product-stock">
                                <span class="badge <?php echo $stock_badge_class; ?>"><?php echo $stock_status; ?></span>
                                <?php if ($product['quantity'] > 0): ?>
                                <small class="text-muted">(<?php echo $product['quantity']; ?> disponibles)</small>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-2 view-details" data-id="<?php echo $product['id']; ?>">
                                <i class="bi bi-info-circle me-1"></i> Détails
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Pagination du catalogue" class="mt-5">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo paginationUrl(max(1, $current_page - 1)); ?>" aria-label="Précédent">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                
                <?php
                // Déterminer les pages à afficher
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                // Toujours afficher la première page
                if ($start_page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="' . paginationUrl(1) . '">1</a></li>';
                    if ($start_page > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                
                // Pages autour de la page courante
                for ($i = $start_page; $i <= $end_page; $i++) {
                    echo '<li class="page-item ' . (($i == $current_page) ? 'active' : '') . '">';
                    echo '<a class="page-link" href="' . paginationUrl($i) . '">' . $i . '</a>';
                    echo '</li>';
                }
                
                // Toujours afficher la dernière page
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="' . paginationUrl($total_pages) . '">' . $total_pages . '</a></li>';
                }
                ?>
                
                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo paginationUrl(min($total_pages, $current_page + 1)); ?>" aria-label="Suivant">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Modal détail produit -->
    <div class="modal fade" id="productDetailModal" tabindex="-1" aria-labelledby="productDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productDetailModalLabel">Détails du produit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Fancybox pour les galeries d'images -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialiser Fancybox
        Fancybox.bind("[data-fancybox]", {
            // Options
        });
        
        // Gérer le clic sur le bouton de détails
        $('.view-details').on('click', function() {
            const productId = $(this).data('id');
            const modal = new bootstrap.Modal(document.getElementById('productDetailModal'));
            
            // Afficher le modal
            modal.show();
            
            // Charger les détails du produit
            $.ajax({
                url: 'product_details_ajax.php',
                type: 'GET',
                data: { id: productId },
                success: function(response) {
                    $('#productDetailModal .modal-body').html(response);
                    
                    // Initialiser les vignettes de la galerie
                    $('.product-gallery-thumb').on('click', function() {
                        const imgSrc = $(this).data('src');
                        $('.product-gallery-main img').attr('src', imgSrc);
                        $('.product-gallery-thumb').removeClass('active');
                        $(this).addClass('active');
                    });
                    
                    // Réinitialiser Fancybox pour les nouvelles images
                    Fancybox.bind('#productDetailModal [data-fancybox]', {
                        // Options
                    });
                },
                error: function() {
                    $('#productDetailModal .modal-body').html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Une erreur est survenue lors du chargement des détails du produit.
                        </div>
                    `);
                }
            });
        });
    });
    </script>
</body>
</html>
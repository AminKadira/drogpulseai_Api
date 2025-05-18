<?php
// product_details_ajax.php - Script pour afficher les détails produit sans restriction d'utilisateur

// Inclusion des fichiers nécessaires
require_once '../api/config/database.php';

// Vérifier l'ID du produit
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-danger">ID du produit manquant</div>';
    exit;
}

$product_id = intval($_GET['id']);

// Connexion à la base de données en utilisant la classe Database
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Récupérer les détails du produit sans restriction d'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo '<div class="alert alert-warning">Produit introuvable</div>';
        exit;
    }
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer les informations sur l'utilisateur propriétaire du produit
    $user_stmt = $pdo->prepare("SELECT nom, prenom FROM users WHERE id = :user_id");
    $user_stmt->bindParam(':user_id', $product['user_id'], PDO::PARAM_INT);
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
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
    
    // Récupérer les fournisseurs du produit (si disponible)
    $suppliers = [];
    $suppliers_stmt = $pdo->prepare("
        SELECT ps.*, c.nom, c.prenom, c.telephone
        FROM product_suppliers ps
        JOIN contacts c ON ps.contact_id = c.id
        WHERE ps.product_id = :product_id
        ORDER BY ps.is_primary DESC, ps.price ASC
    ");
    $suppliers_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $suppliers_stmt->execute();
    
    if ($suppliers_stmt->rowCount() > 0) {
        $suppliers = $suppliers_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Fonction pour formater le prix
    function formatPrice($price) {
        return number_format($price, 2, ',', ' ');
    }
    
?>
<div class="product-detail-header">
    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
    <p class="mb-0 text-muted">Référence: <?php echo htmlspecialchars($product['reference']); ?></p>
</div>

<div class="container-fluid mt-3">
    <div class="row">
        <!-- Galerie d'images -->
        <div class="col-md-6">
            <div class="product-gallery">
                <div class="product-gallery-main">
                    <?php if (!empty("../api/".$product['photo_url'])): ?>
                        <a href="<?php echo htmlspecialchars("../api/".$product['photo_url']); ?>" data-fancybox="product-gallery">
                            <img src="<?php echo htmlspecialchars("../api/".$product['photo_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </a>
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center bg-light" style="height: 300px;">
                            <i class="bi bi-image text-muted" style="font-size: 48px;"></i>
                            <span class="ms-2 text-muted">Pas d'image disponible</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty("../api/".$product['photo_url']) || !empty($product['photo_url2']) || !empty($product['photo_url3'])): ?>
                <div class="product-gallery-thumbs">
                    <?php if (!empty("../api/".$product['photo_url'])): ?>
                    <img src="<?php echo htmlspecialchars("../api/".$product['photo_url']); ?>" 
                         class="product-gallery-thumb active" 
                         data-src="<?php echo htmlspecialchars("../api/".$product['photo_url']); ?>">
                    <?php endif; ?>
                    
                    <?php if (!empty($product['photo_url2'])): ?>
                    <img src="<?php echo htmlspecialchars($product['photo_url2']); ?>" 
                         class="product-gallery-thumb" 
                         data-src="<?php echo htmlspecialchars($product['photo_url2']); ?>">
                    <?php endif; ?>
                    
                    <?php if (!empty($product['photo_url3'])): ?>
                    <img src="<?php echo htmlspecialchars($product['photo_url3']); ?>" 
                         class="product-gallery-thumb" 
                         data-src="<?php echo htmlspecialchars($product['photo_url3']); ?>">
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Informations du produit -->
        <div class="col-md-6">
            <div class="product-details-price">
                <?php echo formatPrice($product['price']); ?> €
            </div>
            
            <div class="product-details-stock">
                <span class="badge <?php echo $stock_badge_class; ?> py-2 px-3"><?php echo $stock_status; ?></span>
                <?php if ($product['quantity'] > 0): ?>
                <span class="ms-2"><?php echo $product['quantity']; ?> unités disponibles</span>
                <?php endif; ?>
            </div>
            
            <!-- Ajout des informations sur le propriétaire du produit -->
            <?php if (!empty($user)): ?>
            <div class="product-details-owner mt-2 mb-3">
                <div class="card border-0 bg-light">
                    <div class="card-body py-2">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-person-circle me-2 text-primary" style="font-size: 1.2rem;"></i>
                            <div>
                                <small class="text-muted">Géré par:</small>
                                <div><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($product['label'])): ?>
            <div class="product-details-section">
                <h5>Label</h5>
                <p><?php echo htmlspecialchars($product['label']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($product['barcode'])): ?>
            <div class="product-details-section">
                <h5>Code-barres</h5>
                <p><?php echo htmlspecialchars($product['barcode']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($product['description'])): ?>
            <div class="product-details-section">
                <h5>Description</h5>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Informations de prix -->
            <div class="product-details-section">
                <h5>Informations de prix</h5>
                <div class="row g-2">
                    <?php if (isset($product['cout_de_revient_unitaire']) && $product['cout_de_revient_unitaire'] > 0): ?>
                    <div class="col-4">
                        <div class="card bg-light">
                            <div class="card-body p-2 text-center">
                                <small class="d-block text-muted">Coût unitaire</small>
                                <strong><?php echo formatPrice($product['cout_de_revient_unitaire']); ?> €</strong>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($product['prix_min_vente']) && $product['prix_min_vente'] > 0): ?>
                    <div class="col-4">
                        <div class="card bg-light">
                            <div class="card-body p-2 text-center">
                                <small class="d-block text-muted">Prix min.</small>
                                <strong><?php echo formatPrice($product['prix_min_vente']); ?> €</strong>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($product['prix_vente_conseille']) && $product['prix_vente_conseille'] > 0): ?>
                    <div class="col-4">
                        <div class="card bg-light">
                            <div class="card-body p-2 text-center">
                                <small class="d-block text-muted">Prix conseillé</small>
                                <strong><?php echo formatPrice($product['prix_vente_conseille']); ?> €</strong>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Dates de création et mise à jour -->
            <div class="product-details-section">
                <h5>Informations temporelles</h5>
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">Créé le:</small>
                        <div><?php echo date('d/m/Y H:i', strtotime($product['created_at'])); ?></div>
                    </div>
                    <?php if (!empty($product['updated_at'])): ?>
                    <div class="col-md-6">
                        <small class="text-muted">Dernière mise à jour:</small>
                        <div><?php echo date('d/m/Y H:i', strtotime($product['updated_at'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Fournisseurs -->
            <?php if (!empty($suppliers)): ?>
            <div class="product-details-section">
                <h5>Fournisseurs</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Fournisseur</th>
                                <th>Prix</th>
                                <th>Délai</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($supplier['prenom'] . ' ' . $supplier['nom']); ?>
                                    <?php if ($supplier['is_primary']): ?>
                                    <span class="badge bg-primary ms-1">Principal</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatPrice($supplier['price']); ?> €</td>
                                <td><?php echo $supplier['delivery_time'] ? $supplier['delivery_time'] . ' jours' : '-'; ?></td>
                                <td>
                                    <?php if ($supplier['is_active']): ?>
                                    <span class="badge bg-success">Actif</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Inactif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <div class="row">
                    <div class="col">
                        <?php if ($product['quantity'] > 0): ?>
                        <button type="button" class="btn btn-primary w-100">
                            <i class="bi bi-cart-plus me-1"></i> Ajouter au panier
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-secondary w-100" disabled>
                            <i class="bi bi-x-circle me-1"></i> Indisponible
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="col-auto">
                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erreur de base de données: ' . $e->getMessage() . '</div>';
}
?>
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
    <!-- Titre du produit - Bien visible en haut -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="product-title fw-bold"><?php echo htmlspecialchars($product['name']); ?></h2>
            <div class="d-flex align-items-center">
                <span class="badge <?php echo $stock_badge_class; ?> me-2"><?php echo $stock_status; ?></span>
                <span class="product-reference text-muted">Réf: <?php echo htmlspecialchars($product['reference']); ?></span>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Galerie d'images - Optimisée pour le responsive -->
        <div class="col-md-6 mb-4">
            <div class="product-gallery">
                <!-- Image principale avec contrôle responsive -->
                <div class="product-gallery-main mb-3 position-relative">
                    <?php if (!empty($product['photo_url'])): ?>
                        <div class="main-image-container">
                            <a href="<?php echo htmlspecialchars("../api/".$product['photo_url']); ?>" data-fancybox="product-gallery" class="d-block">
                                <img src="<?php echo htmlspecialchars("../api/".$product['photo_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     id="main-product-image" 
                                     class="img-fluid rounded product-main-image">
                            </a>
                        </div>
                        
                        <!-- Contrôles de navigation pour mobile et desktop -->
                        <div class="gallery-controls d-flex justify-content-between position-absolute top-50 start-0 end-0 px-2">
                            <button type="button" class="btn btn-light btn-sm rounded-circle shadow-sm gallery-control" id="prev-image" aria-label="Image précédente">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <button type="button" class="btn btn-light btn-sm rounded-circle shadow-sm gallery-control" id="next-image" aria-label="Image suivante">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center bg-light rounded" style="height: 300px;">
                            <div class="text-center">
                                <i class="bi bi-image text-muted" style="font-size: 48px;"></i>
                                <div class="mt-2 text-muted">Pas d'image disponible</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Miniatures - Responsive avec scroll sur mobile -->
                <?php 
                $has_photo_url = !empty($product['photo_url']);
                $has_photo_url2 = !empty($product['photo_url2']);
                $has_photo_url3 = !empty($product['photo_url3']);
                
                if ($has_photo_url || $has_photo_url2 || $has_photo_url3): 
                ?>
                <div class="product-gallery-thumbs d-flex overflow-auto pb-2">
                    <?php if ($has_photo_url): ?>
                    <div class="thumb-container me-2">
                        <img src="<?php echo htmlspecialchars("../api/".$product['photo_url']); ?>" 
                             class="product-gallery-thumb active" 
                             data-src="<?php echo htmlspecialchars("../api/".$product['photo_url']); ?>"
                             data-index="0"
                             alt="Miniature 1">
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($has_photo_url2): ?>
                    <div class="thumb-container me-2">
                        <img src="<?php echo htmlspecialchars("../api/".$product['photo_url2']); ?>" 
                             class="product-gallery-thumb" 
                             data-src="<?php echo htmlspecialchars("../api/".$product['photo_url2']); ?>"
                             data-index="1"
                             alt="Miniature 2">
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($has_photo_url3): ?>
                    <div class="thumb-container">
                        <img src="<?php echo htmlspecialchars("../api/".$product['photo_url3']); ?>" 
                             class="product-gallery-thumb" 
                             data-src="<?php echo htmlspecialchars("../api/".$product['photo_url3']); ?>"
                             data-index="2"
                             alt="Miniature 3">
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Informations du produit - Mieux structurées -->
        <div class="col-md-6">
            <!-- Prix principal mis en évidence -->
            <div class="product-details-price mb-3">
                <?php echo formatPrice($product['prix_vente_conseille']); ?> €
            </div>
            
            <!-- Détails de stock -->
            <div class="product-details-stock mb-4">
                <?php if ($product['quantity'] > 0): ?>
                <div class="d-flex align-items-center">
                    <div class="stock-indicator bg-success rounded-circle me-2"></div>
                    <span class="fw-bold"><?php echo $product['quantity']; ?> unités disponibles</span>
                </div>
                <?php else: ?>
                <div class="d-flex align-items-center text-danger">
                    <div class="stock-indicator bg-danger rounded-circle me-2"></div>
                    <span class="fw-bold">Produit indisponible</span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Informations principales dans un tableau -->
            <div class="product-info-card mb-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 fs-5">Caractéristiques</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <tbody>
                                <?php if (!empty($product['label'])): ?>
                                <tr>
                                    <th class="w-25">Label</th>
                                    <td><?php echo htmlspecialchars($product['label']); ?></td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if (!empty($product['reference'])): ?>
                                <tr>
                                    <th>Référence</th>
                                    <td><?php echo htmlspecialchars($product['reference']); ?></td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if (!empty($product['barcode'])): ?>
                                <tr>
                                    <th>Code-barres</th>
                                    <td><?php echo htmlspecialchars($product['barcode']); ?></td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if (isset($product['prix_vente_conseille']) && $product['prix_vente_conseille'] > 0): ?>
                                <tr>
                                    <th>Prix conseillé</th>
                                    <td><?php echo formatPrice($product['prix_vente_conseille']); ?> €</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Description avec formatage amélioré -->
            <?php if (!empty($product['description'])): ?>
            <div class="product-details-section mb-4">
                <h5 class="section-title border-bottom pb-2 mb-3">Description</h5>
                <div class="description-content">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Informations sur le propriétaire du produit -->
            <?php if (!empty($user)): ?>
            <div class="product-details-owner mb-4">
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
            
            <!-- Boutons d'action -->
            <div class="product-actions mt-4">
                <div class="row g-2">
                    <div class="col">
                        <?php if ($product['quantity'] > 0): ?>
                        <button type="button" class="btn btn-primary btn-lg w-100 d-flex align-items-center justify-content-center">
                            <i class="bi bi-cart-plus me-2"></i> Ajouter au panier
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-secondary btn-lg w-100" disabled>
                            <i class="bi bi-x-circle me-2"></i> Indisponible
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="col-auto">
                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles CSS supplémentaires pour la galerie et le responsive -->
<style>
    /* Styles pour la galerie d'images */
    .product-title {
        font-size: 1.75rem;
        line-height: 1.3;
        margin-bottom: 0.5rem;
    }
    
    .product-reference {
        font-size: 0.9rem;
    }
    
    .product-details-price {
        font-size: 1.75rem;
        font-weight: 700;
        color: #d9534f;
    }
    
    .stock-indicator {
        width: 12px;
        height: 12px;
        display: inline-block;
    }
    
    .product-main-image {
        width: 100%;
        height: 350px;
        object-fit: contain;
        background-color: #f8f9fa;
        transition: transform 0.3s ease;
    }
    
    .main-image-container {
        overflow: hidden;
        border-radius: 4px;
        background-color: #f8f9fa;
    }
    
    .gallery-control {
        opacity: 0.7;
        transition: opacity 0.2s ease;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .gallery-control:hover {
        opacity: 1;
    }
    
    .product-gallery-thumb {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 4px;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.2s ease;
    }
    
    .product-gallery-thumb.active {
        border-color: #007bff;
    }
    
    .product-gallery-thumbs {
        scrollbar-width: thin;
    }
    
    .product-gallery-thumbs::-webkit-scrollbar {
        height: 6px;
    }
    
    .product-gallery-thumbs::-webkit-scrollbar-thumb {
        background-color: rgba(0,0,0,0.2);
        border-radius: 3px;
    }
    
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    /* Styles responsives */
    @media (max-width: 767.98px) {
        .product-main-image {
            height: 280px;
        }
        
        .product-gallery-thumb {
            width: 60px;
            height: 60px;
        }
        
        .product-details-price {
            font-size: 1.5rem;
        }
        
        .product-title {
            font-size: 1.5rem;
        }
    }
    
    @media (max-width: 575.98px) {
        .product-main-image {
            height: 240px;
        }
        
        .product-gallery-thumb {
            width: 50px;
            height: 50px;
        }
    }
</style>

<!-- Script JavaScript pour la galerie d'images -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Éléments de la galerie
    const mainImage = document.getElementById('main-product-image');
    const thumbnails = document.querySelectorAll('.product-gallery-thumb');
    const prevButton = document.getElementById('prev-image');
    const nextButton = document.getElementById('next-image');
    
    // Tableau pour stocker les images
    const images = [];
    let currentIndex = 0;
    
    // Remplir le tableau d'images
    thumbnails.forEach(thumb => {
        images.push({
            src: thumb.dataset.src,
            index: parseInt(thumb.dataset.index)
        });
    });
    
    // Fonction pour changer l'image principale
    function changeMainImage(index) {
        if (index < 0) index = images.length - 1;
        if (index >= images.length) index = 0;
        
        currentIndex = index;
        
        // Changer l'image principale
        if (mainImage) {
            mainImage.src = images[index].src;
            
            // Mettre à jour le lien Fancybox
            const mainImageLink = mainImage.closest('a');
            if (mainImageLink) {
                mainImageLink.href = images[index].src;
            }
        }
        
        // Mettre à jour la classe active sur les miniatures
        thumbnails.forEach(thumb => {
            if (parseInt(thumb.dataset.index) === index) {
                thumb.classList.add('active');
            } else {
                thumb.classList.remove('active');
            }
        });
    }
    
    // Événements pour les miniatures
    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            const index = parseInt(this.dataset.index);
            changeMainImage(index);
        });
    });
    
    // Événements pour les boutons de navigation
    if (prevButton) {
        prevButton.addEventListener('click', function() {
            changeMainImage(currentIndex - 1);
        });
    }
    
    if (nextButton) {
        nextButton.addEventListener('click', function() {
            changeMainImage(currentIndex + 1);
        });
    }
    
    // Navigation au clavier
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
            changeMainImage(currentIndex - 1);
        } else if (e.key === 'ArrowRight') {
            changeMainImage(currentIndex + 1);
        }
    });
    
    // Support du balayage sur mobile
    let touchStartX = 0;
    let touchEndX = 0;
    
    if (mainImage) {
        mainImage.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, false);
        
        mainImage.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, false);
    }
    
    function handleSwipe() {
        if (touchEndX < touchStartX) {
            // Balayage vers la gauche
            changeMainImage(currentIndex + 1);
        }
        if (touchEndX > touchStartX) {
            // Balayage vers la droite
            changeMainImage(currentIndex - 1);
        }
    }
});
</script>

<?php
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erreur de base de données: ' . $e->getMessage() . '</div>';
}
?>
-- ------------------------------------------------------------------------------------------------
-- DrogPulseAI - Database Creation Script
-- Version: 1.0
-- Description: Base de données pour application de gestion de stock, contacts et ventes
-- ------------------------------------------------------------------------------------------------

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS drogpulseai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Utilisation de la base de données
USE drogpulseai;

-- ------------------------------------------------------------------------------------------------
-- Table des utilisateurs
-- ------------------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter la colonne Type_User avec une valeur par défaut
ALTER TABLE `users` 
ADD COLUMN `type_user` ENUM('Admin', 'Commercial', 'Vendeur', 'Invité', 'Manager') 
NOT NULL DEFAULT 'Commercial' 
AFTER `email`;

-- ------------------------------------------------------------------------------------------------
-- Table des contacts
-- ------------------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `email` varchar(100) NULL,
  `notes` text NULL,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------------------------
-- Table des produits
-- ------------------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(50) NOT NULL UNIQUE,
  `label` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text,
  `photo_url` varchar(255),
  `barcode` varchar(50),
  `quantity` int(11) DEFAULT 0,
  `price` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Prix du produit',
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `products` 
ADD COLUMN `cout_de_revient_unitaire` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Coût de revient unitaire du produit',
ADD COLUMN `prix_min_vente` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Prix minimum de vente du produit',
ADD COLUMN `prix_vente_conseille` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Prix de vente conseillé du produit';

ALTER TABLE products 
ADD COLUMN photo_url2 VARCHAR(255) DEFAULT NULL AFTER photo_url,
ADD COLUMN photo_url3 VARCHAR(255) DEFAULT NULL AFTER photo_url2;


-- ------------------------------------------------------------------------------------------------
-- Table des fournisseur par produit
-- ------------------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `price` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_supplier_unique` (`product_id`, `contact_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------------------------
-- Table des paniers/commandes
-- ------------------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `carts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `cart_contact_fk` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE product_suppliers
ADD COLUMN notes TEXT DEFAULT NULL,
ADD COLUMN delivery_conditions TEXT DEFAULT NULL,
ADD COLUMN delivery_time INT DEFAULT NULL COMMENT 'Délai de livraison en jours',
ADD COLUMN is_active TINYINT(1) DEFAULT 1;
-- ------------------------------------------------------------------------------------------------
-- Table des éléments de panier
-- ------------------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cart_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cart_id` (`cart_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_item_cart_fk` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_item_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------------------------
-- Table pour l'historique des prix
-- ------------------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `price_path` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `date` DATE NOT NULL,
  `remarque` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_price_date` (`product_id`, `price`, `date`),
  CONSTRAINT `price_path_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------------------------
-- Table pour le suivi des transactions d'inventaire
-- ------------------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tracking_stores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `last_quantity` int(11) NOT NULL COMMENT 'Quantité avant la transaction',
  `transaction` int(11) NOT NULL COMMENT 'Valeur positive pour ajout, négative pour retrait',
  `date_transaction` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL COMMENT 'Utilisateur ayant effectué la transaction',
  `notes` text,
  `remarque` text COMMENT 'Informations supplémentaires sur la transaction',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `tracking_stores_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tracking_stores_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------------------------
-- Table pour le suivi des frais 
-- ------------------------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `description` text,
  `receipt_photo_url` varchar(255),
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `expense_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------------------------------------------
-- Table pour l'historique des frais (sans contrainte de clé étrangère)
-- ------------------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `expenses_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_id` int(11) NOT NULL COMMENT 'ID de référence, même si le frais original est supprimé',
  `type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `description` text,
  `user_id` int(11) NOT NULL,
  `action` enum('CREATE','UPDATE','DELETE') NOT NULL,
  `action_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text COMMENT 'Informations supplémentaires sur la modification',
  PRIMARY KEY (`id`),
  KEY `expense_id` (`expense_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `expense_history_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
  -- Pas de contrainte de clé étrangère pour expense_id
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ------------------------------------------------------------------------------------------------
-- Index pour optimiser les performances des requêtes
-- ------------------------------------------------------------------------------------------------
CREATE INDEX idx_contacts_user_id ON contacts(user_id);
CREATE INDEX idx_products_user_id ON products(user_id);
CREATE INDEX idx_contacts_search ON contacts(nom, prenom, telephone);
CREATE INDEX idx_products_search ON products(reference, label, name, barcode);
CREATE INDEX idx_price_path_product ON price_path(product_id);
CREATE INDEX idx_price_path_date ON price_path(date);
CREATE INDEX idx_tracking_stores_date ON tracking_stores(date_transaction);
CREATE INDEX idx_tracking_stores_transaction ON tracking_stores(transaction);
CREATE INDEX idx_expenses_user_id ON expenses(user_id);
CREATE INDEX idx_expenses_date ON expenses(date);
CREATE INDEX idx_expenses_type ON expenses(type);
CREATE INDEX idx_expenses_history_date ON expenses_history(date);
CREATE INDEX idx_expenses_history_action_date ON expenses_history(action_date);
CREATE INDEX idx_expenses_history_type ON expenses_history(type);
CREATE INDEX idx_expenses_history_action ON expenses_history(action);

-- ------------------------------------------------------------------------------------------------
-- Triggers pour l'historique des prix
-- ------------------------------------------------------------------------------------------------
DELIMITER //

-- Trigger après insertion de nouveaux produits (historique des prix)
CREATE TRIGGER after_product_insert
AFTER INSERT ON products
FOR EACH ROW
BEGIN
    INSERT INTO price_path (product_id, price, date, remarque)
    VALUES (NEW.id, NEW.price, CURDATE(), 'Prix initial à la création du produit');
END//

-- Trigger après mise à jour de produits (historique des prix - uniquement si le prix change)
CREATE TRIGGER after_product_update
AFTER UPDATE ON products
FOR EACH ROW
BEGIN
    -- Vérifier si le prix a changé
    IF NEW.price <> OLD.price THEN
        INSERT INTO price_path (product_id, price, date, remarque)
        VALUES (NEW.id, NEW.price, CURDATE(), 'Mise à jour du prix');
    END IF;
END//

-- Trigger avant suppression de produits (historique des prix)
CREATE TRIGGER before_product_delete
BEFORE DELETE ON products
FOR EACH ROW
BEGIN
    -- Ajouter une entrée finale indiquant que le produit a été supprimé
    INSERT INTO price_path (product_id, price, date, remarque)
    VALUES (OLD.id, OLD.price, CURDATE(), 'Dernier prix avant suppression du produit');
END//

-- ------------------------------------------------------------------------------------------------
-- Triggers pour le suivi des transactions d'inventaire
-- ------------------------------------------------------------------------------------------------

-- Trigger pour tracer les mouvements de stock après insertion d'un produit
CREATE TRIGGER after_product_insert_tracking
AFTER INSERT ON products
FOR EACH ROW
BEGIN
    -- Ajouter une entrée dans tracking_stores pour l'alimentation initiale du stock
    INSERT INTO tracking_stores (
        product_id,
        last_quantity,
        transaction,
        date_transaction,
        user_id,
        remarque
    )
    VALUES (
        NEW.id,           -- ID du produit nouvellement créé
        0,                -- Quantité avant insertion (toujours 0 pour un nouveau produit)
        NEW.quantity,     -- Transaction positive (alimentation initiale)
        NOW(),            -- Date actuelle
        NEW.user_id,      -- Utilisateur ayant créé le produit
        'Suite à une alimentation de stock'  -- Note standard
    );
END//

-- Trigger pour tracer les mouvements de stock lors de la mise à jour d'un produit
CREATE TRIGGER before_product_update_tracking
BEFORE UPDATE ON products
FOR EACH ROW
BEGIN
    -- Déclarations de variables (doivent être au début du bloc)
    DECLARE quantity_diff INT;
    DECLARE mouvement_remarque VARCHAR(255);
    
    -- Vérifier si la quantité a changé
    IF NEW.quantity <> OLD.quantity THEN
        -- Calculer la différence de quantité
        SET quantity_diff = NEW.quantity - OLD.quantity;
        
        -- Déterminer le type de mouvement de stock
        IF quantity_diff > 0 THEN
            SET mouvement_remarque = 'Positive Suite a une alimentation de stock';
        ELSE
            SET mouvement_remarque = 'Negative Suite a mise à jour de stock';
        END IF;
        
        -- Ajouter une entrée dans tracking_stores
        INSERT INTO tracking_stores (
            product_id,
            last_quantity,
            transaction,
            date_transaction,
            user_id,
            remarque
        )
        VALUES (
            OLD.id,           -- ID du produit
            OLD.quantity,     -- Quantité avant mise à jour
            quantity_diff,    -- Différence de quantité (positive ou négative)
            NOW(),            -- Date actuelle
            OLD.user_id,      -- Utilisateur associé au produit
            mouvement_remarque -- Remarque adaptée selon le type de mouvement
        );
    END IF;
END//

-- Trigger après mise à jour d'un panier (pour les commandes confirmées et annulées)
CREATE TRIGGER after_cart_update
AFTER UPDATE ON carts
FOR EACH ROW
BEGIN
    -- Vérifier si le statut a changé à "confirmed"
    IF NEW.status = 'confirmed' AND OLD.status <> 'confirmed' THEN
        -- Pour chaque article du panier, mettre à jour tracking_stores (sortie de stock)
        INSERT INTO tracking_stores (
            product_id,
            last_quantity,
            transaction,
            date_transaction,
            user_id,
            remarque
        )
        SELECT 
            ci.product_id,
            p.quantity,          -- Dernière quantité avant mise à jour
            -ci.quantity,        -- Transaction négative (sortie de stock)
            NOW(),               -- Date actuelle
            NEW.user_id,         -- Utilisateur du panier
            'Suite à une vente'  -- Note standard
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = NEW.id;
        
        -- Mise à jour des quantités dans la table products (diminution)
        UPDATE products p
        JOIN cart_items ci ON p.id = ci.product_id
        SET p.quantity = p.quantity - ci.quantity
        WHERE ci.cart_id = NEW.id;
    
    -- Vérifier si le statut a changé à "cancelled" et que l'ancien statut était "confirmed"
    ELSEIF NEW.status = 'cancelled' AND OLD.status = 'confirmed' THEN
        -- Pour chaque article du panier, mettre à jour tracking_stores (retour en stock)
        INSERT INTO tracking_stores (
            product_id,
            last_quantity,
            transaction,
            date_transaction,
            user_id,
            remarque
        )
        SELECT 
            ci.product_id,
            p.quantity,          -- Dernière quantité avant mise à jour
            ci.quantity,         -- Transaction positive (retour en stock)
            NOW(),               -- Date actuelle
            NEW.user_id,         -- Utilisateur du panier
            'Suite à une annulation commande confirmée'  -- Note d'annulation
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = NEW.id;
        
        -- Mise à jour des quantités dans la table products (augmentation)
        UPDATE products p
        JOIN cart_items ci ON p.id = ci.product_id
        SET p.quantity = p.quantity + ci.quantity
        WHERE ci.cart_id = NEW.id;
    END IF;
END//

-- Trigger après insertion d'un panier avec statut "confirmed"
CREATE TRIGGER after_cart_insert
AFTER INSERT ON carts
FOR EACH ROW
BEGIN
    -- Vérifier si le statut est "confirmed"
    IF NEW.status = 'confirmed' THEN
        -- Même logique que pour le trigger UPDATE
        INSERT INTO tracking_stores (
            product_id,
            last_quantity,
            transaction,
            date_transaction,
            user_id,
            remarque
        )
        SELECT 
            ci.product_id,
            p.quantity,
            -ci.quantity,
            NOW(),
            NEW.user_id,
            'Suite à une vente'
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = NEW.id;
        
        -- Mise à jour des quantités
        UPDATE products p
        JOIN cart_items ci ON p.id = ci.product_id
        SET p.quantity = p.quantity - ci.quantity
        WHERE ci.cart_id = NEW.id;
    END IF;
END//

DELIMITER ;

-- ------------------------------------------------------------------------------------------------
-- Triggers pour l'historique des frais
-- ------------------------------------------------------------------------------------------------
DELIMITER //

-- Trigger après insertion d'un nouveau frais
CREATE TRIGGER after_expense_insert
AFTER INSERT ON expenses
FOR EACH ROW
BEGIN
    INSERT INTO expenses_history (
        expense_id,
        type,
        amount,
        date,
        description,
        user_id,
        action,
        notes
    )
    VALUES (
        NEW.id,
        NEW.type,
        NEW.amount,
        NEW.date,
        NEW.description,
        NEW.user_id,
        'CREATE',
        'Création initiale du frais'
    );
END//

-- Trigger après mise à jour d'un frais
CREATE TRIGGER after_expense_update
AFTER UPDATE ON expenses
FOR EACH ROW
BEGIN
    INSERT INTO expenses_history (
        expense_id,
        type,
        amount,
        date,
        description,
        user_id,
        action,
        notes
    )
    VALUES (
        NEW.id,
        NEW.type,
        NEW.amount,
        NEW.date,
        NEW.description,
        NEW.user_id,
        'UPDATE',
        CONCAT(
            'Mise à jour du frais. ',
            IF(NEW.type <> OLD.type, CONCAT('Type: ', OLD.type, ' -> ', NEW.type, '. '), ''),
            IF(NEW.amount <> OLD.amount, CONCAT('Montant: ', OLD.amount, ' -> ', NEW.amount, '. '), ''),
            IF(NEW.date <> OLD.date, CONCAT('Date: ', OLD.date, ' -> ', NEW.date, '. '), ''),
            IF(NOT (NEW.description <=> OLD.description), 'Description modifiée. ', '')
        )
    );
END//

-- Trigger avant suppression d'un frais (préservant l'historique)
DELIMITER //
CREATE TRIGGER before_expense_delete
BEFORE DELETE ON expenses
FOR EACH ROW
BEGIN
    -- Ajouter une entrée finale dans l'historique pour indiquer la suppression
    INSERT INTO expenses_history (
        expense_id,
        type,
        amount,
        date,
        description,
        user_id,
        action,
        notes
    )
    VALUES (
        OLD.id,
        OLD.type,
        OLD.amount,
        OLD.date,
        OLD.description,
        OLD.user_id,
        'DELETE',
        'Suppression du frais'
    );
END//
DELIMITER ;


-- Créer les triggers pour la table expenses
DELIMITER //

-- Supprimer les triggers s'ils existent déjà
DROP TRIGGER IF EXISTS after_expense_insert_recalculate_product_prices //
DROP TRIGGER IF EXISTS after_expense_update_recalculate_product_prices //
DROP TRIGGER IF EXISTS after_expense_delete_recalculate_product_prices //
DROP PROCEDURE IF EXISTS update_product_prices //

-- Procédure de mise à jour des prix basée sur les charges indirectes
CREATE PROCEDURE update_product_prices()
BEGIN
    -- Variables pour le calcul
    DECLARE taux_marge_souhaite DECIMAL(5,2) DEFAULT 30.00; -- 30% par défaut
    
    -- Mise à jour des produits dont la quantité est supérieure à zéro
    UPDATE products p
    JOIN (
        -- Sous-requête pour calculer la quote-part des charges indirectes
        SELECT 
            p2.id AS product_id,
            p2.price AS prix_achat_unitaire,
            IFNULL(
                (SELECT SUM(amount) 
                 FROM expenses 
                 WHERE created_at >= p2.created_at
                ), 0
            ) AS charges_indirectes_mensuelles,
            p2.quantity AS quantite_vendue_mensuelle,
            -- Clause de sécurité pour éviter division par zéro
            IFNULL(
                (SELECT SUM(amount) 
                 FROM expenses 
                 WHERE created_at >= p2.created_at
                ) / NULLIF(p2.quantity, 0), 
                0
            ) AS quote_part_charges_indirectes
        FROM products p2
        WHERE p2.quantity > 0
    ) AS calculations ON p.id = calculations.product_id
    
    SET 
        -- 3. Calcul du coût de revient unitaire (sans cout_transport_unitaire)
        p.cout_de_revient_unitaire = calculations.prix_achat_unitaire + calculations.quote_part_charges_indirectes,
        
        -- 4. Calcul du prix minimum de vente
        p.prix_min_vente = p.cout_de_revient_unitaire,
        
        -- 5. Calcul du prix conseillé avec marge
        p.prix_vente_conseille = p.cout_de_revient_unitaire / (1 - (taux_marge_souhaite / 100))
    WHERE p.quantity > 0;
END //

-- Trigger après insertion d'une nouvelle charge qui recalcule les prix des produits
CREATE TRIGGER after_expense_insert_recalculate_product_prices
AFTER INSERT ON expenses
FOR EACH ROW
BEGIN
    -- Appel de la procédure de mise à jour des prix
    CALL update_product_prices();
END //

-- Trigger après modification d'une charge qui recalcule les prix des produits
CREATE TRIGGER after_expense_update_recalculate_product_prices
AFTER UPDATE ON expenses
FOR EACH ROW
BEGIN
    -- Appel de la procédure de mise à jour des prix
    CALL update_product_prices();
END //


-- Trigger après suppression d'une charge qui recalcule les prix des produits
CREATE TRIGGER after_expense_delete_recalculate_product_prices
AFTER DELETE ON expenses
FOR EACH ROW
BEGIN
    -- Appel de la procédure de mise à jour des prix
    CALL update_product_prices();
END //

DELIMITER ;


-- Supprimer les triggers s'ils existent déjà
DROP TRIGGER IF EXISTS after_product_insert_recalculate_product_prices;
DROP TRIGGER IF EXISTS after_product_update_recalculate_product_prices;

DELIMITER //

-- Trigger après insertion dans tracking_stores qui recalcule les prix
CREATE TRIGGER after_product_insert_recalculate_product_prices
AFTER INSERT ON carts 
FOR EACH ROW
BEGIN
    CALL update_product_prices();
END //

-- Trigger après modification dans tracking_stores qui recalcule les prix
CREATE TRIGGER after_product_update_recalculate_product_prices
AFTER UPDATE ON carts
FOR EACH ROW
BEGIN
    CALL update_product_prices();
END //

DELIMITER ;
-- ------------------------------------------------------------------------------------------------
-- Données de démonstration
-- ------------------------------------------------------------------------------------------------

-- Création d'un utilisateur de test
INSERT INTO `users` (`nom`, `prenom`, `telephone`, `latitude`, `longitude`, `email`, `password`) VALUES
('Admin', 'System', '0123456789', 48.8566, 2.3522, 'admin@example.com', '$2y$10$UzW0uXBzOcNaUmS8DUQIa.hG8R1nqrhNEzz/lekh2NOL46Lp5QRYe'); -- Mot de passe: admin123


INSERT INTO `contacts` (`nom`, `prenom`, `telephone`, `email`, `notes`, `latitude`, `longitude`, `user_id`) VALUES
('El Alaoui', 'Youssef', '0623456789', 'youssef.alaoui@example.ma', 'Client important', 33.5731, -7.5898, 1),
('Bennis', 'Fatima Zahra', '0634567890', 'fatima.bennis@example.ma', 'Fournisseur principal', 34.0209, -6.8416, 1),
('Bennani', 'Mohammed', '0645678901', 'mohammed.bennani@example.ma', 'Partenaire SAV', 33.9716, -6.8498, 1),
('Amrani', 'Khadija', '0656789012', 'khadija.amrani@example.ma', 'Contact occasionnel', 34.0151, -6.8326, 1),
('Ait', 'Hassan', '0667890123', 'hassan.ait@example.ma', 'Client fidèle', 31.6295, -8.0084, 1),
('El Mansouri', 'Sara', '0678901234', 'sara.elmansouri@example.ma', 'Lead commercial', 35.7785, -5.8340, 1),
('Haddad', 'Ahmed', '0689012345', 'ahmed.haddad@example.ma', 'Fournisseur pièces', 32.2833, -9.2370, 1),
('Idrissi', 'Amina', '0690123456', 'amina.idrissi@example.ma', 'Support technique', 34.0239, -6.8417, 1),
('Fassi', 'Yassine', '0601234567', 'yassine.fassi@example.ma', 'Prospect', 34.0331, -5.0000, 1),
('Alami', 'Salma', '0612345670', 'salma.alami@example.ma', 'Partenaire logistique', 33.5311, -7.6690, 1);


INSERT INTO `products` (`reference`, `label`, `name`, `description`, `barcode`, `quantity`, `price`, `user_id`) VALUES
('PROD-004', 'Cintreuse hydraulique SWG-3', 'Cintreuse hydraulique 1/2 à 3 - SWG-3', 'Cintreuse hydraulique pour tubes de 1/2 à 3 pouces.', '123456789014', 10, 459.99, 8),
('PROD-005', 'Jeu de 9 clés mâles longues', 'Jeu de 9 clés mâles longues 1.5 à 10mm sur râtelier', 'Clés mâles longues en acier, tailles de 1.5 à 10mm.', '123456789015', 25, 24.99, 8),
('PROD-006', 'Meuleuse angulaire Bosch GWS 750', 'Meuleuse angulaire Bosch GWS 750 (115)', 'Meuleuse angulaire 750W, disque de 115mm.', '123456789016', 15, 89.99, 8),
('PROD-007', 'Scie circulaire Bosch GKS 190', 'Scie circulaire Bosch GKS 190 1400W 184mm', 'Scie circulaire puissante de 1400W avec lame de 184mm.', '123456789017', 8, 159.99, 8),
('PROD-008', 'Agrafeuse pneumatique PS111', 'Agrafeuse pneumatique professionnelle PS111 140/6-16mm', 'Agrafeuse pneumatique pour agrafes de 6 à 16mm.', '123456789018', 20, 79.99, 8),
('PROD-009', 'Foret étagé HSS M2 4A20 mm', 'Foret étagé HSS M2 4A20 mm marque Tivoly', 'Foret étagé en acier HSS M2, diamètre 4 à 20mm.', '123456789019', 30, 39.99, 8),
('PROD-010', 'Coffret de 6 tournevis mixtes', 'Coffret de 6 tournevis mixtes – Stanley', 'Ensemble de 6 tournevis pour usages variés.', '123456789020', 40, 29.99, 8),
('PROD-011', 'Boîte à outils aluminium', 'Boîte à outils 41.5x13x36.5 cm en aluminium', 'Boîte à outils en aluminium avec dimensions 41.5x13x36.5 cm.', '123456789021', 12, 49.99, 8),
('PROD-012', 'Trousse à outils lourds', 'Trousse à outils lourds 129×26 cm', 'Trousse robuste pour outils lourds, dimensions 129×26 cm.', '123456789022', 18, 59.99, 8),
('PROD-013', 'Truelle 180mm', 'Truelle 180mm', 'Truelle de maçonnerie de 180mm.', '123456789023', 50, 14.99, 8);




-- Désactiver temporairement le trigger
DELIMITER //
DROP TRIGGER IF EXISTS before_product_delete //
DELIMITER ;

-- Supprimer le produit
DELETE FROM `products` WHERE `products`.`id` = 63 LIMIT 1;

-- Recréer le trigger
DELIMITER //
CREATE TRIGGER before_product_delete
BEFORE DELETE ON products
FOR EACH ROW
BEGIN
    -- Ajouter une entrée finale indiquant que le produit a été supprimé
    INSERT INTO price_path (product_id, price, date, remarque)
    VALUES (OLD.id, OLD.price, CURDATE(), 'Dernier prix avant suppression du produit');
END//
DELIMITER ;

BEGIN
    -- Déclarations des variables
    DECLARE done INT DEFAULT FALSE;
    DECLARE current_user_id INT;
    DECLARE nb_produits INT;
    DECLARE valeur_totale DECIMAL(15,2);
    DECLARE charges_totales DECIMAL(15,2);
    DECLARE first_product_id INT;
    
    -- Déclaration du curseur
    DECLARE user_cursor CURSOR FOR 
        SELECT DISTINCT user_id 
        FROM products 
        ORDER BY user_id;
    
    -- Déclaration du handler
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Variables de session
    SET @quantite_fixe = 100;
    SET @taux_marge = 5.00;
    SET @plafond_charges_pct = 200.00;
    SET @days_lookback = 30;
    
    -- Ouvrir le curseur
    OPEN user_cursor;
    
    -- Parcourir tous les utilisateurs
    user_loop: LOOP
        FETCH user_cursor INTO current_user_id;
        
        IF done THEN
            LEAVE user_loop;
        END IF;
        
        -- Trouver d'abord un produit valide pour cet utilisateur (pour les logs)
        SELECT MIN(id) INTO first_product_id 
        FROM products 
        WHERE user_id = current_user_id
        LIMIT 1;
        
        -- Vérifier si l'utilisateur a des produits
        IF first_product_id IS NOT NULL THEN
            -- Calcul des totaux pour cet utilisateur
            SELECT 
                COUNT(*) AS nombre_produits,
                COALESCE(SUM(price * @quantite_fixe), 0) AS valeur_totale_catalogue,
                (SELECT COALESCE(SUM(amount), 0) 
                 FROM expenses 
                 WHERE user_id = current_user_id 
                 AND date >= DATE_SUB(CURDATE(), INTERVAL @days_lookback DAY)) AS charges_totales
            INTO nb_produits, valeur_totale, charges_totales
            FROM products 
            WHERE user_id = current_user_id;
            
            -- Log des informations de calcul (avec product_id valide)
            INSERT INTO tracking_stores (
                product_id, last_quantity, transaction, date_transaction, user_id, remarque
            ) VALUES (
                first_product_id, 0, 0, NOW(), current_user_id, 
                CONCAT('Calcul pour User ID: ', current_user_id, 
                       ' - Produits: ', nb_produits,
                       ', Valeur totale: ', valeur_totale,
                       ', Charges: ', charges_totales)
            );
            
            IF valeur_totale > 0 AND nb_produits > 0 THEN
                UPDATE products p
                SET 
                    p.cout_de_revient_unitaire = p.price + 
                        LEAST(
                            @plafond_charges_pct/100 * p.price,
                            ((0.5 * (charges_totales * ((p.price * @quantite_fixe) / valeur_totale))) +
                             (0.5 * (charges_totales / nb_produits))) / @quantite_fixe
                        ),
                    
                    p.prix_min_vente = p.price + 
                        LEAST(
                            @plafond_charges_pct/100 * p.price,
                            ((0.5 * (charges_totales * ((p.price * @quantite_fixe) / valeur_totale))) +
                             (0.5 * (charges_totales / nb_produits))) / @quantite_fixe
                        ),
                    
                    p.prix_vente_conseille = (p.price + 
                        LEAST(
                            @plafond_charges_pct/100 * p.price,
                            ((0.5 * (charges_totales * ((p.price * @quantite_fixe) / valeur_totale))) +
                             (0.5 * (charges_totales / nb_produits))) / @quantite_fixe
                        )) / (1 - (@taux_marge / 100)),
                        
                    p.updated_at = NOW()
                WHERE p.user_id = current_user_id;
                
                -- Utilisation de DELETE + INSERT pour l'historique des prix
                DELETE FROM price_path 
                WHERE date = CURDATE() 
                AND product_id IN (SELECT id FROM products WHERE user_id = current_user_id);
                
                INSERT INTO price_path (product_id, price, date, remarque)
                SELECT 
                    id, 
                    prix_vente_conseille, 
                    CURDATE(), 
                    CONCAT('Calcul automatique - User: ', current_user_id, ', Qté: ', @quantite_fixe)
                FROM products 
                WHERE user_id = current_user_id;
                
                -- Log des résultats
                INSERT INTO tracking_stores (
                    product_id, last_quantity, transaction, date_transaction, user_id, remarque
                ) VALUES (
                    first_product_id, 0, 0, NOW(), current_user_id, 
                    CONCAT('Mise à jour réussie - Charges réparties: ', charges_totales, ' MAD')
                );
            END IF;
        END IF;
    END LOOP;
    
    CLOSE user_cursor;
END



-- ------------------------------------------------------------------------------------------------
-- Table des groupes
-- ------------------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `groupes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL COMMENT 'ID de l\'administrateur du groupe',
  `name` varchar(100) NOT NULL,
  `description` text NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_validite` date NULL COMMENT 'Date de fin de validité du groupe (NULL = illimité)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupe_name_unique` (`name`),
  KEY `idx_groupe_admin` (`id_user`),
  KEY `idx_groupe_name` (`name`),
  KEY `idx_groupe_date_creation` (`date_creation`),
  KEY `idx_groupe_date_validite` (`date_validite`),
  KEY `idx_groupe_admin_name` (`id_user`, `name`),
  CONSTRAINT `groupe_admin_fk` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Table des groupes avec administrateur';

-- ------------------------------------------------------------------------------------------------
-- Table des membres de groupes (relation many-to-many)
-- ------------------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `groupe_membres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_groupe` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `date_ajout` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ajoute_par` int(11) NOT NULL COMMENT 'ID de l\'utilisateur qui a ajouté ce membre',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=actif, 0=inactif',
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupe_user_unique` (`id_groupe`, `id_user`),
  KEY `idx_membre_groupe` (`id_groupe`),
  KEY `idx_membre_user` (`id_user`),
  KEY `idx_membre_ajoute_par` (`ajoute_par`),
  KEY `idx_membre_date_ajout` (`date_ajout`),
  KEY `idx_membre_is_active` (`is_active`),
  KEY `idx_membre_groupe_active` (`id_groupe`, `is_active`),
  KEY `idx_membre_user_active` (`id_user`, `is_active`),
  KEY `idx_membre_groupe_user_active` (`id_groupe`, `id_user`, `is_active`),
  CONSTRAINT `membre_groupe_fk` FOREIGN KEY (`id_groupe`) REFERENCES `groupes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `membre_user_fk` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `membre_ajoute_par_fk` FOREIGN KEY (`ajoute_par`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Table de liaison entre groupes et utilisateurs membres';

-- ------------------------------------------------------------------------------------------------
-- Index composés supplémentaires pour optimiser les requêtes fréquentes
-- ------------------------------------------------------------------------------------------------

-- Index pour rechercher les groupes actifs d'un administrateur
CREATE INDEX idx_groupes_admin_valide ON groupes(id_user, date_validite);

-- Index pour rechercher les groupes par nom et administrateur
CREATE INDEX idx_groupes_search ON groupes(name, id_user, date_creation);

-- Index pour les requêtes de comptage de membres par groupe
CREATE INDEX idx_membres_count_groupe ON groupe_membres(id_groupe, is_active);

-- Index pour les requêtes de recherche de groupes d'un utilisateur
CREATE INDEX idx_membres_user_groupes ON groupe_membres(id_user, is_active, date_ajout);

-- Index pour l'historique d'ajout par administrateur
CREATE INDEX idx_membres_admin_history ON groupe_membres(ajoute_par, date_ajout, is_active);


-- ------------------------------------------------------------------------------------------------
-- Table de partage entre utilisateurs et groupes
-- ------------------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `share_with` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL COMMENT 'Utilisateur qui partage (propriétaire)',
  `id_groupe` int(11) NOT NULL COMMENT 'Groupe avec qui on partage',
  `shared_type` enum('product','cart') NOT NULL COMMENT 'Type d\'objet partagé',
  `shared_id` int(11) NOT NULL COMMENT 'ID de l\'objet partagé (product_id ou cart_id)',
  `permissions` set('view','edit','delete','manage') NOT NULL DEFAULT 'view' COMMENT 'Permissions accordées',
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_expiration` datetime NULL COMMENT 'Date d\'expiration du partage (NULL = illimité)',
  `partage_par` int(11) NOT NULL COMMENT 'Utilisateur ayant effectué le partage (peut être différent du propriétaire)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=actif, 0=inactif',
  `notes` text NULL COMMENT 'Notes sur le partage',
  PRIMARY KEY (`id`),
  UNIQUE KEY `share_unique` (`id_user`, `id_groupe`, `shared_type`, `shared_id`),
  KEY `idx_share_user` (`id_user`),
  KEY `idx_share_groupe` (`id_groupe`),
  KEY `idx_share_type` (`shared_type`),
  KEY `idx_share_object` (`shared_type`, `shared_id`),
  KEY `idx_share_active` (`is_active`),
  KEY `idx_share_expiration` (`date_expiration`),
  KEY `idx_share_creation` (`date_creation`),
  KEY `idx_share_partage_par` (`partage_par`),
  CONSTRAINT `share_user_fk` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `share_groupe_fk` FOREIGN KEY (`id_groupe`) REFERENCES `groupes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `share_partage_par_fk` FOREIGN KEY (`partage_par`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Table de partage de produits et paniers avec des groupes';

-- ------------------------------------------------------------------------------------------------
-- Index composés pour optimiser les requêtes fréquentes
-- ------------------------------------------------------------------------------------------------

-- Index pour rechercher tous les partages d'un utilisateur
CREATE INDEX idx_share_user_active ON share_with(id_user, is_active, date_creation);

-- Index pour rechercher les partages reçus par un groupe
CREATE INDEX idx_share_groupe_active ON share_with(id_groupe, is_active, shared_type);

-- Index pour rechercher les partages d'un type d'objet spécifique
CREATE INDEX idx_share_type_active ON share_with(shared_type, shared_id, is_active);

-- Index pour les permissions et type d'objet
CREATE INDEX idx_share_permissions ON share_with(shared_type, permissions, is_active);

-- Index pour rechercher les partages expirés
CREATE INDEX idx_share_expired ON share_with(date_expiration, is_active);

-- Index pour l'historique des partages par utilisateur
CREATE INDEX idx_share_history ON share_with(partage_par, date_creation, shared_type);

-- Index composite pour requêtes complexes
CREATE INDEX idx_share_user_groupe_type ON share_with(id_user, id_groupe, shared_type, is_active);

-- Index pour rechercher les partages d'un objet spécifique avec permissions
CREATE INDEX idx_share_object_permissions ON share_with(shared_type, shared_id, permissions, is_active);
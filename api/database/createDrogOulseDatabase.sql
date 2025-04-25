-- Création de la base de données
CREATE DATABASE IF NOT EXISTS drogpulseai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Utilisation de la base de données
USE drogpulseai;

-- Table des utilisateurs
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

-- Table des contacts
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

-- Table des produits
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(50) NOT NULL UNIQUE,
  `label` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text,
  `photo_url` varchar(255),
  `barcode` varchar(50),
  `quantity` int(11) DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Création d'un utilisateur de test
INSERT INTO `users` (`nom`, `prenom`, `telephone`, `latitude`, `longitude`, `email`, `password`) VALUES
('Admin', 'System', '0123456789', 48.8566, 2.3522, 'admin@example.com', '$2y$10$UzW0uXBzOcNaUmS8DUQIa.hG8R1nqrhNEzz/lekh2NOL46Lp5QRYe'); -- Mot de passe: admin123

-- Création de contacts de test
INSERT INTO `contacts` (`nom`, `prenom`, `telephone`, `email`, `notes`, `latitude`, `longitude`, `user_id`) VALUES
('Dupont', 'Jean', '0123456789', 'jean.dupont@example.com', 'Contact principal', 48.8566, 2.3522, 1),
('Martin', 'Sophie', '0698765432', 'sophie.martin@example.com', 'Quincaillerie centrale', 48.8534, 2.3488, 1),
('Dubois', 'Pierre', '0634567890', 'pierre.dubois@example.com', 'Fournisseur de matériel', 48.8640, 2.3700, 1);

-- Création de produits de test
INSERT INTO `products` (`reference`, `label`, `name`, `description`, `barcode`, `quantity`, `user_id`) VALUES
('PROD-001', 'Tournevis plat', 'Tournevis plat professionnel', 'Tournevis plat avec manche ergonomique', '123456789011', 50, 1),
('PROD-002', 'Marteau', 'Marteau de charpentier', 'Marteau de charpentier avec manche en bois', '123456789012', 30, 1),
('PROD-003', 'Perceuse', 'Perceuse sans fil 18V', 'Perceuse sans fil professionnelle avec 2 batteries', '123456789013', 15, 1);

-- Création d'index pour optimiser les performances des requêtes
CREATE INDEX idx_contacts_user_id ON contacts(user_id);
CREATE INDEX idx_products_user_id ON products(user_id);
CREATE INDEX idx_contacts_search ON contacts(nom, prenom, telephone);
CREATE INDEX idx_products_search ON products(reference, label, name, barcode);

-- Ajouter le champ price à la table products s'il n'existe pas déjà
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `price` DECIMAL(10,2) DEFAULT 0.00 
COMMENT 'Prix du produit';
<?php
// api_diagnostic.php - Outil de diagnostic pour l'API de produits
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border-radius: 5px; }
        .btn { padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>API Diagnostic - DrogPulseAI</h1>
    
    <div class="section">
        <h2>1. Vérification de la connexion à la base de données</h2>
        <?php
        // Inclure les fichiers de configuration
        require_once 'api/config/database.php';
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            if ($db) {
                echo "<p class='success'>✓ Connexion à la base de données réussie</p>";
                
                // Tester une requête simple
                $query = "SELECT 1";
                $stmt = $db->prepare($query);
                $stmt->execute();
                
                echo "<p class='success'>✓ Requête test exécutée avec succès</p>";
                
                // Récupérer la version de MySQL
                $query = "SELECT VERSION() as version";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    echo "<p>Version MySQL: " . $result['version'] . "</p>";
                }
            } else {
                echo "<p class='error'>✗ Échec de la connexion à la base de données</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Erreur: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>2. Vérification des tables</h2>
        <?php
        try {
            if (isset($db) && $db) {
                // Vérifier la présence de la table 'products'
                $query = "SHOW TABLES LIKE 'products'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    echo "<p class='success'>✓ Table 'products' existe</p>";
                    
                    // Vérifier la structure de la table
                    $query = "DESCRIBE products";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<p>Structure de la table 'products':</p>";
                    echo "<pre>";
                    print_r($columns);
                    echo "</pre>";
                } else {
                    echo "<p class='error'>✗ Table 'products' n'existe pas!</p>";
                    echo "<p>Voici le SQL pour créer la table:</p>";
                    echo "<pre>
CREATE TABLE `products` (
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
);
                    </pre>";
                }
                
                // Vérifier la présence de la table 'users'
                $query = "SHOW TABLES LIKE 'users'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    echo "<p class='success'>✓ Table 'users' existe</p>";
                    
                    // Vérifier s'il y a des utilisateurs
                    $query = "SELECT COUNT(*) AS count FROM users";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result['count'] > 0) {
                        echo "<p class='success'>✓ Il y a " . $result['count'] . " utilisateur(s) dans la base</p>";
                    } else {
                        echo "<p class='warning'>! Aucun utilisateur trouvé dans la table 'users'</p>";
                    }
                } else {
                    echo "<p class='error'>✗ Table 'users' n'existe pas!</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Erreur lors de la vérification des tables: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>3. Test de création d'un produit</h2>
        <?php
        if (isset($_POST['test_create'])) {
            try {
                $product_data = [
                    "reference" => "TEST-" . time(),
                    "label" => "Test produit diagnostic",
                    "name" => "Produit de test diagnostic",
                    "description" => "Description de test depuis l'outil de diagnostic",
                    "quantity" => 5,
                    "userId" => intval($_POST['user_id'])
                ];
                
                // URL de l'API
                $api_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/api/products/create.php";
                
                // Initialisation de cURL
                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($product_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen(json_encode($product_data))
                ]);
                
                // Exécution de la requête
                $response = curl_exec($ch);
                $info = curl_getinfo($ch);
                $error = curl_error($ch);
                curl_close($ch);
                
                echo "<h3>Données envoyées:</h3>";
                echo "<pre>" . json_encode($product_data, JSON_PRETTY_PRINT) . "</pre>";
                
                echo "<h3>Réponse (Code HTTP: " . $info['http_code'] . ")</h3>";
                
                if ($error) {
                    echo "<p class='error'>✗ Erreur cURL: " . $error . "</p>";
                } else {
                    $formatted_response = json_decode($response, true);
                    echo "<pre>" . json_encode($formatted_response, JSON_PRETTY_PRINT) . "</pre>";
                    
                    if (isset($formatted_response['success']) && $formatted_response['success']) {
                        echo "<p class='success'>✓ Produit créé avec succès!</p>";
                    } else {
                        echo "<p class='error'>✗ Échec de la création du produit</p>";
                    }
                }
            } catch (Exception $e) {
                echo "<p class='error'>✗ Exception: " . $e->getMessage() . "</p>";
            }
        } else {
            // Formulaire pour tester la création
            ?>
            <form method="post" action="">
                <p>Choisissez un ID utilisateur existant:</p>
                <select name="user_id">
                    <?php
                    if (isset($db) && $db) {
                        try {
                            $query = "SELECT id, nom, prenom FROM users";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            
                            while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='" . $user['id'] . "'>" . $user['id'] . " - " . $user['prenom'] . " " . $user['nom'] . "</option>";
                            }
                        } catch (Exception $e) {
                            echo "<option value='1'>1 (par défaut)</option>";
                        }
                    } else {
                        echo "<option value='1'>1 (par défaut)</option>";
                    }
                    ?>
                </select>
                <p><button type="submit" name="test_create" class="btn">Tester la création d'un produit</button></p>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>
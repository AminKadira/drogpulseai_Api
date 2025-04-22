<?php
// test_products_api.php - Interface de test pour l'API de produits

// Configuration de base
$api_base_url = "http://192.168.1.15/drogpulseai_Api/api/products/";
$user_id = 1; // ID utilisateur par défaut

// Traitement des actions
$result = null;
$result_code = null;
$action_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Action: Mise à jour de produit
        if ($action === 'update' && isset($_POST['product_id'])) {
            $product_id = intval($_POST['product_id']);
            
            if ($product_id > 0) {
                $product_data = [
                    "id" => $product_id,
                    "reference" => $_POST['reference'],
                    "label" => $_POST['label'],
                    "name" => $_POST['name'],
                    "description" => $_POST['description'],
                    "barcode" => $_POST['barcode'],
                    "quantity" => intval($_POST['quantity']),
                    "userId" => $user_id
                ];
                
                // Si photo_url est fourni
                if (!empty($_POST['photo_url'])) {
                    $product_data['photo_url'] = $_POST['photo_url'];
                }
                
                $ch = curl_init($api_base_url . "update.php");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($product_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                ]);
                
                $result = curl_exec($ch);
                $result_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $action_message = "Mise à jour du produit #" . $product_id;
            }
        }
        
        // Action: Suppression de produit
        else if ($action === 'delete' && isset($_POST['product_id'])) {
            $product_id = intval($_POST['product_id']);
            
            if ($product_id > 0) {
                $ch = curl_init($api_base_url . "delete.php?id=" . $product_id);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                
                $result = curl_exec($ch);
                $result_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $action_message = "Suppression du produit #" . $product_id;
            }
        }
    }
}

// Récupération de la liste des produits pour remplir le formulaire
$ch = curl_init($api_base_url . "list.php?user_id=" . $user_id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$products_json = curl_exec($ch);
curl_close($ch);

$products = json_decode($products_json, true);
if (!is_array($products)) {
    $products = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test de l'API Produits</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        h1, h2 { color: #333; }
        
        .tabs { display: flex; margin-bottom: 20px; }
        .tab-button {
            padding: 10px 20px;
            background: #f0f0f0;
            border: none;
            cursor: pointer;
        }
        .tab-button.active {
            background: #2196F3;
            color: white;
        }
        
        .tab-content {
            display: none;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .tab-content.active { display: block; }
        
        form { margin-bottom: 20px; }
        label { display: block; margin: 10px 0 5px; }
        input, select, textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea { height: 100px; resize: vertical; }
        
        button {
            padding: 10px 15px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover { background: #45a049; }
        button.delete { background: #f44336; }
        button.delete:hover { background: #d32f2f; }
        
        .result {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <h1>Interface de test de l'API Produits</h1>
    
    <div class="tabs">
        <button class="tab-button active" onclick="openTab('update')">Mise à jour de produit</button>
        <button class="tab-button" onclick="openTab('delete')">Suppression de produit</button>
    </div>
    
    <!-- Onglet Mise à jour de produit -->
    <div id="update" class="tab-content active">
        <h2>Mise à jour d'un produit</h2>
        
        <form method="post" action="">
            <input type="hidden" name="action" value="update">
            
            <label for="product_id">Sélectionner un produit :</label>
            <select id="product_id" name="product_id" onchange="loadProductDetails(this.value)">
                <option value="">-- Sélectionner --</option>
                <?php foreach ($products as $product): ?>
                <option value="<?php echo $product['id']; ?>">
                    #<?php echo $product['id']; ?> - <?php echo htmlspecialchars($product['reference']); ?> - <?php echo htmlspecialchars($product['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <div id="product_form" style="display: none;">
                <label for="reference">Référence :</label>
                <input type="text" id="reference" name="reference" required>
                
                <label for="label">Libellé :</label>
                <input type="text" id="label" name="label" required>
                
                <label for="name">Nom :</label>
                <input type="text" id="name" name="name" required>
                
                <label for="description">Description :</label>
                <textarea id="description" name="description"></textarea>
                
                <label for="photo_url">URL Photo :</label>
                <input type="text" id="photo_url" name="photo_url">
                
                <label for="barcode">Code-barres :</label>
                <input type="text" id="barcode" name="barcode">
                
                <label for="quantity">Quantité :</label>
                <input type="number" id="quantity" name="quantity" min="0" value="0">
                
                <button type="submit">Mettre à jour le produit</button>
            </div>
        </form>
    </div>
    
    <!-- Onglet Suppression de produit -->
    <div id="delete" class="tab-content">
        <h2>Suppression d'un produit</h2>
        
        <form method="post" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit?');">
            <input type="hidden" name="action" value="delete">
            
            <label for="delete_product_id">Sélectionner un produit à supprimer :</label>
            <select id="delete_product_id" name="product_id" required>
                <option value="">-- Sélectionner --</option>
                <?php foreach ($products as $product): ?>
                <option value="<?php echo $product['id']; ?>">
                    #<?php echo $product['id']; ?> - <?php echo htmlspecialchars($product['reference']); ?> - <?php echo htmlspecialchars($product['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="delete">Supprimer le produit</button>
        </form>
    </div>
    
    <!-- Affichage des résultats -->
    <?php if ($result !== null): ?>
    <div class="result">
        <h3>Résultat: <?php echo $action_message; ?> (Code HTTP: <?php echo $result_code; ?>)</h3>
        <pre><?php echo json_encode(json_decode($result), JSON_PRETTY_PRINT); ?></pre>
    </div>
    <?php endif; ?>
    
    <script>
        // Fonction pour changer d'onglet
        function openTab(tabName) {
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }
            
            const tabButtons = document.getElementsByClassName('tab-button');
            for (let i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove('active');
            }
            
            document.getElementById(tabName).classList.add('active');
            // Sélectionner le bouton correspondant
            event.currentTarget.classList.add('active');
        }
        
        // Charger les détails du produit pour le formulaire de mise à jour
        function loadProductDetails(productId) {
            const productForm = document.getElementById('product_form');
            
            if (!productId) {
                productForm.style.display = 'none';
                return;
            }
            
            // Trouver le produit dans la liste des produits
            const products = <?php echo $products_json ? $products_json : '[]'; ?>;
            const product = products.find(p => p.id == productId);
            
            if (product) {
                document.getElementById('reference').value = product.reference || '';
                document.getElementById('label').value = product.label || '';
                document.getElementById('name').value = product.name || '';
                document.getElementById('description').value = product.description || '';
                document.getElementById('photo_url').value = product.photo_url || '';
                document.getElementById('barcode').value = product.barcode || '';
                document.getElementById('quantity').value = product.quantity || 0;
                
                productForm.style.display = 'block';
            } else {
                productForm.style.display = 'none';
                alert('Produit non trouvé!');
            }
        }
    </script>
</body>
</html>
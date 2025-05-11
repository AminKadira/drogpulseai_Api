<?php
// api/products/get_product_suppliers.php
// Modifications pour inclure le prix dans les résultats

// [Code existant inchangé jusqu'à la requête SQL]

// Requête pour obtenir les fournisseurs du produit avec le prix
$query = "SELECT ps.id, ps.product_id, ps.contact_id, ps.notes, ps.prix,
                c.nom, c.prenom, c.telephone, c.email
          FROM product_suppliers ps
          JOIN contacts c ON ps.contact_id = c.id
          WHERE ps.product_id = :product_id";

// [Code existant inchangé jusqu'à la boucle de récupération]

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
?>
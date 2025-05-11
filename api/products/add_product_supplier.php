<?php
// api/products/add_product_supplier.php
// Modifications pour gérer le nouveau champ prix

// [Code existant inchangé jusqu'à la vérification de relation]

// Vérifier si la relation existe déjà
$check_query = "SELECT id FROM product_suppliers WHERE product_id = :product_id AND contact_id = :contact_id";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(":product_id", $data->product_id);
$check_stmt->bindParam(":contact_id", $data->contact_id);
$check_stmt->execute();

if ($check_stmt->rowCount() > 0) {
    // La relation existe déjà, mise à jour des notes et du prix
    $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
    $id = $row['id'];
    
    // Préparer la valeur du prix (gérer le cas où il est absent)
    $prix = property_exists($data, 'prix') ? $data->prix : null;
    
    $update_query = "UPDATE product_suppliers SET notes = :notes, prix = :prix, updated_at = NOW() WHERE id = :id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":notes", $data->notes);
    $update_stmt->bindParam(":prix", $prix);
    $update_stmt->bindParam(":id", $id);
    
    if ($update_stmt->execute()) {
        Response::success([
            "id" => $id,
            "product_id" => $data->product_id,
            "contact_id" => $data->contact_id,
            "notes" => $data->notes,
            "prix" => $prix
        ], "Relation produit-fournisseur mise à jour avec succès");
    } else {
        Response::error("Erreur lors de la mise à jour de la relation", 500);
    }
} else {
    // Créer une nouvelle relation
    // Préparer la valeur du prix (gérer le cas où il est absent)
    $prix = property_exists($data, 'prix') ? $data->prix : null;
    
    $insert_query = "INSERT INTO product_suppliers (product_id, contact_id, notes, prix) VALUES (:product_id, :contact_id, :notes, :prix)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bindParam(":product_id", $data->product_id);
    $insert_stmt->bindParam(":contact_id", $data->contact_id);
    $insert_stmt->bindParam(":notes", $data->notes);
    $insert_stmt->bindParam(":prix", $prix);
    
    if ($insert_stmt->execute()) {
        $id = $db->lastInsertId();
        
        Response::success([
            "id" => $id,
            "product_id" => $data->product_id,
            "contact_id" => $data->contact_id,
            "notes" => $data->notes,
            "prix" => $prix
        ], "Relation produit-fournisseur créée avec succès");
    } else {
        Response::error("Erreur lors de la création de la relation", 500);
    }
}
?>
<?php
// test_image_access.php
$image_path = isset($_GET['path']) ? $_GET['path'] : '';

if (empty($image_path)) {
    echo "Aucun chemin d'image spécifié";
    exit;
}

$full_path = realpath($image_path);

echo "<h1>Test d'accès à l'image</h1>";
echo "<p>Chemin demandé: " . htmlspecialchars($image_path) . "</p>";
echo "<p>Chemin complet: " . htmlspecialchars($full_path) . "</p>";

if (file_exists($image_path)) {
    echo "<p style='color: green;'>Le fichier existe!</p>";
    
    // Afficher les infos du fichier
    echo "<p>Taille: " . filesize($image_path) . " octets</p>";
    echo "<p>Type MIME: " . mime_content_type($image_path) . "</p>";
    
    // Essayer d'afficher l'image
    echo "<h2>Test d'affichage:</h2>";
    echo "<img src='/" . $image_path . "' style='max-width: 300px; border: 1px solid #ccc;'>";
    echo "<p>Si l'image n'apparaît pas ci-dessus, c'est un problème d'accès web.</p>";
} else {
    echo "<p style='color: red;'>Le fichier n'existe pas!</p>";
}
?>

<form method="get">
    <input type="text" name="path" value="<?php echo htmlspecialchars($image_path); ?>" placeholder="Chemin de l'image" style="width: 300px;">
    <button type="submit">Vérifier</button>
</form>
<?php
// test_create_api.php
header('Content-Type: text/plain');

// URL de l'API
$api_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/products/create.php';

// Données de test
$data = [
    'reference' => 'TEST-' . time(),
    'label' => 'Produit test',
    'name' => 'Nom du produit test',
    'description' => 'Description du test',
    'quantity' => 10,
    'userId' => 1
];

// Initialisation de cURL
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($data))
]);

// Exécution et récupération de la réponse
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Affichage des résultats
echo "=== REQUÊTE ENVOYÉE ===\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

echo "=== STATUT HTTP ===\n";
echo $status . "\n\n";

echo "=== ERREUR CURL ===\n";
echo ($curl_error ? $curl_error : "Aucune") . "\n\n";

echo "=== RÉPONSE BRUTE ===\n";
echo $response . "\n\n";

echo "=== ANALYSE JSON ===\n";
$json_response = json_decode($response, true);
if ($json_response === null) {
    echo "ERREUR: La réponse n'est pas un JSON valide.\n";
    echo "Erreur JSON: " . json_last_error_msg() . "\n";
} else {
    echo "JSON valide reçu:\n";
    echo json_encode($json_response, JSON_PRETTY_PRINT) . "\n";
}
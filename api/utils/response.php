<?php
// utils/response.php - Version corrigée
class Response {
    // Envoyer une réponse de succès
    public static function success($data = null, $message = "Opération réussie") {
        self::sendResponse(true, $message, $data, 200);
    }
    
    // Envoyer une réponse d'erreur
    public static function error($message = "Une erreur est survenue", $status_code = 400) {
        self::sendResponse(false, $message, null, $status_code);
    }
    
    // Méthode centrale pour envoyer une réponse JSON
    private static function sendResponse($success, $message, $data = null, $status_code = 200) {
        // S'assurer qu'aucune sortie n'a déjà été envoyée
        if (headers_sent()) {
            // Si les headers ont déjà été envoyés, on ne peut pas les modifier
            // On écrit juste notre réponse JSON mais ça pourrait causer d'autres problèmes
            $response = json_encode([
                'success' => false,
                'message' => 'Headers déjà envoyés, impossible de définir le code HTTP'
            ]);
            echo $response;
            exit;
        }
        
        // Définir le content-type et le code HTTP
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($status_code);
        
        // Construire la réponse
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        // Ajouter les données si fournies
        if ($data !== null) {
            if (is_array($data)) {
                // Si c'est un tableau, on peut le fusionner
                // mais on évite d'écraser success ou message
                foreach ($data as $key => $value) {
                    if ($key !== 'success' && $key !== 'message') {
                        $response[$key] = $value;
                    }
                }
            } elseif (is_string($data)) {
                // Si c'est une chaîne, on peut la traiter comme un message
                $response['details'] = $data;
            }
        }
        
        // Envoyer la réponse JSON
        echo json_encode($response);
        exit;
    }
    
    // Fonction simple pour envoyer un tableau JSON
    public static function json($data) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }
}
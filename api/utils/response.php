<?php
// Utilitaire pour les réponses API standardisées

class Response {
    // Envoie une réponse de succès avec les données
    public static function success($data = null, $message = "Opération réussie") {
        header('Content-Type: application/json');
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            if (is_string($data)) {
                $response['message'] = $data;
            } else {
                $response = array_merge($response, $data);
            }
        }
        
        echo json_encode($response);
        exit;
    }
    
    // Envoie une réponse d'erreur
    public static function error($message = "Une erreur est survenue", $status_code = 400) {
        // Définir le code d'état HTTP
        http_response_code($status_code);
        
        header('Content-Type: application/json');
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        echo json_encode($response);
        exit;
    }
    
    // Envoie une liste d'objets au format JSON
    public static function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
?>
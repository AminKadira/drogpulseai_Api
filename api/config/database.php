<?php
// Configuration de la base de données

class Database {
    // Paramètres de connexion à la base de données
    private $host = "127.0.0.1";
    private $db_name = "drogpulseai";
    private $username = "root";
    private $password = ""; // Mot de passe par défaut pour WampServer
    public $conn;
    
    // Méthode pour se connecter à la base de données
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Erreur de connexion : " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>
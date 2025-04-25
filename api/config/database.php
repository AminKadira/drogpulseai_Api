<?php
// Configuration de la base de données
class Database {
    // Paramètres de connexion à la base de données
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;
   
    public function __construct() {
        // Charger les variables d'environnement depuis le fichier .env
        $this->loadEnv();
       
        // Récupérer les informations de connexion depuis les variables d'environnement
        $this->host = getenv('DB_HOST') ?: '127.0.0.1';
        $this->db_name = getenv('DB_NAME') ?: 'drogpulseai';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';
        
        // Journaliser les informations pour le débogage (sans le mot de passe)
        error_log("Tentative de connexion à la BDD: host=" . $this->host . ", db=" . $this->db_name . ", user=" . $this->username);
    }
   
    // Méthode pour charger les variables d'environnement depuis .env
    private function loadEnv() {
        $envFile = dirname(__FILE__, 2) . '/.env';
        
        error_log("Recherche du fichier .env: " . $envFile);
        
        if (file_exists($envFile)) {
            error_log(".env trouvé, chargement des variables");
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Ignorer les commentaires
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Vérifier que la ligne contient un signe =
                if (strpos($line, '=') === false) {
                    error_log("Ligne .env ignorée (pas de signe =): " . $line);
                    continue;
                }
                
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                if (!empty($name)) {
                    putenv("$name=$value");
                    $_ENV[$name] = $value;
                    error_log("Variable chargée: $name");
                }
            }
        } else {
            error_log("ATTENTION: Fichier .env non trouvé à " . $envFile);
        }
    }
   
    // Méthode pour se connecter à la base de données
    public function getConnection() {
        $this->conn = null;

        try {
            // Vérifier les informations de connexion
            if (empty($this->host) || empty($this->db_name) || empty($this->username)) {
                throw new Exception("Informations de connexion à la base de données incomplètes");
            }
            
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name;
            error_log("Tentative de connexion avec DSN: " . $dsn);
            
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            error_log("Connexion à la base de données réussie");
            
            return $this->conn;
        } catch(PDOException $exception) {
            // Journaliser l'erreur mais ne pas l'afficher
            error_log("Erreur PDO: " . $exception->getMessage());
            // Propager l'erreur
            throw $exception;
        } catch(Exception $exception) {
            // Journaliser l'erreur mais ne pas l'afficher
            error_log("Erreur: " . $exception->getMessage());
            // Propager l'erreur
            throw $exception;
        }
    }
}
?>
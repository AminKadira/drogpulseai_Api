
<?php
// api/utils/ImageHandler.php
class ImageHandler {
    private $upload_dir;
    private $allowed_types;
    private $max_size;
    
    public function __construct($upload_dir = 'uploads/products/') {
        $this->upload_dir = '../' . $upload_dir;
        $this->allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $this->max_size = 5 * 1024 * 1024; // 5 Mo
        
        // Créer le répertoire s'il n'existe pas
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }
    
    public function upload($file, $user_id) {
        // Vérification du type MIME
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $file['tmp_name']);
        finfo_close($file_info);
        
        if (!in_array($mime_type, $this->allowed_types)) {
            throw new Exception("Type de fichier non autorisé. Seuls JPEG, PNG et GIF sont acceptés.");
        }
        
        // Vérification de la taille
        if ($file['size'] > $this->max_size) {
            throw new Exception("Le fichier est trop volumineux (max 5 Mo)");
        }
        
        // Générer un nom unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('product_') . '_' . $user_id . '.' . $extension;
        $target_path = $this->upload_dir . $file_name;
        
        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            return 'uploads/products/' . $file_name;
        }
        
        throw new Exception("Erreur lors de l'upload du fichier");
    }
    
    public function delete($path) {
        $full_path = '../' . $path;
        if (file_exists($full_path)) {
            return unlink($full_path);
        }
        return false;
    }
}
?>
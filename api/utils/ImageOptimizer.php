
<?php
// Fichier: api/utils/ImageOptimizer.php

/**
 * Classe pour l'optimisation des images
 */
class ImageOptimizer {
    // Constantes de configuration
    private $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    private $max_file_size = 5 * 1024 * 1024; // 5 Mo
    private $default_quality = 85;
    private $max_width = 1920;
    private $max_height = 1920;
    
    /**
     * Vérifie si le fichier est une image valide
     */
    public function isValidImage($file_path) {
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $file_path);
        finfo_close($file_info);
        
        return in_array($mime_type, $this->allowed_types);
    }
    
    /**
     * Retourne la taille maximale de fichier autorisée
     */
    public function getMaxFileSize() {
        return $this->max_file_size;
    }
    
    /**
     * Optimise et sauvegarde une image
     */
    public function optimizeAndSave($source_path, $target_path, $quality = null) {
        if ($quality === null) {
            $quality = $this->default_quality;
        }
        
        // Obtenir les informations sur l'image source
        list($width, $height, $type) = getimagesize($source_path);
        
        // Déterminer si l'image doit être redimensionnée
        $ratio = 1;
        if ($width > $this->max_width || $height > $this->max_height) {
            $ratio = min($this->max_width / $width, $this->max_height / $height);
        }
        
        $new_width = round($width * $ratio);
        $new_height = round($height * $ratio);
        
        // Créer l'image source
        $source_image = $this->createImageFromFile($source_path, $type);
        if (!$source_image) {
            return false;
        }
        
        // Créer l'image destination
        $target_image = imagecreatetruecolor($new_width, $new_height);
        
        // Préserver la transparence pour les PNG
        if ($type === IMAGETYPE_PNG) {
            imagecolortransparent($target_image, imagecolorallocate($target_image, 0, 0, 0));
            imagealphablending($target_image, false);
            imagesavealpha($target_image, true);
        }
        
        // Redimensionner
        imagecopyresampled(
            $target_image, $source_image,
            0, 0, 0, 0,
            $new_width, $new_height, $width, $height
        );
        
        // Sauvegarder l'image (toujours en JPEG pour l'uniformité)
        $result = imagejpeg($target_image, $target_path, $quality);
        
        // Libérer la mémoire
        imagedestroy($source_image);
        imagedestroy($target_image);
        
        return $result;
    }
    
    /**
     * Redimensionne une image à des dimensions spécifiques
     */
    public function resize($source_path, $target_path, $width, $height, $quality = null) {
        if ($quality === null) {
            $quality = $this->default_quality;
        }
        
        // Obtenir les informations sur l'image source
        list($src_width, $src_height, $type) = getimagesize($source_path);
        
        // Créer l'image source
        $source_image = $this->createImageFromFile($source_path, $type);
        if (!$source_image) {
            return false;
        }
        
        // Créer l'image destination
        $target_image = imagecreatetruecolor($width, $height);
        
        // Préserver la transparence pour les PNG
        if ($type === IMAGETYPE_PNG) {
            imagecolortransparent($target_image, imagecolorallocate($target_image, 0, 0, 0));
            imagealphablending($target_image, false);
            imagesavealpha($target_image, true);
        }
        
        // Redimensionner avec recadrage pour maintenir le ratio (center-crop)
        $src_ratio = $src_width / $src_height;
        $dst_ratio = $width / $height;
        
        if ($src_ratio > $dst_ratio) {
            // L'image source est plus large
            $crop_width = round($src_height * $dst_ratio);
            $crop_height = $src_height;
            $crop_x = round(($src_width - $crop_width) / 2);
            $crop_y = 0;
        } else {
            // L'image source est plus haute
            $crop_width = $src_width;
            $crop_height = round($src_width / $dst_ratio);
            $crop_x = 0;
            $crop_y = round(($src_height - $crop_height) / 2);
        }
        
        // Effectuer le recadrage et le redimensionnement
        imagecopyresampled(
            $target_image, $source_image,
            0, 0, $crop_x, $crop_y,
            $width, $height, $crop_width, $crop_height
        );
        
        // Sauvegarder l'image
        $result = imagejpeg($target_image, $target_path, $quality);
        
        // Libérer la mémoire
        imagedestroy($source_image);
        imagedestroy($target_image);
        
        return $result;
    }
    
    /**
     * Crée une ressource image GD à partir d'un fichier
     */
    private function createImageFromFile($path, $type) {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            default:
                return false;
        }
    }
}
?>
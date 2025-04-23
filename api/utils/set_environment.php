<?php

//*********** Usage: php utils/set_environment.php prod


// utils/set_environment.php
$env = $argv[1] ?? 'dev';  // Par défaut, utiliser l'environnement de développement
$envFile = ".env.$env";
$targetFile = ".env";

if (!file_exists($envFile)) {
    die("Le fichier d'environnement '$envFile' n'existe pas.\n");
}

if (copy($envFile, $targetFile)) {
    echo "Environnement configuré pour: $env\n";
} else {
    die("Impossible de copier le fichier d'environnement.\n");
}
?>
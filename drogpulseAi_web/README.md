# DrogPulseAI - Catalogue Produits MVC

Version optimisée avec architecture MVC, gestion d'erreurs avancée et requêtes optimisées.

## 🏗️ Architecture MVC

```
drogpluseAI_web/products_catalogue/
├── bootstrap.php                 # Configuration et autoloader
├── index.php                    # Point d'entrée catalogue  
├── product.php                  # Point d'entrée détails produit
├── controllers/
│   ├── CatalogueController.php  # Controller catalogue
│   └── ProductController.php    # Controller détails produit
├── services/
│   └── ProductService.php       # Logique métier centralisée
├── views/
│   ├── catalogue.phtml          # Vue principale catalogue
│   ├── error.phtml             # Vue d'erreur centralisée
│   └── partials/
│       └── product_details_content.phtml # Vue AJAX détails
├── utils/
│   └── ProductUtilities.php     # Utilitaires et exceptions
└── config/
    └── config.php              # Configuration centralisée
```

## ✅ Corrections apportées

### 1. **Architecture MVC respectée**
- **Controllers** : Gestion des requêtes et validation
- **Services** : Logique métier centralisée
- **Views** : Présentation séparée
- **Utilitaires** : Code réutilisable centralisé

### 2. **Gestion d'erreurs avancée**
- **Exceptions typées** : `ValidationException`, `DatabaseException`
- **Logging automatique** des erreurs critiques
- **Messages d'erreur sécurisés** (pas d'exposition technique)
- **Mode debug** configurable

### 3. **Requêtes optimisées**
- **JOINs uniques** au lieu de requêtes multiples
- **Paramètres liés** avec types appropriés
- **Pagination efficace** avec LIMIT/OFFSET
- **Requêtes préparées** sécurisées

### 4. **Code DRY (Don't Repeat Yourself)**
- **Trait ProductUtilities** pour méthodes communes
- **Service centralisé** pour logique métier
- **Configuration unifiée**
- **Gestion d'erreurs centralisée**

## 🚀 Installation

### 1. Structure des fichiers
```bash
# Copier les fichiers dans la structure MVC
drogpluseAI_web/products_catalogue/
├── Tous les fichiers fournis
```

### 2. Configuration base de données
```php
// La classe Database existante est réutilisée
// Aucune modification nécessaire dans /api/config/database.php
```

### 3. Configuration environnement
```php
// config/config.php - Modifier selon environnement
'app' => [
    'environment' => 'production', // ou 'development'
    'debug' => false               // true en développement
]
```

## 🎯 Fonctionnalités

### Catalogue
- **Pagination** optimisée (12 produits par page)
- **Filtres avancés** : recherche, prix, stock
- **Tri dynamique** par tous les champs
- **Responsive design** Bootstrap 5
- **Chargement AJAX** des détails

### Détails produit
- **Galerie d'images** avec navigation tactile
- **Informations complètes** : prix, stock, fournisseurs
- **Mode AJAX et page complète**
- **Détection automatique** du type de requête

### Sécurité
- **Validation stricte** des paramètres
- **Échappement HTML** systématique
- **Requêtes préparées** PDO
- **Gestion d'erreurs** sécurisée

## 🔧 Configuration avancée

### Mode debug
```php
// config/config.php
'app' => [
    'debug' => true  // Affiche détails erreurs
]
```

### Cache (préparé pour évolution)
```php
'cache' => [
    'enabled' => false,  // Activer si nécessaire
    'ttl' => 300
]
```

### Performance
```php
'performance' => [
    'compression_enabled' => true,
    'lazy_loading' => true
]
```

## 📱 Utilisation

### Accès catalogue
```
http://votre-domaine/drogpluseAI_web/products_catalogue/index.php
```

### Détails produit
```
# Page complète
http://votre-domaine/product.php?id=123

# AJAX (automatique depuis le catalogue)
Headers: X-Requested-With: XMLHttpRequest
```

### Filtres URL
```
# Recherche
?search=vis&min_price=10&max_price=100&stock=in_stock

# Tri
?sort=prix_vente_conseille&dir=desc

# Pagination
?page=2
```

## 🛠️ Développement

### Ajouter nouvelle fonctionnalité
1. **Controller** : Ajouter méthode dans controller approprié
2. **Service** : Implémenter logique dans ProductService
3. **Vue** : Créer template dans views/
4. **Route** : Modifier point d'entrée si nécessaire

### Debugging
```php
// Mode développement dans config.php
'app' => ['debug' => true]

// Logs automatiques dans /var/logs/app.log
error_log("Debug info: " . print_r($data, true));
```

### Tests de charge
```bash
# Test pagination
for i in {1..10}; do curl "http://domain/index.php?page=$i"; done

# Test recherche
curl "http://domain/index.php?search=test&min_price=10"
```

## 🔍 Monitoring

### Logs d'erreurs
```bash
# Vérifier logs
tail -f var/logs/app.log

# Erreurs critiques
grep "Fatal error" var/logs/app.log
```

### Performance
```sql
-- Requêtes lentes potentielles
SHOW PROCESSLIST;

-- Index manquants
EXPLAIN SELECT * FROM products WHERE name LIKE '%test%';
```

## 📈 Évolutions possibles

### Features prêtes
```php
// config/config.php - Features flags
'features' => [
    'advanced_search' => false,  // Recherche avancée
    'favorites' => false,        // Système favoris
    'cart' => false,            // Panier
    'user_accounts' => false,   // Comptes utilisateurs
    'analytics' => false        // Analytics
]
```

### Cache Redis
```php
// Préparé pour intégration cache
'cache' => [
    'enabled' => true,
    'driver' => 'redis'
]
```

## 🆘 Dépannage

### Erreur 500
1. Vérifier logs : `var/logs/app.log`
2. Activer debug : `'debug' => true`
3. Vérifier permissions fichiers
4. Tester connexion DB

### Performance lente
1. Vérifier index DB
2. Activer cache si disponible
3. Optimiser images
4. Réduire items par page

### Erreurs AJAX
1. Vérifier headers `X-Requested-With`
2. Tester URL directement
3. Vérifier console JS navigateur

---

**Version:** 2.0.0  
**Compatibilité:** PHP 8.0+, MySQL 5.7+  
**Framework:** Bootstrap 5.3.2
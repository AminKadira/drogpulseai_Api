# Documentation Web DrogPulseAI

Ce dépôt contient une documentation technique et fonctionnelle du projet DrogPulseAI sous forme de site web statique. La documentation couvre les trois couches de l'application :
- Application Android (frontend)
- API PHP (backend)
- Base de données MySQL (stockage)

## Contenu du projet

- `index.html` - Page principale de la documentation
- `styles.css` - Styles CSS pour la mise en page et l'apparence
- `script.js` - Fonctionnalités interactives JavaScript

## Fonctionnalités

- Design responsive s'adaptant à tous les appareils (desktop, tablette, mobile)
- Navigation interactive avec menu latéral et sous-menus
- Mode sombre automatique selon les préférences du système
- Recherche dans la documentation
- Possibilité de copier les blocs de code
- Optimisé pour l'impression

## Déploiement

### Option 1 : Hébergement statique simple

Puisque la documentation est composée uniquement de fichiers HTML, CSS et JavaScript statiques, vous pouvez la déployer sur n'importe quel hébergement web :

1. Téléchargez les fichiers sur votre hébergeur via FTP ou un autre protocole de transfert
2. Assurez-vous que `index.html` est configuré comme page d'accueil

### Option 2 : GitHub Pages

Pour un déploiement rapide et gratuit :

1. Créez un nouveau dépôt GitHub
2. Uploadez les fichiers de documentation
3. Activez GitHub Pages dans les paramètres du dépôt
4. Sélectionnez la branche main comme source

### Option 3 : Serveur local (démo)

Pour une démo rapide en local :

1. Installez un serveur web léger comme [http-server](https://www.npmjs.com/package/http-server) :
   ```
   npm install -g http-server
   ```

2. Naviguez vers le dossier contenant les fichiers de documentation et exécutez :
   ```
   http-server
   ```

3. Accédez à `http://localhost:8080` dans votre navigateur

### Option 4 : Google Drive

Pour partager facilement la documentation via Google Drive :

1. Créez un nouveau dossier dans Google Drive
2. Uploadez les fichiers (index.html, styles.css, script.js)
3. Utilisez une application tierce comme [DriveToWeb](https://www.drv.tw/) pour servir le contenu HTML

## Personnalisation

### Modification des couleurs

Les couleurs principales sont définies dans des variables CSS au début du fichier `styles.css`. Pour changer le thème de couleur, modifiez ces variables :

```css
:root {
    --primary: #2196F3;
    --primary-dark: #1976D2;
    --primary-light: #BBDEFB;
    --accent: #FFC107;
    /* Autres variables... */
}
```

### Ajout de nouvelles sections

Pour ajouter une nouvelle section à la documentation :

1. Ajoutez un nouveau lien dans le menu de navigation dans `index.html` :
   ```html
   <li><a href="#nouvelle-section"><i class="fas fa-icon"></i> Nouvelle Section</a></li>
   ```

2. Créez la section correspondante dans le contenu principal :
   ```html
   <section id="nouvelle-section" class="section">
       <h2><i class="fas fa-icon"></i> Nouvelle Section</h2>
       <div class="section-content">
           <!-- Contenu de la section -->
       </div>
   </section>
   ```

## Maintenance

Pour mettre à jour la documentation :

1. Modifiez les fichiers HTML, CSS ou JavaScript selon vos besoins
2. Testez les changements localement
3. Redéployez les fichiers mis à jour

## Compatibilité navigateurs

La documentation est compatible avec les navigateurs modernes :
- Chrome (dernières versions)
- Firefox (dernières versions)
- Safari (dernières versions)
- Edge (dernières versions)

## Licence

Cette documentation est fournie sous licence MIT. Voir le fichier LICENSE pour plus de détails.
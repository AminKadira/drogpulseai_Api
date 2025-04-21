// script.js - Documentation DrogPulseAI

document.addEventListener('DOMContentLoaded', function() {
    // Gestion du menu de navigation
    setupNavigation();
    
    // Ajout du bouton pour le menu mobile
    setupMobileMenu();
    
    // Surlignage de la syntaxe dans les blocs de code
    highlightCodeBlocks();
    
    // Gestion du défilement et des ancres
    setupScrolling();
});

// Configuration de la navigation
function setupNavigation() {
    // Gestion des sous-menus
    const menuItems = document.querySelectorAll('.nav-links > li');
    
    menuItems.forEach(item => {
        const link = item.querySelector('a');
        const submenu = item.querySelector('.submenu');
        
        if (submenu) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Ferme tous les autres sous-menus
                menuItems.forEach(other => {
                    if (other !== item && other.classList.contains('active')) {
                        other.classList.remove('active');
                    }
                });
                
                // Ouvre/ferme le sous-menu actuel
                item.classList.toggle('active');
                
                // Scroll jusqu'à la section correspondante
                const targetId = link.getAttribute('href').substring(1);
                const targetSection = document.getElementById(targetId);
                
                if (targetSection) {
                    scrollToSection(targetSection);
                }
            });
            
            // Gestion des liens dans le sous-menu
            const submenuLinks = submenu.querySelectorAll('a');
            submenuLinks.forEach(sublink => {
                sublink.addEventListener('click', function(e) {
                    e.stopPropagation(); // Évite de déclencher l'événement du parent
                    
                    // Marque le parent comme actif
                    item.classList.add('active');
                    
                    // Retire la classe active de tous les liens
                    document.querySelectorAll('.nav-links a').forEach(a => {
                        a.classList.remove('active');
                    });
                    
                    // Ajoute la classe active au lien cliqué
                    sublink.classList.add('active');
                });
            });
        } else {
            link.addEventListener('click', function() {
                // Retire la classe active de tous les liens
                document.querySelectorAll('.nav-links a').forEach(a => {
                    a.classList.remove('active');
                });
                
                // Ajoute la classe active au lien cliqué
                link.classList.add('active');
            });
        }
    });
    
    // Gestion du clic sur les liens de navigation
    const navLinks = document.querySelectorAll('.nav-links a');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href.startsWith('#')) {
                e.preventDefault();
                const targetSection = document.getElementById(href.substring(1));
                
                if (targetSection) {
                    scrollToSection(targetSection);
                    
                    // Ferme le menu mobile si ouvert
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar.classList.contains('mobile-visible')) {
                        sidebar.classList.remove('mobile-visible');
                    }
                }
            }
        });
    });
}

// Configuration du menu mobile
function setupMobileMenu() {
    // Créer le bouton de menu mobile
    const mobileMenuButton = document.createElement('button');
    mobileMenuButton.className = 'mobile-menu-toggle';
    mobileMenuButton.innerHTML = '<i class="fas fa-bars"></i>';
    document.body.appendChild(mobileMenuButton);
    
    // Comportement du bouton
    mobileMenuButton.addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('mobile-visible');
        
        // Changer l'icône du bouton
        const icon = this.querySelector('i');
        if (sidebar.classList.contains('mobile-visible')) {
            icon.className = 'fas fa-times';
        } else {
            icon.className = 'fas fa-bars';
        }
    });
    
    // Fermer le menu si on clique en dehors
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const mobileMenuButton = document.querySelector('.mobile-menu-toggle');
        
        if (sidebar.classList.contains('mobile-visible') && 
            !sidebar.contains(e.target) && 
            e.target !== mobileMenuButton && 
            !mobileMenuButton.contains(e.target)) {
            sidebar.classList.remove('mobile-visible');
            mobileMenuButton.querySelector('i').className = 'fas fa-bars';
        }
    });
}

// Fonction pour faire défiler la page jusqu'à une section
function scrollToSection(section) {
    const headerOffset = 20;
    const elementPosition = section.getBoundingClientRect().top;
    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
    
    window.scrollTo({
        top: offsetPosition,
        behavior: 'smooth'
    });
}

// Gestion du défilement
function setupScrolling() {
    // Détecter la section visible et mettre à jour la navigation
    window.addEventListener('scroll', debounce(function() {
        const sections = document.querySelectorAll('.section');
        let currentSectionId = '';
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            
            if (window.pageYOffset >= sectionTop - 200) {
                currentSectionId = section.getAttribute('id');
            }
        });
        
        // Mettre à jour la navigation
        if (currentSectionId) {
            document.querySelectorAll('.nav-links a').forEach(link => {
                link.classList.remove('active');
                
                if (link.getAttribute('href') === '#' + currentSectionId) {
                    link.classList.add('active');
                    
                    // Si le lien est dans un sous-menu, ouvrir le parent
                    const parentLi = link.closest('li.active');
                    if (!parentLi && link.closest('.submenu')) {
                        const parentLink = link.closest('li').parentNode.closest('li');
                        if (parentLink) {
                            parentLink.classList.add('active');
                        }
                    }
                }
            });
        }
    }, 100));
}

// Fonction debounce pour améliorer les performances
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

// Mise en valeur du code
function highlightCodeBlocks() {
    // Cette fonction est simplifiée car nous n'utilisons pas de bibliothèque
    // de coloration syntaxique comme highlight.js
    // Dans une implémentation réelle, on pourrait intégrer une telle bibliothèque

    // Pour l'instant, on ajoute juste une petite animation
    const codeBlocks = document.querySelectorAll('.code-block');
    
    codeBlocks.forEach(block => {
        block.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
            this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.15)';
        });
        
        block.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
            this.style.boxShadow = '';
        });
    });
}

// Initialisation de la navigation par défaut
window.onload = function() {
    // Activer le premier élément du menu par défaut
    const firstNavLink = document.querySelector('.nav-links a');
    if (firstNavLink) {
        firstNavLink.classList.add('active');
    }
    
    // Gérer les liens d'ancrage dans l'URL
    if (window.location.hash) {
        const targetId = window.location.hash.substring(1);
        const targetSection = document.getElementById(targetId);
        const targetLink = document.querySelector(`.nav-links a[href="#${targetId}"]`);
        
        if (targetSection && targetLink) {
            // Désactiver tous les liens actifs
            document.querySelectorAll('.nav-links a').forEach(link => {
                link.classList.remove('active');
            });
            
            // Activer le lien correspondant
            targetLink.classList.add('active');
            
            // Si le lien est dans un sous-menu, ouvrir le parent
            const parentSubmenu = targetLink.closest('.submenu');
            if (parentSubmenu) {
                const parentLi = parentSubmenu.closest('li');
                if (parentLi) {
                    parentLi.classList.add('active');
                }
            }
            
            // Défiler jusqu'à la section
            setTimeout(() => {
                scrollToSection(targetSection);
            }, 300);
        }
    }
    
    // Ajouter les événements de clic pour les cartes
    setupCardInteractions();
    
    // Changer le titre de la page en fonction de la section visible
    updateDocumentTitle();
};

// Gestion des interactions avec les cartes
function setupCardInteractions() {
    const cards = document.querySelectorAll('.feature-card, .screen-card, .security-card, .performance-card, .evolution-card');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
            this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
        
        // Animation de pulsation au clic
        card.addEventListener('click', function() {
            this.classList.add('pulse');
            setTimeout(() => {
                this.classList.remove('pulse');
            }, 300);
        });
    });
}

// Mise à jour du titre du document en fonction de la section visible
function updateDocumentTitle() {
    window.addEventListener('scroll', debounce(function() {
        const sections = document.querySelectorAll('.section');
        let currentSectionId = '';
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            
            if (window.pageYOffset >= sectionTop - 200) {
                currentSectionId = section.getAttribute('id');
            }
        });
        
        // Mettre à jour le titre du document
        if (currentSectionId) {
            const sectionTitle = document.querySelector(`#${currentSectionId} h2`).textContent;
            document.title = `${sectionTitle} | Documentation DrogPulseAI`;
        } else {
            document.title = 'Documentation DrogPulseAI';
        }
    }, 300));
}

// Fonction pour copier le code
function setupCodeCopyButtons() {
    const codeBlocks = document.querySelectorAll('.code-block');
    
    codeBlocks.forEach((block, index) => {
        // Créer le bouton de copie
        const copyButton = document.createElement('button');
        copyButton.className = 'copy-button';
        copyButton.innerHTML = '<i class="fas fa-copy"></i>';
        copyButton.title = 'Copier le code';
        
        // Ajouter le bouton au bloc de code
        block.style.position = 'relative';
        block.appendChild(copyButton);
        
        // Gérer le clic sur le bouton
        copyButton.addEventListener('click', function() {
            const code = block.textContent;
            
            // Copier le code dans le presse-papier
            navigator.clipboard.writeText(code).then(() => {
                // Changer temporairement l'icône pour confirmer
                this.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-copy"></i>';
                }, 2000);
            }).catch(err => {
                console.error('Erreur lors de la copie :', err);
            });
        });
    });
}

// Appel de la fonction pour ajouter les boutons de copie
document.addEventListener('DOMContentLoaded', function() {
    setupCodeCopyButtons();
    
    // Ajouter une animation de pulse à tous les boutons
    const buttons = document.querySelectorAll('button:not(.copy-button), .btn');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            this.classList.add('pulse');
            setTimeout(() => {
                this.classList.remove('pulse');
            }, 300);
        });
    });
    
    // Détecter le mode sombre du système et ajuster le thème
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.body.classList.add('dark-mode');
    }
    
    // Écouter les changements de préférence de thème
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (e.matches) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    });
    
    // Initialiser la recherche
    initSearch();
});

// Fonction pour initialiser la recherche
function initSearch() {
    // Créer le champ de recherche dans l'en-tête
    const header = document.querySelector('header .header-content');
    const searchContainer = document.createElement('div');
    searchContainer.className = 'search-container';
    
    searchContainer.innerHTML = `
        <div class="search-box">
            <input type="text" id="search-input" placeholder="Rechercher dans la documentation...">
            <button id="search-button"><i class="fas fa-search"></i></button>
        </div>
        <div id="search-results" class="search-results"></div>
    `;
    
    header.appendChild(searchContainer);
    
    // Gérer la recherche
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    
    searchInput.addEventListener('input', debounce(function() {
        const query = this.value.toLowerCase();
        
        if (query.length < 3) {
            searchResults.innerHTML = '';
            searchResults.style.display = 'none';
            return;
        }
        
        // Rechercher dans les sections
        const sections = document.querySelectorAll('.section');
        const results = [];
        
        sections.forEach(section => {
            const sectionTitle = section.querySelector('h2').textContent;
            const sectionId = section.getAttribute('id');
            const sectionContent = section.textContent;
            
            if (sectionTitle.toLowerCase().includes(query) || sectionContent.toLowerCase().includes(query)) {
                results.push({
                    id: sectionId,
                    title: sectionTitle,
                    preview: getTextPreview(sectionContent, query)
                });
            }
        });
        
        // Afficher les résultats
        if (results.length > 0) {
            searchResults.innerHTML = '';
            results.forEach(result => {
                const resultItem = document.createElement('div');
                resultItem.className = 'search-result-item';
                resultItem.innerHTML = `
                    <h4><a href="#${result.id}">${result.title}</a></h4>
                    <p>${result.preview}</p>
                `;
                searchResults.appendChild(resultItem);
                
                // Ajouter un événement de clic
                resultItem.addEventListener('click', function() {
                    const targetSection = document.getElementById(result.id);
                    if (targetSection) {
                        scrollToSection(targetSection);
                        searchResults.style.display = 'none';
                    }
                });
            });
            searchResults.style.display = 'block';
        } else {
            searchResults.innerHTML = '<div class="no-results">Aucun résultat trouvé</div>';
            searchResults.style.display = 'block';
        }
    }, 300));
    
    // Fermer les résultats si on clique en dehors
    document.addEventListener('click', function(e) {
        if (!searchContainer.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
}

// Fonction pour obtenir un aperçu du texte contenant le terme recherché
function getTextPreview(text, query) {
    const maxLength = 100;
    const lowerText = text.toLowerCase();
    const index = lowerText.indexOf(query);
    
    if (index === -1) return text.substring(0, maxLength) + '...';
    
    const start = Math.max(0, index - 40);
    const end = Math.min(text.length, index + query.length + 40);
    let preview = text.substring(start, end);
    
    if (start > 0) preview = '...' + preview;
    if (end < text.length) preview = preview + '...';
    
    // Mettre en évidence le terme recherché
    const regex = new RegExp(query, 'gi');
    preview = preview.replace(regex, match => `<strong>${match}</strong>`);
    
    return preview;
}
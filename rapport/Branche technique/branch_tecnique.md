# Branche technique

Dans cette section, nous présentons la branche technique de notre projet, qui consiste à décrire les choix technologiques, les outils et les architectures adoptés pour assurer le bon fonctionnement de l'application, tout en garantissant une solution performante, sécurisée et adaptée aux besoins identifiés.

---

## Choix technologiques

Dans le cadre de ce projet, plusieurs technologies modernes et complémentaires ont été sélectionnées pour garantir la performance, la maintenabilité, la sécurité et la rapidité de la plateforme de gestion de copropriété immobilière.

### Technologies Backend

- **PHP 8.2+ et Laravel 12.0** : Ce duo technologique est utilisé pour développer le backend en architecture MVC, garantissant une base sécurisée, des performances accrues et un code structuré.
- **Spatie Laravel Permission 7.4** : Bibliothèque pour la gestion professionnelle des rôles et des autorisations, garantissant un cloisonnement strict des accès.
- **NativePHP** : Framework pour transformer l'application Laravel en application mobile (Android, iOS) pour les résidents.

### Technologies Frontend

- **Blade Templates** : Moteur de rendu HTML, natif de Laravel, utilisé pour structurer l'interface utilisateur en composants réutilisables.
- **Tailwind CSS** : Framework CSS utilitaire ultra-rapide, permettant de concevoir une interface sur mesure, moderne, responsive et compatible mode sombre.
- **Alpine** : Framework JavaScript léger et réactif, utilisé pour les animations du frontend (barres de recherche, modales, menus déroulants).
- **Preline** : Bibliothèque de composants UI basés sur Tailwind CSS, accélérant le développement avec des modèles prêts à l'emploi.
- **Vite** : Outil de build moderne, optimisant la compilation des ressources CSS/JS pour un chargement de page ultra-rapide en production.

### Base de données

- **MySQL** : Système de gestion de base de données relationnelle (SGBDR) fiable, utilisé pour stocker l'intégralité des données de l'application.

### Outils et utilitaires externes

- **Mermaid** : Outil permettant de générer des schémas et diagrammes à partir d'une syntaxe textuelle simple.
- **PlantUML** : Solution utilisée pour concevoir les différents diagrammes UML nécessaires à l'analyse et à la documentation du système.
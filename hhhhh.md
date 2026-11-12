Crée une application web complète de gestion pour un « Service de Location de Voitures » (nommée AutoLoc) basée sur une architecture N-tiers légère sans framework : Vanilla PHP (POO) / DAO JSON / Tailwind CSS / Vanilla JS, exécutable directement avec la commande `php -S localhost:8000`.

### 1. Architecture Technique Imposée
Organise le projet selon les 6 couches distinctes suivantes :
- `index.html` : Interface SPA moderne (Tailwind CDN, barre latérale, dashboard, modales masquables, conteneur de notifications Toast).
- `assets/js/app.js` : Logique client asynchrone (appels Fetch REST, gestion des états Loading, filtres en direct, calcul automatique des tarifs, DOM dynamique).
- `backend/classes/` : Modèles POPO purs (propriétés strictement privées et typées, constructeurs, getters, implémentation de JsonSerializable).
- `backend/dao/` : Couche d'accès aux données (lecture/écriture dans des fichiers JSON, auto-incrémentation des IDs, validation des champs).
- `backend/data/` : Fichiers JSON de persistance initialisés avec des données de test réalistes.
- `backend/services/` : Couche métier héritant d'un `BaseService` abstrait, orchestrant les DAOs, les jointures et renvoyant des codes HTTP standardisés (200, 201, 400, 404, 500).
- `backend/api/api.php` : Point d'entrée HTTP unique routant les requêtes selon le paramètre `resource` (stats, categories, vehicules, locataires, contrats).

### 2. Modélisation des 4 Entités
Implémente exactement les 4 tables suivantes avec leurs attributs typés :

1. `CategorieVehicule` (Classe de classification) :
   - `id`: int
   - `nom`: string (ex: Économique, Berline, SUV, Utilitaire)
   - `description`: string
   - `tarifBase`: float (tarif indicatif journalier en DH)
   - `image`: string (URL de l'illustration de la catégorie)

2. `Vehicule` (Classe principale) :
   - `id`: int
   - `immatriculation`: string (unique)
   - `marque`: string (ex: Dacia, Renault, Volkswagen)
   - `modele`: string (ex: Sandero, Clio, Golf)
   - `categorieId`: int (Clé étrangère liée à CategorieVehicule)
   - `prixJour`: float (en DH)
   - `statut`: string ('Disponible', 'Loué', 'En maintenance')
   - `image`: string (URL de la photo du véhicule)

3. `Locataire` (Table Client) :
   - `id`: int
   - `cin`: string (unique)
   - `nom`: string
   - `prenom`: string
   - `telephone`: string
   - `email`: string
   - `permis`: string (numéro de permis de conduire)

4. `ContratLocation` (Table Pivot / Transaction) :
   - `id`: int
   - `locataireId`: int (Clé étrangère liée à Locataire)
   - `vehiculeId`: int (Clé étrangère liée à Vehicule)
   - `dateDebut`: string (format YYYY-MM-DD)
   - `dateFin`: string (format YYYY-MM-DD)
   - `prixTotal`: float (en DH)
   - `statut`: string ('En cours', 'Terminé')

### 3. Règles Métier & Jointures Applicatives
- Jointure Catégorie : Dans `VehiculeService`, joindre `CategorieDAO` pour inclure automatiquement le libellé de la catégorie (`categorieNom`).
- Jointure Contrat : Dans `ContratService`, joindre `LocataireDAO` et `VehiculeDAO` pour enrichir les contrats avec le nom du client, son CIN, la marque/modèle du véhicule, l'immatriculation et la durée en jours.
- Cycle de vie de la location :
  * À la création d'un contrat, vérifier que le véhicule est 'Disponible', calculer automatiquement le montant total (`durée en jours × prixJour`), puis basculer automatiquement le statut du véhicule à 'Loué'.
  * À la clôture d'un contrat (`action=cloturer`), passer le contrat à 'Terminé' et remettre immédiatement le véhicule à 'Disponible'.
- Intégrité référentielle : Interdire la suppression d'un locataire ou d'un véhicule ayant un contrat en cours.

### 4. Interface Utilisateur & Intégration Visuelle
- Dashboard : Cartes de statistiques en temps réel (flotte totale, véhicules disponibles, locations en cours, chiffre d'affaires cumulé) et tableau des contrats récents.
- Section Catégories avec Photos : Chaque carte de catégorie doit afficher sa bannière visuelle ET la liste complète des véhicules qui lui sont rattachés avec leurs photos, prix, immatriculation et badge de disponibilité, ainsi qu'un bouton rapide d'ajout direct.
- Section Véhicules : Tableau complet avec vignette photo, recherche multi-critères en direct et filtres par catégorie et par statut.
- Section Locataires & Contrats : CRUD complet, recherche par nom/CIN et modale de réservation avec calcul automatique du devis en direct lors de la sélection des dates.
- Système de notifications Toast animées pour chaque action (succès/erreur).
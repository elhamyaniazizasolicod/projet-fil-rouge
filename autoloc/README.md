# AutoLoc — Application de gestion de location de voitures

AutoLoc est une application SPA de gestion de flotte automobile réalisée sans framework PHP. Les données sont persistées dans des fichiers JSON.

## 1. Prérequis

- PHP 8.1 ou supérieur (le projet est compatible avec PHP 8.4/8.5).
- Un navigateur récent.
- Une connexion Internet pour charger Tailwind CSS depuis son CDN et afficher les photos distantes de démonstration.

Aucune base de données, aucun Composer et aucune dépendance PHP externe ne sont nécessaires.

## 2. Démarrage

Ouvrez un terminal dans le dossier `autoloc` (le dossier qui contient `index.html`), puis lancez :

```bash
php -S localhost:8000
```

Ouvrez ensuite cette adresse dans le navigateur :

```text
http://localhost:8000
```

Important : lancez le serveur depuis la racine du projet, pas depuis le dossier `backend`.

## 3. Architecture

```text
autoloc/
├── index.html                         # Interface SPA, Tailwind CDN et modales
├── assets/
│   └── js/
│       └── app.js                     # Fetch, rendu DOM, filtres, devis et Toast
├── backend/
│   ├── bootstrap.php                  # Autoload PHP léger, sans Composer
│   ├── api/
│   │   └── api.php                    # Point d'entrée HTTP unique
│   ├── classes/                       # Modèles POPO typés et JsonSerializable
│   │   ├── CategorieVehicule.php
│   │   ├── Vehicule.php
│   │   ├── Locataire.php
│   │   ├── ContratLocation.php
│   │   └── HttpException.php
│   ├── dao/                            # Persistance et validation JSON
│   │   ├── BaseJsonDAO.php
│   │   ├── CategorieDAO.php
│   │   ├── VehiculeDAO.php
│   │   ├── LocataireDAO.php
│   │   └── ContratDAO.php
│   ├── data/                           # Données initiales modifiables
│   │   ├── categories.json
│   │   ├── vehicules.json
│   │   ├── locataires.json
│   │   └── contrats.json
│   └── services/                       # Règles métier et jointures
│       ├── BaseService.php
│       ├── StatsService.php
│       ├── CategorieService.php
│       ├── VehiculeService.php
│       ├── LocataireService.php
│       └── ContratService.php
└── README.md
```

### Responsabilités des couches

1. **Interface (`index.html`)** : navigation SPA, tableaux, cartes, modales et notifications.
2. **Client (`assets/js/app.js`)** : appels Fetch asynchrones, filtres, chargements et calcul du devis.
3. **Modèles (`backend/classes`)** : quatre entités métier avec propriétés privées typées, getters et `JsonSerializable`.
4. **DAO (`backend/dao`)** : lecture/écriture JSON, validation, identifiants auto-incrémentés et verrouillage des fichiers.
5. **Données (`backend/data`)** : fichiers JSON de démonstration.
6. **Services/API (`backend/services`, `backend/api`)** : règles métier, jointures et réponses HTTP standardisées.

## 4. Fonctionnalités incluses

- Tableau de bord : flotte, véhicules disponibles, contrats en cours et chiffre d'affaires cumulé.
- Catégories : bannière photo, description, tarif indicatif et liste des véhicules rattachés.
- Véhicules : CRUD, recherche multi-critères et filtres catégorie/statut.
- Locataires : CRUD et recherche par nom, prénom, CIN, téléphone ou e-mail.
- Contrats : création, consultation, modification d'un contrat en cours, clôture et suppression d'un contrat terminé.
- Calcul automatique du devis : nombre de jours × prix journalier.
- À la création d'un contrat, le véhicule passe automatiquement à `Loué`.
- À la clôture, le contrat passe à `Terminé` et le véhicule redevient `Disponible`.
- Suppression bloquée pour un véhicule ou un locataire lié à un contrat en cours.
- Contrôle d'unicité du CIN et de l'immatriculation.
- Notifications Toast et indicateurs de chargement.

## 5. API REST

Toutes les routes passent par `backend/api/api.php` et le paramètre `resource`.

| Méthode | URL | Action |
|---|---|---|
| GET | `?resource=stats` | Statistiques du tableau de bord |
| GET / POST | `?resource=categories` | Lister / créer |
| PUT / DELETE | `?resource=categories&id=1` | Modifier / supprimer |
| GET / POST | `?resource=vehicules` | Lister / créer |
| PUT / DELETE | `?resource=vehicules&id=1` | Modifier / supprimer |
| GET / POST | `?resource=locataires` | Lister / créer |
| PUT / DELETE | `?resource=locataires&id=1` | Modifier / supprimer |
| GET / POST | `?resource=contrats` | Lister / créer une réservation |
| PUT / DELETE | `?resource=contrats&id=1` | Modifier / supprimer (contrat terminé) |
| POST | `?resource=contrats&id=1&action=cloturer` | Clôturer un contrat |

Les réponses JSON contiennent `success`, `message` et `data`. Les codes HTTP utilisés incluent 200, 201, 400, 404, 405, 409 et 500.

### Exemple de création d'une réservation

```bash
curl -X POST "http://localhost:8000/backend/api/api.php?resource=contrats" \
  -H "Content-Type: application/json" \
  -d '{"locataireId":1,"vehiculeId":1,"dateDebut":"2026-11-20","dateFin":"2026-11-23"}'
```

Le montant est calculé côté serveur ; il n'est pas nécessaire de transmettre `prixTotal`.

## 6. Persistance et réinitialisation

Les changements sont enregistrés directement dans les fichiers de `backend/data/`. Pour réinitialiser la démonstration, remplacez ces quatre fichiers par leurs versions originales depuis une copie propre du projet.

Les fichiers JSON doivent rester accessibles en écriture par le processus PHP.

## 7. Vérification rapide

Depuis la racine du projet, sous Windows PowerShell :

```powershell
Get-ChildItem .\backend -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

Sous Linux/macOS :

```bash
find backend -name "*.php" -exec php -l {} \;
```

Puis vérifiez les routes dans le navigateur ou avec `curl`, par exemple :

```text
http://localhost:8000/backend/api/api.php?resource=stats
http://localhost:8000/backend/api/api.php?resource=categories
http://localhost:8000/backend/api/api.php?resource=vehicules
http://localhost:8000/backend/api/api.php?resource=locataires
http://localhost:8000/backend/api/api.php?resource=contrats
```

## 8. Note de périmètre

Il s'agit d'une base pédagogique locale. Elle ne contient pas de système d'authentification, de gestion des rôles, de protection CSRF ni de paiement en ligne. Pour une mise en production, il faudra notamment ajouter ces mécanismes, une stratégie de sauvegarde et une base de données transactionnelle.

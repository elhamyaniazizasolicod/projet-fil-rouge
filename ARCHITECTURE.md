# Architecture Applicative - Service de Location de Voitures (AutoLoc)

> **Projet Fil Rouge - SoliCode**  
> Architecture N-tiers légère : **Vanilla PHP (POO) / DAO JSON / Tailwind CSS / Vanilla JS**

---

## 1. Cadrage Métier et Entités

L'application gère un parc de location de véhicules composé de 4 entités interconnectées :

1. **`CategorieVehicule`** *(Classe de classification)* : segmentation de la flotte (Économique, Berline, SUV, Utilitaire) avec tarif indicatif journalier.
2. **`Vehicule`** *(Classe principale)* : informations détaillées d'un véhicule du parc (immatriculation, marque, modèle, tarif/jour, statut de disponibilité).
3. **`Locataire`** *(Client)* : informations du client locataire (CIN/Passeport, nom, prénom, téléphone, email, permis de conduire).
4. **`ContratLocation`** *(Transaction / Pivot relationnel)* : associe un locataire et un véhicule sur une période (date début, date fin, montant total calculé, statut).

### Schéma Relationnel & Cardinalités

```
+------------------------------------+          1:N         +------------------------------------+
|         CategorieVehicule          |--------------------->|              Vehicule              |
|------------------------------------|                      |------------------------------------|
| - id: int                          |                      | - id: int                          |
| - nom: string                      |                      | - immatriculation: string (unique) |
| - description: string              |                      | - marque: string                   |
| - tarifBase: float                 |                      | - modele: string                   |
| - image: string                    |                      | - categorieId: int (FK)            |
+------------------------------------+                      | - prixJour: float                  |
                                                            | - statut: string                   |
                                                            | - image: string                    |
                                                            +------------------------------------+
                                                                              |
                                                                              | 1:N
                                                                              v
+------------------------------------+          1:N         +------------------------------------+
|             Locataire              |--------------------->|          ContratLocation           |
|------------------------------------|                      |------------------------------------|
| - id: int                          |                      | - id: int                          |
| - cin: string (unique)             |                      | - locataireId: int (FK)            |
| - nom: string                      |                      | - vehiculeId: int (FK)             |
| - prenom: string                   |                      | - dateDebut: string (YYYY-MM-DD)   |
| - telephone: string                |                      | - dateFin: string (YYYY-MM-DD)     |
| - email: string                    |                      | - prixTotal: float                 |
| - permis: string                   |                      | - statut: string                   |
+------------------------------------+                      +------------------------------------+
```

- Une **Catégorie** regroupe **plusieurs Véhicules** (`1:N`).
- Un **Locataire** peut conclure **plusieurs Contrats de location** au fil du temps (`1:N`).
- Un **Véhicule** peut faire l'objet de **plusieurs Contrats successifs** (`1:N`).

---

## 2. Arborescence du Projet

```
projet_fil_rouge/
├── index.html                  # Interface SPA moderne (Tailwind CDN, Dashboard, Modals, Toasts)
├── assets/
│   └── js/
│       └── app.js              # Logique asynchrone frontend (Fetch, Filtres, Modals, Toasts, DOM)
├── backend/
│   ├── api/
│   │   └── api.php             # Routeur REST unifié (GET, POST, DELETE, CORS, JSON)
│   ├── classes/                # Entités POPO typées (propriétés privées, getters, JsonSerializable)
│   │   ├── CategorieVehicule.php
│   │   ├── Vehicule.php
│   │   ├── Locataire.php
│   │   └── ContratLocation.php
│   ├── dao/                    # Couche d'accès aux données (lecture/écriture JSON, ID auto, validation)
│   │   ├── CategorieDAO.php
│   │   ├── VehiculeDAO.php
│   │   ├── LocataireDAO.php
│   │   └── ContratDAO.php
│   ├── data/                   # Fichiers de persistance JSON
│   │   ├── categories.json
│   │   ├── vehicules.json
│   │   ├── locataires.json
│   │   └── contrats.json
│   └── services/               # Couche métier et orchestration des jointures
│       ├── BaseService.php     # Classe abstraite pour standardiser les réponses API
│       ├── CategorieService.php
│       ├── VehiculeService.php
│       ├── LocataireService.php
│       └── ContratService.php
└── ARCHITECTURE.md
```

---

## 3. Rôles et Responsabilités des Couches

### 1. Couche Modèle (`backend/classes/`)
- Objets purs PHP (**POPO** - Plain Old PHP Objects).
- Propriétés privées et strictement typées (`int`, `string`, `float`).
- Constructeurs avec affectation directe.
- Getters pour l'encapsulation.
- Implémentation de `JsonSerializable` pour sérialiser facilement les entités en tableaux/JSON.
- **Règle absolue** : aucune logique de fichier ni de base de données dans ces classes.

### 2. Couche DAO (`backend/dao/`)
- Encapsule la lecture et l'écriture dans les fichiers JSON correspondants via `__DIR__ . "/../data/[fichier].json"`.
- Fournit les méthodes CRUD :
  - `AfficherTous()` : lit le fichier, parse le JSON, instancie les classes et renvoie la liste.
  - `TrouverParId($id)` : recherche un objet spécifique par son identifiant unique.
  - `ajouter(...)` : valide les données, génère l'identifiant incrémental `max(id) + 1`, enregistre et persiste dans le fichier.
  - `supprimer($id)` : filtre et réécrit le fichier.
  - `mettreAJourStatut($id, $statut)` : met à jour l'état de l'entité.

### 3. Couche Service (`backend/services/`)
- Hérite de `BaseService` pour harmoniser les codes HTTP (`200`, `201`, `400`, `404`, `500`) et les réponses JSON `{"success": bool, "message": string, "data": mixed}`.
- **Jointures applicatives** :
  - `VehiculeService` joint `CategorieDAO` pour associer automatiquement `categorieNom` à chaque véhicule.
  - `ContratService` joint `LocataireDAO` et `VehiculeDAO` pour enrichir les contrats avec le nom du client, son CIN, le modèle du véhicule et calculer la durée en jours.
- **Règles métier clés** :
  - Lors de la création d'un contrat, le statut du véhicule passe automatiquement à `"Loué"`.
  - Lors de la clôture d'un contrat, le contrat passe à `"Terminé"` et le véhicule redevient immédiatement `"Disponible"`.
  - Interdiction de supprimer un locataire ou un véhicule ayant un contrat en cours.

### 4. Couche API (`backend/api/api.php`)
- Routeur unifié accessible via le paramètre HTTP `resource` :
  - `GET /backend/api/api.php?resource=stats` : compteurs du tableau de bord.
  - `GET/POST/DELETE /backend/api/api.php?resource=vehicules`
  - `GET/POST/DELETE /backend/api/api.php?resource=categories`
  - `GET/POST/DELETE /backend/api/api.php?resource=locataires`
  - `GET/POST/DELETE /backend/api/api.php?resource=contrats`
  - `POST /backend/api/api.php?resource=contrats&action=cloturer&id={id}` : clôture de location.

### 5. Couche Frontend SPA (`index.html` & `assets/js/app.js`)
- Design épuré avec Tailwind CSS (police moderne Plus Jakarta Sans, icons Font Awesome 6).
- Navigation par onglets (Dashboard, Flotte Véhicules, Catégories, Locataires, Contrats) sans rechargement de page.
- Calcul en temps réel dans le modal de contrat :
  $$\text{Montant Total} = \text{Durée (jours)} \times \text{Prix journalier du véhicule}$$
- Système de notifications Toast animées et gestion des états de chargement (`Loading`).

---

## 4. Démarrage et Test

Le projet ne nécessite aucune installation complexe ni Composer :

```powershell
# Depuis la racine du projet E:\projet_fil_rouge :
php -S localhost:8000
```

Ouvrez ensuite votre navigateur sur :  
👉 **`http://localhost:8000`**


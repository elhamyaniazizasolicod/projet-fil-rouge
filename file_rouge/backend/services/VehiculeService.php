<?php

require_once __DIR__ . "/BaseService.php";
require_once __DIR__ . "/../dao/VehiculeDAO.php";
require_once __DIR__ . "/../dao/CategorieDAO.php";
require_once __DIR__ . "/../dao/ContratDAO.php";

class VehiculeService extends BaseService {
    public function lister(): void {
        try {
            $vehicules = VehiculeDAO::AfficherTous();
            $categories = CategorieDAO::AfficherTous();

            // Indexer les catégories par ID pour optimiser la jointure
            $catMap = [];
            foreach ($categories as $cat) {
                $catMap[$cat->getId()] = $cat->getNom();
            }

            $resultat = [];
            foreach ($vehicules as $v) {
                $item = $v->jsonSerialize();
                $item["categorieNom"] = $catMap[$v->getCategorieId()] ?? "Non spécifiée";
                $resultat[] = $item;
            }

            $this->sendSuccess("Véhicules récupérés avec succès", $resultat);
        } catch (Throwable $e) {
            $this->sendError("Erreur lors de la récupération des véhicules : " . $e->getMessage());
        }
    }

    public function ajouter(): void {
        try {
            $input = $this->getJsonInput();
            $immatriculation = trim($input["immatriculation"] ?? "");
            $marque = trim($input["marque"] ?? "");
            $modele = trim($input["modele"] ?? "");
            $categorieId = (int)($input["categorieId"] ?? 0);
            $prixJour = (float)($input["prixJour"] ?? 0);
            $statut = trim($input["statut"] ?? "Disponible");
            $image = trim($input["image"] ?? "");

            // Valider que la catégorie existe
            $categorie = CategorieDAO::TrouverParId($categorieId);
            if (!$categorie) {
                $this->sendError("La catégorie sélectionnée n'existe pas.", 400);
                return;
            }

            $nouveau = VehiculeDAO::ajouter(
                $immatriculation,
                $marque,
                $modele,
                $categorieId,
                $prixJour,
                $statut,
                $image
            );

            $this->sendSuccess("Véhicule ajouté avec succès", $nouveau->jsonSerialize(), 201);
        } catch (Throwable $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function supprimer(int $id): void {
        try {
            // Vérifier si le véhicule est lié à un contrat en cours
            $contrats = ContratDAO::AfficherTous();
            foreach ($contrats as $c) {
                if ($c->getVehiculeId() === $id && $c->getStatut() === "En cours") {
                    $this->sendError("Impossible de supprimer ce véhicule : un contrat de location est actuellement en cours.", 400);
                    return;
                }
            }

            $reussi = VehiculeDAO::supprimer($id);
            if ($reussi) {
                $this->sendSuccess("Véhicule supprimé avec succès");
            } else {
                $this->sendError("Véhicule introuvable", 404);
            }
        } catch (Throwable $e) {
            $this->sendError("Erreur lors de la suppression : " . $e->getMessage());
        }
    }
}

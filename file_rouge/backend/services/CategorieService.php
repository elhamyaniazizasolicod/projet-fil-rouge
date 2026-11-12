<?php

require_once __DIR__ . "/BaseService.php";
require_once __DIR__ . "/../dao/CategorieDAO.php";

class CategorieService extends BaseService {
    public function lister(): void {
        try {
            $categories = CategorieDAO::AfficherTous();
            $data = array_map(fn($c) => $c->jsonSerialize(), $categories);
            $this->sendSuccess("Catégories récupérées avec succès", $data);
        } catch (Throwable $e) {
            $this->sendError("Erreur lors de la récupération des catégories : " . $e->getMessage());
        }
    }

    public function ajouter(): void {
        try {
            $input = $this->getJsonInput();
            $nom = trim($input["nom"] ?? "");
            $description = trim($input["description"] ?? "");
            $tarifBase = (float)($input["tarifBase"] ?? 0);
            $image = trim($input["image"] ?? "");

            $nouvelle = CategorieDAO::ajouter($nom, $description, $tarifBase, $image);
            $this->sendSuccess("Catégorie ajoutée avec succès", $nouvelle->jsonSerialize(), 201);
        } catch (Throwable $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function supprimer(int $id): void {
        try {
            $reussi = CategorieDAO::supprimer($id);
            if ($reussi) {
                $this->sendSuccess("Catégorie supprimée avec succès");
            } else {
                $this->sendError("Catégorie introuvable", 404);
            }
        } catch (Throwable $e) {
            $this->sendError("Erreur lors de la suppression : " . $e->getMessage());
        }
    }
}

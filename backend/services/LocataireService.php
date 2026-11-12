<?php

require_once __DIR__ . "/BaseService.php";
require_once __DIR__ . "/../dao/LocataireDAO.php";
require_once __DIR__ . "/../dao/ContratDAO.php";

class LocataireService extends BaseService {
    public function lister(): void {
        try {
            $locataires = LocataireDAO::AfficherTous();
            $data = array_map(fn($l) => $l->jsonSerialize(), $locataires);
            $this->sendSuccess("Locataires récupérés avec succès", $data);
        } catch (Throwable $e) {
            $this->sendError("Erreur lors de la récupération des locataires : " . $e->getMessage());
        }
    }

    public function ajouter(): void {
        try {
            $input = $this->getJsonInput();
            $cin = trim($input["cin"] ?? "");
            $nom = trim($input["nom"] ?? "");
            $prenom = trim($input["prenom"] ?? "");
            $telephone = trim($input["telephone"] ?? "");
            $email = trim($input["email"] ?? "");
            $permis = trim($input["permis"] ?? "");

            $nouveau = LocataireDAO::ajouter($cin, $nom, $prenom, $telephone, $email, $permis);
            $this->sendSuccess("Locataire ajouté avec succès", $nouveau->jsonSerialize(), 201);
        } catch (Throwable $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function supprimer(int $id): void {
        try {
            // Vérifier si le locataire a des contrats en cours
            $contrats = ContratDAO::AfficherTous();
            foreach ($contrats as $c) {
                if ($c->getLocataireId() === $id && $c->getStatut() === "En cours") {
                    $this->sendError("Impossible de supprimer ce locataire : un contrat de location est actuellement en cours.", 400);
                    return;
                }
            }

            $reussi = LocataireDAO::supprimer($id);
            if ($reussi) {
                $this->sendSuccess("Locataire supprimé avec succès");
            } else {
                $this->sendError("Locataire introuvable", 404);
            }
        } catch (Throwable $e) {
            $this->sendError("Erreur lors de la suppression : " . $e->getMessage());
        }
    }
}


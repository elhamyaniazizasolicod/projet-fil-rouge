<?php

require_once __DIR__ . "/BaseService.php";
require_once __DIR__ . "/../dao/ContratDAO.php";
require_once __DIR__ . "/../dao/LocataireDAO.php";
require_once __DIR__ . "/../dao/VehiculeDAO.php";

class ContratService extends BaseService {
    public function lister(): void {
        try {
            $contrats = ContratDAO::AfficherTous();
            $locataires = LocataireDAO::AfficherTous();
            $vehicules = VehiculeDAO::AfficherTous();

            // Maps d'indexation
            $locMap = [];
            foreach ($locataires as $l) {
                $locMap[$l->getId()] = $l;
            }

            $vehMap = [];
            foreach ($vehicules as $v) {
                $vehMap[$v->getId()] = $v;
            }

            $resultat = [];
            foreach ($contrats as $c) {
                $item = $c->jsonSerialize();

                // Jointure Locataire
                $loc = $locMap[$c->getLocataireId()] ?? null;
                $item["locataireNom"] = $loc ? $loc->getNomComplet() : "Inconnu";
                $item["locataireCin"] = $loc ? $loc->getCin() : "N/A";
                $item["locataireTelephone"] = $loc ? $loc->getTelephone() : "N/A";

                // Jointure Véhicule
                $veh = $vehMap[$c->getVehiculeId()] ?? null;
                $item["vehiculeLibelle"] = $veh ? ($veh->getMarque() . " " . $veh->getModele()) : "Inconnu";
                $item["vehiculeImmatriculation"] = $veh ? $veh->getImmatriculation() : "N/A";
                $item["vehiculePrixJour"] = $veh ? $veh->getPrixJour() : 0;

                // Calcul durée en jours
                $tsDebut = strtotime($c->getDateDebut());
                $tsFin = strtotime($c->getDateFin());
                $duree = max(1, (int)round(($tsFin - $tsDebut) / (60 * 60 * 24)));
                $item["dureeJours"] = $duree;

                $resultat[] = $item;
            }

            $this->sendSuccess("Contrats récupérés avec succès", $resultat);
        } catch (Throwable $e) {
            $this->sendError("Erreur lors de la récupération des contrats : " . $e->getMessage());
        }
    }

    public function ajouter(): void {
        try {
            $input = $this->getJsonInput();
            $locataireId = (int)($input["locataireId"] ?? 0);
            $vehiculeId = (int)($input["vehiculeId"] ?? 0);
            $dateDebut = trim($input["dateDebut"] ?? "");
            $dateFin = trim($input["dateFin"] ?? "");

            // Vérifier existence du locataire
            $locataire = LocataireDAO::TrouverParId($locataireId);
            if (!$locataire) {
                $this->sendError("Le locataire sélectionné n'existe pas.", 400);
                return;
            }

            // Vérifier existence du véhicule
            $vehicule = VehiculeDAO::TrouverParId($vehiculeId);
            if (!$vehicule) {
                $this->sendError("Le véhicule sélectionné n'existe pas.", 400);
                return;
            }

            if ($vehicule->getStatut() !== "Disponible") {
                $this->sendError("Ce véhicule n'est pas disponible pour une nouvelle location (Statut: " . $vehicule->getStatut() . ").", 400);
                return;
            }

            // Calculer montant automatique
            $tsDebut = strtotime($dateDebut);
            $tsFin = strtotime($dateFin);
            if (!$tsDebut || !$tsFin || $tsFin < $tsDebut) {
                $this->sendError("Les dates saisies sont invalides (la fin doit être postérieure ou égale au début).", 400);
                return;
            }

            $dureeJours = max(1, (int)round(($tsFin - $tsDebut) / (60 * 60 * 24)));
            $prixTotal = (float)($input["prixTotal"] ?? ($dureeJours * $vehicule->getPrixJour()));

            $nouveauContrat = ContratDAO::ajouter(
                $locataireId,
                $vehiculeId,
                $dateDebut,
                $dateFin,
                $prixTotal,
                "En cours"
            );

            // Mettre à jour le statut du véhicule en "Loué"
            VehiculeDAO::mettreAJourStatut($vehiculeId, "Loué");

            $this->sendSuccess("Contrat de location créé avec succès", $nouveauContrat->jsonSerialize(), 201);
        } catch (Throwable $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function cloturer(int $id): void {
        try {
            $contrat = ContratDAO::TrouverParId($id);
            if (!$contrat) {
                $this->sendError("Contrat introuvable", 404);
                return;
            }

            // Marquer le contrat comme terminé
            ContratDAO::mettreAJourStatut($id, "Terminé");

            // Libérer le véhicule
            VehiculeDAO::mettreAJourStatut($contrat->getVehiculeId(), "Disponible");

            $this->sendSuccess("Contrat clôturé avec succès et véhicule libéré");
        } catch (Throwable $e) {
            $this->sendError("Erreur lors de la clôture du contrat : " . $e->getMessage());
        }
    }

    public function supprimer(int $id): void {
        try {
            $contrat = ContratDAO::TrouverParId($id);
            if (!$contrat) {
                $this->sendError("Contrat introuvable", 404);
                return;
            }

            // Si le contrat était en cours, libérer le véhicule
            if ($contrat->getStatut() === "En cours") {
                VehiculeDAO::mettreAJourStatut($contrat->getVehiculeId(), "Disponible");
            }

            ContratDAO::supprimer($id);
            $this->sendSuccess("Contrat supprimé avec succès");
        } catch (Throwable $e) {
            $this->sendError("Erreur lors de la suppression : " . $e->getMessage());
        }
    }

    public function stats(): void {
        try {
            $vehicules = VehiculeDAO::AfficherTous();
            $locataires = LocataireDAO::AfficherTous();
            $contrats = ContratDAO::AfficherTous();

            $totalVehicules = count($vehicules);
            $disponibles = 0;
            $loues = 0;
            $maintenance = 0;

            foreach ($vehicules as $v) {
                if ($v->getStatut() === "Disponible") $disponibles++;
                elseif ($v->getStatut() === "Loué") $loues++;
                else $maintenance++;
            }

            $contratsEnCours = 0;
            $chiffreAffaires = 0.0;
            foreach ($contrats as $c) {
                if ($c->getStatut() === "En cours") {
                    $contratsEnCours++;
                }
                $chiffreAffaires += $c->getPrixTotal();
            }

            $data = [
                "totalVehicules" => $totalVehicules,
                "vehiculesDisponibles" => $disponibles,
                "vehiculesLoues" => $loues,
                "vehiculesMaintenance" => $maintenance,
                "totalLocataires" => count($locataires),
                "contratsEnCours" => $contratsEnCours,
                "totalContrats" => count($contrats),
                "chiffreAffaires" => $chiffreAffaires
            ];

            $this->sendSuccess("Statistiques calculées avec succès", $data);
        } catch (Throwable $e) {
            $this->sendError("Erreur lors du calcul des statistiques : " . $e->getMessage());
        }
    }
}


<?php

require_once __DIR__ . "/../classes/ContratLocation.php";

class ContratDAO {
    private static string $dataFile = __DIR__ . "/../data/contrats.json";

    public static function AfficherTous(): array {
        if (!file_exists(self::$dataFile)) {
            return [];
        }

        $content = file_get_contents(self::$dataFile);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            return [];
        }

        $contrats = [];
        foreach ($data as $item) {
            $contrats[] = new ContratLocation(
                (int)$item["id"],
                (int)$item["locataireId"],
                (int)$item["vehiculeId"],
                (string)$item["dateDebut"],
                (string)$item["dateFin"],
                (float)$item["prixTotal"],
                (string)($item["statut"] ?? "En cours")
            );
        }

        return $contrats;
    }

    public static function TrouverParId(int $id): ?ContratLocation {
        $contrats = self::AfficherTous();
        foreach ($contrats as $c) {
            if ($c->getId() === $id) {
                return $c;
            }
        }
        return null;
    }

    public static function ajouter(
        int $locataireId,
        int $vehiculeId,
        string $dateDebut,
        string $dateFin,
        float $prixTotal,
        string $statut = "En cours"
    ): ContratLocation {
        if ($locataireId <= 0) {
            throw new Exception("Le locataire sélectionné est invalide.");
        }
        if ($vehiculeId <= 0) {
            throw new Exception("Le véhicule sélectionné est invalide.");
        }
        if (trim($dateDebut) === "" || trim($dateFin) === "") {
            throw new Exception("Les dates de début et de fin sont obligatoires.");
        }
        if (strtotime($dateFin) < strtotime($dateDebut)) {
            throw new Exception("La date de fin ne peut pas être antérieure à la date de début.");
        }
        if ($prixTotal <= 0) {
            throw new Exception("Le montant total doit être supérieur à 0.");
        }

        $contrats = self::AfficherTous();

        $maxId = 0;
        foreach ($contrats as $c) {
            if ($c->getId() > $maxId) {
                $maxId = $c->getId();
            }
        }
        $newId = $maxId + 1;

        $nouveauContrat = new ContratLocation(
            $newId,
            $locataireId,
            $vehiculeId,
            trim($dateDebut),
            trim($dateFin),
            $prixTotal,
            trim($statut)
        );

        $contrats[] = $nouveauContrat;
        self::sauvegarder($contrats);

        return $nouveauContrat;
    }

    public static function mettreAJourStatut(int $id, string $statut): bool {
        $contrats = self::AfficherTous();
        $trouve = false;

        foreach ($contrats as $c) {
            if ($c->getId() === $id) {
                $c->setStatut($statut);
                $trouve = true;
                break;
            }
        }

        if ($trouve) {
            self::sauvegarder($contrats);
        }

        return $trouve;
    }

    public static function supprimer(int $id): bool {
        $contrats = self::AfficherTous();
        $filtrees = array_filter($contrats, fn($c) => $c->getId() !== $id);

        if (count($filtrees) === count($contrats)) {
            return false;
        }

        self::sauvegarder(array_values($filtrees));
        return true;
    }

    private static function sauvegarder(array $contrats): void {
        $data = array_map(fn($c) => $c->jsonSerialize(), $contrats);
        file_put_contents(self::$dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}


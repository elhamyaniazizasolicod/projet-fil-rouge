<?php

require_once __DIR__ . "/../classes/Vehicule.php";

class VehiculeDAO {
    private static string $dataFile = __DIR__ . "/../data/vehicules.json";

    public static function AfficherTous(): array {
        if (!file_exists(self::$dataFile)) {
            return [];
        }

        $content = file_get_contents(self::$dataFile);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            return [];
        }

        $vehicules = [];
        foreach ($data as $item) {
            $vehicules[] = new Vehicule(
                (int)$item["id"],
                (string)$item["immatriculation"],
                (string)$item["marque"],
                (string)$item["modele"],
                (int)$item["categorieId"],
                (float)$item["prixJour"],
                (string)($item["statut"] ?? "Disponible"),
                (string)($item["image"] ?? "")
            );
        }

        return $vehicules;
    }

    public static function TrouverParId(int $id): ?Vehicule {
        $vehicules = self::AfficherTous();
        foreach ($vehicules as $v) {
            if ($v->getId() === $id) {
                return $v;
            }
        }
        return null;
    }

    public static function ajouter(
        string $immatriculation,
        string $marque,
        string $modele,
        int $categorieId,
        float $prixJour,
        string $statut = "Disponible",
        string $image = ""
    ): Vehicule {
        if (trim($immatriculation) === "") {
            throw new Exception("L'immatriculation est obligatoire.");
        }
        if (trim($marque) === "") {
            throw new Exception("La marque est obligatoire.");
        }
        if (trim($modele) === "") {
            throw new Exception("Le modèle est obligatoire.");
        }
        if ($categorieId <= 0) {
            throw new Exception("La catégorie sélectionnée est invalide.");
        }
        if ($prixJour <= 0) {
            throw new Exception("Le prix par jour doit être supérieur à 0.");
        }

        $vehicules = self::AfficherTous();

        // Vérifier l'unicité de l'immatriculation
        foreach ($vehicules as $v) {
            if (strcasecmp($v->getImmatriculation(), trim($immatriculation)) === 0) {
                throw new Exception("Un véhicule avec cette immatriculation existe déjà.");
            }
        }

        $maxId = 0;
        foreach ($vehicules as $v) {
            if ($v->getId() > $maxId) {
                $maxId = $v->getId();
            }
        }
        $newId = $maxId + 1;

        $nouveauVehicule = new Vehicule(
            $newId,
            trim($immatriculation),
            trim($marque),
            trim($modele),
            $categorieId,
            $prixJour,
            trim($statut),
            trim($image)
        );

        $vehicules[] = $nouveauVehicule;
        self::sauvegarder($vehicules);

        return $nouveauVehicule;
    }

    public static function mettreAJourStatut(int $id, string $statut): bool {
        $vehicules = self::AfficherTous();
        $trouve = false;

        foreach ($vehicules as $v) {
            if ($v->getId() === $id) {
                $v->setStatut($statut);
                $trouve = true;
                break;
            }
        }

        if ($trouve) {
            self::sauvegarder($vehicules);
        }

        return $trouve;
    }

    public static function supprimer(int $id): bool {
        $vehicules = self::AfficherTous();
        $filtrees = array_filter($vehicules, fn($v) => $v->getId() !== $id);

        if (count($filtrees) === count($vehicules)) {
            return false;
        }

        self::sauvegarder(array_values($filtrees));
        return true;
    }

    private static function sauvegarder(array $vehicules): void {
        $data = array_map(fn($v) => $v->jsonSerialize(), $vehicules);
        file_put_contents(self::$dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

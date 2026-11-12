<?php

require_once __DIR__ . "/../classes/Locataire.php";

class LocataireDAO {
    private static string $dataFile = __DIR__ . "/../data/locataires.json";

    public static function AfficherTous(): array {
        if (!file_exists(self::$dataFile)) {
            return [];
        }

        $content = file_get_contents(self::$dataFile);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            return [];
        }

        $locataires = [];
        foreach ($data as $item) {
            $locataires[] = new Locataire(
                (int)$item["id"],
                (string)$item["cin"],
                (string)$item["nom"],
                (string)$item["prenom"],
                (string)($item["telephone"] ?? ""),
                (string)($item["email"] ?? ""),
                (string)($item["permis"] ?? "")
            );
        }

        return $locataires;
    }

    public static function TrouverParId(int $id): ?Locataire {
        $locataires = self::AfficherTous();
        foreach ($locataires as $l) {
            if ($l->getId() === $id) {
                return $l;
            }
        }
        return null;
    }

    public static function ajouter(
        string $cin,
        string $nom,
        string $prenom,
        string $telephone,
        string $email,
        string $permis
    ): Locataire {
        if (trim($cin) === "") {
            throw new Exception("Le numéro CIN est obligatoire.");
        }
        if (trim($nom) === "") {
            throw new Exception("Le nom est obligatoire.");
        }
        if (trim($prenom) === "") {
            throw new Exception("Le prénom est obligatoire.");
        }
        if (trim($telephone) === "") {
            throw new Exception("Le numéro de téléphone est obligatoire.");
        }
        if (trim($permis) === "") {
            throw new Exception("Le numéro de permis est obligatoire.");
        }

        $locataires = self::AfficherTous();

        // Vérifier l'unicité du CIN
        foreach ($locataires as $l) {
            if (strcasecmp($l->getCin(), trim($cin)) === 0) {
                throw new Exception("Un locataire avec ce CIN existe déjà.");
            }
        }

        $maxId = 0;
        foreach ($locataires as $l) {
            if ($l->getId() > $maxId) {
                $maxId = $l->getId();
            }
        }
        $newId = $maxId + 1;

        $nouveauLocataire = new Locataire(
            $newId,
            trim($cin),
            trim($nom),
            trim($prenom),
            trim($telephone),
            trim($email),
            trim($permis)
        );

        $locataires[] = $nouveauLocataire;
        self::sauvegarder($locataires);

        return $nouveauLocataire;
    }

    public static function supprimer(int $id): bool {
        $locataires = self::AfficherTous();
        $filtrees = array_filter($locataires, fn($l) => $l->getId() !== $id);

        if (count($filtrees) === count($locataires)) {
            return false;
        }

        self::sauvegarder(array_values($filtrees));
        return true;
    }

    private static function sauvegarder(array $locataires): void {
        $data = array_map(fn($l) => $l->jsonSerialize(), $locataires);
        file_put_contents(self::$dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}


<?php

require_once __DIR__ . "/../classes/CategorieVehicule.php";

class CategorieDAO {
    private static string $dataFile = __DIR__ . "/../data/categories.json";

    public static function AfficherTous(): array {
        if (!file_exists(self::$dataFile)) {
            return [];
        }

        $content = file_get_contents(self::$dataFile);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            return [];
        }

        $categories = [];
        foreach ($data as $item) {
            $categories[] = new CategorieVehicule(
                (int)$item["id"],
                (string)$item["nom"],
                (string)($item["description"] ?? ""),
                (float)($item["tarifBase"] ?? 0.0),
                (string)($item["image"] ?? "")
            );
        }

        return $categories;
    }

    public static function TrouverParId(int $id): ?CategorieVehicule {
        $categories = self::AfficherTous();
        foreach ($categories as $cat) {
            if ($cat->getId() === $id) {
                return $cat;
            }
        }
        return null;
    }

    public static function ajouter(string $nom, string $description, float $tarifBase, string $image = ""): CategorieVehicule {
        if (trim($nom) === "") {
            throw new Exception("Le nom de la catégorie est obligatoire.");
        }
        if ($tarifBase <= 0) {
            throw new Exception("Le tarif de base doit être supérieur à 0.");
        }

        $categories = self::AfficherTous();
        $maxId = 0;
        foreach ($categories as $cat) {
            if ($cat->getId() > $maxId) {
                $maxId = $cat->getId();
            }
        }
        $newId = $maxId + 1;

        $nouvelleCategorie = new CategorieVehicule($newId, trim($nom), trim($description), $tarifBase, trim($image));
        $categories[] = $nouvelleCategorie;

        self::sauvegarder($categories);
        return $nouvelleCategorie;
    }

    public static function supprimer(int $id): bool {
        $categories = self::AfficherTous();
        $filtrees = array_filter($categories, fn($c) => $c->getId() !== $id);

        if (count($filtrees) === count($categories)) {
            return false;
        }

        self::sauvegarder(array_values($filtrees));
        return true;
    }

    private static function sauvegarder(array $categories): void {
        $data = array_map(fn($c) => $c->jsonSerialize(), $categories);
        file_put_contents(self::$dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

<?php

class CategorieVehicule implements JsonSerializable {
    private int $id;
    private string $nom;
    private string $description;
    private float $tarifBase;
    private string $image;

    public function __construct(int $id, string $nom, string $description, float $tarifBase, string $image = "") {
        $this->id = $id;
        $this->nom = $nom;
        $this->description = $description;
        $this->tarifBase = $tarifBase;
        $this->image = $image;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getNom(): string {
        return $this->nom;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getTarifBase(): float {
        return $this->tarifBase;
    }

    public function getImage(): string {
        return $this->image;
    }

    public function setImage(string $image): void {
        $this->image = $image;
    }

    public function jsonSerialize(): array {
        return [
            "id" => $this->id,
            "nom" => $this->nom,
            "description" => $this->description,
            "tarifBase" => $this->tarifBase,
            "image" => $this->image
        ];
    }
}

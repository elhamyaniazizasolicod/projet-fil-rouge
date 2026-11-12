<?php
declare(strict_types=1);

namespace AutoLoc\Classes;

use JsonSerializable;

final class CategorieVehicule implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $nom,
        private string $description,
        private float $tarifBase,
        private string $image
    ) {}

    public function getId(): int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getDescription(): string { return $this->description; }
    public function getTarifBase(): float { return $this->tarifBase; }
    public function getImage(): string { return $this->image; }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'tarifBase' => $this->tarifBase,
            'image' => $this->image,
        ];
    }
}

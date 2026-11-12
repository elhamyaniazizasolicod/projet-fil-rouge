<?php
declare(strict_types=1);

namespace AutoLoc\Classes;

use JsonSerializable;

final class Vehicule implements JsonSerializable
{
    public const STATUTS = ['Disponible', 'Loué', 'En maintenance'];

    public function __construct(
        private int $id,
        private string $immatriculation,
        private string $marque,
        private string $modele,
        private int $categorieId,
        private float $prixJour,
        private string $statut,
        private string $image
    ) {}

    public function getId(): int { return $this->id; }
    public function getImmatriculation(): string { return $this->immatriculation; }
    public function getMarque(): string { return $this->marque; }
    public function getModele(): string { return $this->modele; }
    public function getCategorieId(): int { return $this->categorieId; }
    public function getPrixJour(): float { return $this->prixJour; }
    public function getStatut(): string { return $this->statut; }
    public function getImage(): string { return $this->image; }

    public function setStatut(string $statut): void
    {
        if (!in_array($statut, self::STATUTS, true)) {
            throw new \InvalidArgumentException('Statut de véhicule invalide.');
        }
        $this->statut = $statut;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'immatriculation' => $this->immatriculation,
            'marque' => $this->marque,
            'modele' => $this->modele,
            'categorieId' => $this->categorieId,
            'prixJour' => $this->prixJour,
            'statut' => $this->statut,
            'image' => $this->image,
        ];
    }
}

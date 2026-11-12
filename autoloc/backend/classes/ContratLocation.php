<?php
declare(strict_types=1);

namespace AutoLoc\Classes;

use JsonSerializable;

final class ContratLocation implements JsonSerializable
{
    public const STATUTS = ['En cours', 'Terminé'];

    public function __construct(
        private int $id,
        private int $locataireId,
        private int $vehiculeId,
        private string $dateDebut,
        private string $dateFin,
        private float $prixTotal,
        private string $statut
    ) {}

    public function getId(): int { return $this->id; }
    public function getLocataireId(): int { return $this->locataireId; }
    public function getVehiculeId(): int { return $this->vehiculeId; }
    public function getDateDebut(): string { return $this->dateDebut; }
    public function getDateFin(): string { return $this->dateFin; }
    public function getPrixTotal(): float { return $this->prixTotal; }
    public function getStatut(): string { return $this->statut; }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'locataireId' => $this->locataireId,
            'vehiculeId' => $this->vehiculeId,
            'dateDebut' => $this->dateDebut,
            'dateFin' => $this->dateFin,
            'prixTotal' => $this->prixTotal,
            'statut' => $this->statut,
        ];
    }
}

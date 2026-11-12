<?php
declare(strict_types=1);

namespace AutoLoc\Classes;

use JsonSerializable;

final class Locataire implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $cin,
        private string $nom,
        private string $prenom,
        private string $telephone,
        private string $email,
        private string $permis
    ) {}

    public function getId(): int { return $this->id; }
    public function getCin(): string { return $this->cin; }
    public function getNom(): string { return $this->nom; }
    public function getPrenom(): string { return $this->prenom; }
    public function getTelephone(): string { return $this->telephone; }
    public function getEmail(): string { return $this->email; }
    public function getPermis(): string { return $this->permis; }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'cin' => $this->cin,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'permis' => $this->permis,
        ];
    }
}

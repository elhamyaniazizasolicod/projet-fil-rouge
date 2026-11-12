<?php

class Vehicule implements JsonSerializable {
    private int $id;
    private string $immatriculation;
    private string $marque;
    private string $modele;
    private int $categorieId;
    private float $prixJour;
    private string $statut;
    private string $image;

    public function __construct(
        int $id,
        string $immatriculation,
        string $marque,
        string $modele,
        int $categorieId,
        float $prixJour,
        string $statut = "Disponible",
        string $image = ""
    ) {
        $this->id = $id;
        $this->immatriculation = $immatriculation;
        $this->marque = $marque;
        $this->modele = $modele;
        $this->categorieId = $categorieId;
        $this->prixJour = $prixJour;
        $this->statut = $statut;
        $this->image = $image;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getImmatriculation(): string {
        return $this->immatriculation;
    }

    public function getMarque(): string {
        return $this->marque;
    }

    public function getModele(): string {
        return $this->modele;
    }

    public function getCategorieId(): int {
        return $this->categorieId;
    }

    public function getPrixJour(): float {
        return $this->prixJour;
    }

    public function getStatut(): string {
        return $this->statut;
    }

    public function getImage(): string {
        return $this->image;
    }

    public function setStatut(string $statut): void {
        $this->statut = $statut;
    }

    public function setImage(string $image): void {
        $this->image = $image;
    }

    public function jsonSerialize(): array {
        return [
            "id" => $this->id,
            "immatriculation" => $this->immatriculation,
            "marque" => $this->marque,
            "modele" => $this->modele,
            "categorieId" => $this->categorieId,
            "prixJour" => $this->prixJour,
            "statut" => $this->statut,
            "image" => $this->image
        ];
    }
}

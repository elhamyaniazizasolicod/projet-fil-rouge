<?php

class Locataire implements JsonSerializable {
    private int $id;
    private string $cin;
    private string $nom;
    private string $prenom;
    private string $telephone;
    private string $email;
    private string $permis;

    public function __construct(
        int $id,
        string $cin,
        string $nom,
        string $prenom,
        string $telephone,
        string $email,
        string $permis
    ) {
        $this->id = $id;
        $this->cin = $cin;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->telephone = $telephone;
        $this->email = $email;
        $this->permis = $permis;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getCin(): string {
        return $this->cin;
    }

    public function getNom(): string {
        return $this->nom;
    }

    public function getPrenom(): string {
        return $this->prenom;
    }

    public function getNomComplet(): string {
        return $this->nom . " " . $this->prenom;
    }

    public function getTelephone(): string {
        return $this->telephone;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function getPermis(): string {
        return $this->permis;
    }

    public function jsonSerialize(): array {
        return [
            "id" => $this->id,
            "cin" => $this->cin,
            "nom" => $this->nom,
            "prenom" => $this->prenom,
            "nomComplet" => $this->getNomComplet(),
            "telephone" => $this->telephone,
            "email" => $this->email,
            "permis" => $this->permis
        ];
    }
}


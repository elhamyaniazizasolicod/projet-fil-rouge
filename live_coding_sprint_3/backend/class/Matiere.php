<?php
class Matiere
{
    private int $id;
    private string $nom;
    private string $description;

    public function __construct(int $id = 0, string $nom = "", string $description = "")
    {
        $this->id = $id;
        $this->nom = $nom;
        $this->description = $description;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
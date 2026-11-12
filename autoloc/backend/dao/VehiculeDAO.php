<?php
declare(strict_types=1);

namespace AutoLoc\DAO;

use AutoLoc\Classes\Vehicule;

final class VehiculeDAO extends BaseJsonDAO
{
    public function __construct(?string $filePath = null)
    {
        parent::__construct($filePath ?? __DIR__ . '/../data/vehicules.json');
    }

    /** @return Vehicule[] */
    public function findAll(): array
    {
        return array_map(fn (array $row): Vehicule => $this->hydrate($row), $this->readRecords());
    }

    public function findById(int $id): ?Vehicule
    {
        foreach ($this->readRecords() as $row) {
            if ((int) $row['id'] === $id) return $this->hydrate($row);
        }
        return null;
    }

    public function findByImmatriculation(string $immatriculation, ?int $exceptId = null): ?Vehicule
    {
        foreach ($this->findAll() as $vehicule) {
            if (strtolower($vehicule->getImmatriculation()) === strtolower(trim($immatriculation)) && $vehicule->getId() !== $exceptId) return $vehicule;
        }
        return null;
    }

    public function create(array $data): Vehicule
    {
        $this->validateRequired($data, ['immatriculation', 'marque', 'modele', 'image']);
        $immatriculation = trim((string) $data['immatriculation']);
        if ($this->findByImmatriculation($immatriculation)) throw new \InvalidArgumentException('Cette immatriculation existe déjà.');
        $statut = (string) ($data['statut'] ?? 'Disponible');
        if (!in_array($statut, Vehicule::STATUTS, true)) throw new \InvalidArgumentException('Statut de véhicule invalide.');
        $record = [
            'immatriculation' => $immatriculation,
            'marque' => trim((string) $data['marque']),
            'modele' => trim((string) $data['modele']),
            'categorieId' => $this->positiveInt($data['categorieId'] ?? null, 'categorieId'),
            'prixJour' => $this->positiveFloat($data['prixJour'] ?? null, 'prixJour'),
            'statut' => $statut,
            'image' => trim((string) $data['image']),
        ];
        return $this->hydrate($this->insertRecord($record));
    }

    public function update(int $id, array $data): ?Vehicule
    {
        $current = $this->findById($id);
        if (!$current) return null;
        $merged = array_merge($current->jsonSerialize(), $data);
        $this->validateRequired($merged, ['immatriculation', 'marque', 'modele', 'image']);
        $immatriculation = trim((string) $merged['immatriculation']);
        if ($this->findByImmatriculation($immatriculation, $id)) throw new \InvalidArgumentException('Cette immatriculation existe déjà.');
        $statut = (string) $merged['statut'];
        if (!in_array($statut, Vehicule::STATUTS, true)) throw new \InvalidArgumentException('Statut de véhicule invalide.');
        $record = [
            'immatriculation' => $immatriculation,
            'marque' => trim((string) $merged['marque']),
            'modele' => trim((string) $merged['modele']),
            'categorieId' => $this->positiveInt($merged['categorieId'], 'categorieId'),
            'prixJour' => $this->positiveFloat($merged['prixJour'], 'prixJour'),
            'statut' => $statut,
            'image' => trim((string) $merged['image']),
        ];
        $this->replaceRecord($id, $record);
        return $this->findById($id);
    }

    public function delete(int $id): bool { return $this->removeRecord($id); }

    private function hydrate(array $row): Vehicule
    {
        return new Vehicule((int) $row['id'], (string) $row['immatriculation'], (string) $row['marque'], (string) $row['modele'], (int) $row['categorieId'], (float) $row['prixJour'], (string) $row['statut'], (string) $row['image']);
    }
}

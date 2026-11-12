<?php
declare(strict_types=1);

namespace AutoLoc\DAO;

use AutoLoc\Classes\ContratLocation;

final class ContratDAO extends BaseJsonDAO
{
    public function __construct(?string $filePath = null)
    {
        parent::__construct($filePath ?? __DIR__ . '/../data/contrats.json');
    }

    /** @return ContratLocation[] */
    public function findAll(): array
    {
        return array_map(fn (array $row): ContratLocation => $this->hydrate($row), $this->readRecords());
    }

    public function findById(int $id): ?ContratLocation
    {
        foreach ($this->readRecords() as $row) {
            if ((int) $row['id'] === $id) return $this->hydrate($row);
        }
        return null;
    }

    public function hasOngoingForVehicle(int $vehiculeId, ?int $exceptId = null): bool
    {
        foreach ($this->findAll() as $contrat) {
            if ($contrat->getVehiculeId() === $vehiculeId && $contrat->getStatut() === 'En cours' && $contrat->getId() !== $exceptId) return true;
        }
        return false;
    }

    public function hasOngoingForLocataire(int $locataireId): bool
    {
        foreach ($this->findAll() as $contrat) {
            if ($contrat->getLocataireId() === $locataireId && $contrat->getStatut() === 'En cours') return true;
        }
        return false;
    }

    public function create(array $data): ContratLocation
    {
        $record = $this->normalize($data);
        return $this->hydrate($this->insertRecord($record));
    }

    public function update(int $id, array $data): ?ContratLocation
    {
        $current = $this->findById($id);
        if (!$current) return null;
        $record = $this->normalize(array_merge($current->jsonSerialize(), $data));
        $this->replaceRecord($id, $record);
        return $this->findById($id);
    }

    public function delete(int $id): bool { return $this->removeRecord($id); }

    private function normalize(array $data): array
    {
        $this->validateRequired($data, ['dateDebut', 'dateFin', 'statut']);
        $statut = (string) $data['statut'];
        if (!in_array($statut, ContratLocation::STATUTS, true)) throw new \InvalidArgumentException('Statut de contrat invalide.');
        return [
            'locataireId' => $this->positiveInt($data['locataireId'] ?? null, 'locataireId'),
            'vehiculeId' => $this->positiveInt($data['vehiculeId'] ?? null, 'vehiculeId'),
            'dateDebut' => (string) $data['dateDebut'],
            'dateFin' => (string) $data['dateFin'],
            'prixTotal' => $this->positiveFloat($data['prixTotal'] ?? null, 'prixTotal'),
            'statut' => $statut,
        ];
    }

    private function hydrate(array $row): ContratLocation
    {
        return new ContratLocation((int) $row['id'], (int) $row['locataireId'], (int) $row['vehiculeId'], (string) $row['dateDebut'], (string) $row['dateFin'], (float) $row['prixTotal'], (string) $row['statut']);
    }
}

<?php
declare(strict_types=1);

namespace AutoLoc\DAO;

use AutoLoc\Classes\Locataire;

final class LocataireDAO extends BaseJsonDAO
{
    public function __construct(?string $filePath = null)
    {
        parent::__construct($filePath ?? __DIR__ . '/../data/locataires.json');
    }

    /** @return Locataire[] */
    public function findAll(): array
    {
        return array_map(fn (array $row): Locataire => $this->hydrate($row), $this->readRecords());
    }

    public function findById(int $id): ?Locataire
    {
        foreach ($this->readRecords() as $row) {
            if ((int) $row['id'] === $id) return $this->hydrate($row);
        }
        return null;
    }

    public function findByCin(string $cin, ?int $exceptId = null): ?Locataire
    {
        foreach ($this->findAll() as $locataire) {
            if (strtolower($locataire->getCin()) === strtolower(trim($cin)) && $locataire->getId() !== $exceptId) return $locataire;
        }
        return null;
    }

    public function create(array $data): Locataire
    {
        $this->validateRequired($data, ['cin', 'nom', 'prenom', 'telephone', 'email', 'permis']);
        $cin = strtoupper(trim((string) $data['cin']));
        if ($this->findByCin($cin)) throw new \InvalidArgumentException('Ce CIN existe déjà.');
        $email = trim((string) $data['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Adresse e-mail invalide.');
        $record = ['cin' => $cin, 'nom' => trim((string) $data['nom']), 'prenom' => trim((string) $data['prenom']), 'telephone' => trim((string) $data['telephone']), 'email' => $email, 'permis' => trim((string) $data['permis'])];
        return $this->hydrate($this->insertRecord($record));
    }

    public function update(int $id, array $data): ?Locataire
    {
        $current = $this->findById($id);
        if (!$current) return null;
        $merged = array_merge($current->jsonSerialize(), $data);
        $this->validateRequired($merged, ['cin', 'nom', 'prenom', 'telephone', 'email', 'permis']);
        $cin = strtoupper(trim((string) $merged['cin']));
        if ($this->findByCin($cin, $id)) throw new \InvalidArgumentException('Ce CIN existe déjà.');
        $email = trim((string) $merged['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Adresse e-mail invalide.');
        $record = ['cin' => $cin, 'nom' => trim((string) $merged['nom']), 'prenom' => trim((string) $merged['prenom']), 'telephone' => trim((string) $merged['telephone']), 'email' => $email, 'permis' => trim((string) $merged['permis'])];
        $this->replaceRecord($id, $record);
        return $this->findById($id);
    }

    public function delete(int $id): bool { return $this->removeRecord($id); }

    private function hydrate(array $row): Locataire
    {
        return new Locataire((int) $row['id'], (string) $row['cin'], (string) $row['nom'], (string) $row['prenom'], (string) $row['telephone'], (string) $row['email'], (string) $row['permis']);
    }
}

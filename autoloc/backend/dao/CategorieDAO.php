<?php
declare(strict_types=1);

namespace AutoLoc\DAO;

use AutoLoc\Classes\CategorieVehicule;

final class CategorieDAO extends BaseJsonDAO
{
    public function __construct(?string $filePath = null)
    {
        parent::__construct($filePath ?? __DIR__ . '/../data/categories.json');
    }

    /** @return CategorieVehicule[] */
    public function findAll(): array
    {
        return array_map(fn (array $row): CategorieVehicule => $this->hydrate($row), $this->readRecords());
    }

    public function findById(int $id): ?CategorieVehicule
    {
        foreach ($this->readRecords() as $row) {
            if ((int) $row['id'] === $id) return $this->hydrate($row);
        }
        return null;
    }

    public function create(array $data): CategorieVehicule
    {
        $this->validateRequired($data, ['nom', 'description', 'image']);
        $record = [
            'nom' => trim((string) $data['nom']),
            'description' => trim((string) $data['description']),
            'tarifBase' => $this->positiveFloat($data['tarifBase'] ?? null, 'tarifBase'),
            'image' => trim((string) $data['image']),
        ];
        return $this->hydrate($this->insertRecord($record));
    }

    public function update(int $id, array $data): ?CategorieVehicule
    {
        $current = $this->findById($id);
        if (!$current) return null;
        $merged = array_merge($current->jsonSerialize(), $data);
        $this->validateRequired($merged, ['nom', 'description', 'image']);
        $record = [
            'nom' => trim((string) $merged['nom']),
            'description' => trim((string) $merged['description']),
            'tarifBase' => $this->positiveFloat($merged['tarifBase'], 'tarifBase'),
            'image' => trim((string) $merged['image']),
        ];
        $this->replaceRecord($id, $record);
        return $this->findById($id);
    }

    public function delete(int $id): bool { return $this->removeRecord($id); }

    private function hydrate(array $row): CategorieVehicule
    {
        return new CategorieVehicule((int) $row['id'], (string) $row['nom'], (string) $row['description'], (float) $row['tarifBase'], (string) $row['image']);
    }
}

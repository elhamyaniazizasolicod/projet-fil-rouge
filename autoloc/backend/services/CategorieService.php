<?php
declare(strict_types=1);

namespace AutoLoc\Services;

use AutoLoc\DAO\CategorieDAO;
use AutoLoc\DAO\VehiculeDAO;

final class CategorieService extends BaseService
{
    public function __construct(private ?CategorieDAO $categories = null, private ?VehiculeDAO $vehicules = null)
    {
        $this->categories ??= new CategorieDAO();
        $this->vehicules ??= new VehiculeDAO();
    }

    public function index(): array
    {
        $vehicles = $this->vehicules->findAll();
        $data = [];
        foreach ($this->categories->findAll() as $category) {
            $row = $category->jsonSerialize();
            $row['vehicules'] = array_values(array_map(
                static fn ($vehicle): array => $vehicle->jsonSerialize(),
                array_filter($vehicles, static fn ($vehicle): bool => $vehicle->getCategorieId() === $category->getId())
            ));
            $data[] = $row;
        }
        return $this->response($data);
    }

    public function store(array $input): array
    {
        return $this->created($this->categories->create($input), 'Catégorie ajoutée avec succès.');
    }

    public function update(int $id, array $input): array
    {
        $category = $this->categories->update($id, $input);
        if (!$category) $this->notFound('Catégorie introuvable.');
        return $this->response($category, 'Catégorie mise à jour.');
    }

    public function destroy(int $id): array
    {
        foreach ($this->vehicules->findAll() as $vehicle) {
            if ($vehicle->getCategorieId() === $id) {
                $this->conflict('Impossible de supprimer une catégorie qui contient des véhicules.');
            }
        }
        if (!$this->categories->delete($id)) $this->notFound('Catégorie introuvable.');
        return $this->response(null, 'Catégorie supprimée.');
    }
}

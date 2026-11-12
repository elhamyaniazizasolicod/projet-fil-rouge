<?php
declare(strict_types=1);

namespace AutoLoc\Services;

use AutoLoc\Classes\Vehicule;
use AutoLoc\DAO\CategorieDAO;
use AutoLoc\DAO\ContratDAO;
use AutoLoc\DAO\VehiculeDAO;

final class VehiculeService extends BaseService
{
    public function __construct(
        private ?VehiculeDAO $vehicules = null,
        private ?CategorieDAO $categories = null,
        private ?ContratDAO $contrats = null
    ) {
        $this->vehicules ??= new VehiculeDAO();
        $this->categories ??= new CategorieDAO();
        $this->contrats ??= new ContratDAO();
    }

    public function index(): array
    {
        $categories = [];
        foreach ($this->categories->findAll() as $category) $categories[$category->getId()] = $category->getNom();
        $data = array_map(static function (Vehicule $vehicle) use ($categories): array {
            return array_merge($vehicle->jsonSerialize(), ['categorieNom' => $categories[$vehicle->getCategorieId()] ?? 'Catégorie inconnue']);
        }, $this->vehicules->findAll());
        return $this->response($data);
    }

    public function store(array $input): array
    {
        $categoryId = (int) ($input['categorieId'] ?? 0);
        if (!$this->categories->findById($categoryId)) $this->notFound('La catégorie sélectionnée n’existe pas.');
        if (($input['statut'] ?? 'Disponible') === 'Loué') {
            throw new \InvalidArgumentException('Un nouveau véhicule doit être disponible ou en maintenance.');
        }
        return $this->created($this->vehicules->create($input), 'Véhicule ajouté avec succès.');
    }

    public function update(int $id, array $input): array
    {
        $current = $this->vehicules->findById($id);
        if (!$current) $this->notFound('Véhicule introuvable.');
        $categoryId = (int) ($input['categorieId'] ?? $current->getCategorieId());
        if (!$this->categories->findById($categoryId)) $this->notFound('La catégorie sélectionnée n’existe pas.');
        if ($this->contrats->hasOngoingForVehicle($id)) {
            if (isset($input['statut']) && $input['statut'] !== 'Loué') {
                $this->conflict('Ce véhicule est lié à une location en cours : son statut ne peut pas être modifié.');
            }
            $input['statut'] = 'Loué';
        } elseif (isset($input['statut']) && $input['statut'] === 'Loué') {
            throw new \InvalidArgumentException('Le statut « Loué » est géré automatiquement par les contrats.');
        }
        $vehicle = $this->vehicules->update($id, $input);
        if (!$vehicle) $this->notFound('Véhicule introuvable.');
        return $this->response($vehicle, 'Véhicule mis à jour.');
    }

    public function destroy(int $id): array
    {
        $vehicle = $this->vehicules->findById($id);
        if (!$vehicle) $this->notFound('Véhicule introuvable.');
        if ($this->contrats->hasOngoingForVehicle($id) || $vehicle->getStatut() === 'Loué') {
            $this->conflict('Impossible de supprimer un véhicule actuellement loué.');
        }
        $this->vehicules->delete($id);
        return $this->response(null, 'Véhicule supprimé.');
    }
}

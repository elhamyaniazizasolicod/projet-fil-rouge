<?php
declare(strict_types=1);

namespace AutoLoc\Services;

use AutoLoc\DAO\ContratDAO;
use AutoLoc\DAO\VehiculeDAO;

final class StatsService extends BaseService
{
    public function __construct(private ?VehiculeDAO $vehicules = null, private ?ContratDAO $contrats = null)
    {
        $this->vehicules ??= new VehiculeDAO();
        $this->contrats ??= new ContratDAO();
    }

    public function index(): array
    {
        $vehicles = $this->vehicules->findAll();
        $contracts = $this->contrats->findAll();
        $data = [
            'flotteTotale' => count($vehicles),
            'vehiculesDisponibles' => count(array_filter($vehicles, static fn ($v): bool => $v->getStatut() === 'Disponible')),
            'locationsEnCours' => count(array_filter($contracts, static fn ($c): bool => $c->getStatut() === 'En cours')),
            'chiffreAffaires' => round(array_sum(array_map(static fn ($c): float => $c->getPrixTotal(), $contracts)), 2),
            'contratsRecents' => [],
        ];
        usort($contracts, static fn ($a, $b): int => $b->getId() <=> $a->getId());
        $data['contratsRecents'] = array_map(static fn ($c): array => $c->jsonSerialize(), array_slice($contracts, 0, 5));
        return $this->response($data);
    }
}

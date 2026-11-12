<?php
declare(strict_types=1);

namespace AutoLoc\Services;

use AutoLoc\Classes\ContratLocation;
use AutoLoc\DAO\ContratDAO;
use AutoLoc\DAO\LocataireDAO;
use AutoLoc\DAO\VehiculeDAO;
use DateTimeImmutable;
use Throwable;

final class ContratService extends BaseService
{
    public function __construct(
        private ?ContratDAO $contrats = null,
        private ?LocataireDAO $locataires = null,
        private ?VehiculeDAO $vehicules = null
    ) {
        $this->contrats ??= new ContratDAO();
        $this->locataires ??= new LocataireDAO();
        $this->vehicules ??= new VehiculeDAO();
    }

    public function index(): array
    {
        $tenants = [];
        foreach ($this->locataires->findAll() as $tenant) $tenants[$tenant->getId()] = $tenant;
        $vehicles = [];
        foreach ($this->vehicules->findAll() as $vehicle) $vehicles[$vehicle->getId()] = $vehicle;

        $data = array_map(static function (ContratLocation $contract) use ($tenants, $vehicles): array {
            $tenant = $tenants[$contract->getLocataireId()] ?? null;
            $vehicle = $vehicles[$contract->getVehiculeId()] ?? null;
            $duration = self::durationBetween($contract->getDateDebut(), $contract->getDateFin());
            return array_merge($contract->jsonSerialize(), [
                'locataireNom' => $tenant ? $tenant->getPrenom() . ' ' . $tenant->getNom() : 'Locataire supprimé',
                'locataireCin' => $tenant?->getCin() ?? '—',
                'vehiculeLabel' => $vehicle ? $vehicle->getMarque() . ' ' . $vehicle->getModele() : 'Véhicule supprimé',
                'marque' => $vehicle?->getMarque() ?? '',
                'modele' => $vehicle?->getModele() ?? '',
                'immatriculation' => $vehicle?->getImmatriculation() ?? '—',
                'dureeJours' => $duration,
            ]);
        }, $this->contrats->findAll());
        usort($data, static fn (array $a, array $b): int => $b['id'] <=> $a['id']);
        return $this->response($data);
    }

    public function store(array $input): array
    {
        $tenantId = (int) ($input['locataireId'] ?? 0);
        $vehicleId = (int) ($input['vehiculeId'] ?? 0);
        $tenant = $this->locataires->findById($tenantId);
        if (!$tenant) $this->notFound('Le locataire sélectionné n’existe pas.');
        $vehicle = $this->vehicules->findById($vehicleId);
        if (!$vehicle) $this->notFound('Le véhicule sélectionné n’existe pas.');
        if ($vehicle->getStatut() !== 'Disponible' || $this->contrats->hasOngoingForVehicle($vehicleId)) {
            $this->conflict('Ce véhicule n’est pas disponible pour la période demandée.');
        }

        $duration = self::durationBetween((string) ($input['dateDebut'] ?? ''), (string) ($input['dateFin'] ?? ''));
        $total = $duration * $vehicle->getPrixJour();
        $contract = $this->contrats->create([
            'locataireId' => $tenantId,
            'vehiculeId' => $vehicleId,
            'dateDebut' => (string) $input['dateDebut'],
            'dateFin' => (string) $input['dateFin'],
            'prixTotal' => $total,
            'statut' => 'En cours',
        ]);
        try {
            $updatedVehicle = $this->vehicules->update($vehicleId, ['statut' => 'Loué']);
            if (!$updatedVehicle) throw new \RuntimeException('Le véhicule n’a pas pu être mis à jour.');
        } catch (Throwable $exception) {
            $this->contrats->delete($contract->getId());
            throw $exception;
        }
        return $this->created($this->enrich($contract), 'Réservation créée. Le véhicule est maintenant loué.');
    }

    public function update(int $id, array $input): array
    {
        $current = $this->contrats->findById($id);
        if (!$current) $this->notFound('Contrat introuvable.');
        if ($current->getStatut() !== 'En cours') $this->conflict('Un contrat terminé ne peut plus être modifié.');

        $tenantId = (int) ($input['locataireId'] ?? $current->getLocataireId());
        $vehicleId = (int) ($input['vehiculeId'] ?? $current->getVehiculeId());
        if (!$this->locataires->findById($tenantId)) $this->notFound('Le locataire sélectionné n’existe pas.');
        $vehicle = $this->vehicules->findById($vehicleId);
        if (!$vehicle) $this->notFound('Le véhicule sélectionné n’existe pas.');

        $vehicleChanged = $vehicleId !== $current->getVehiculeId();
        if ($vehicleChanged && ($vehicle->getStatut() !== 'Disponible' || $this->contrats->hasOngoingForVehicle($vehicleId))) {
            $this->conflict('Le nouveau véhicule n’est pas disponible.');
        }
        $dateDebut = (string) ($input['dateDebut'] ?? $current->getDateDebut());
        $dateFin = (string) ($input['dateFin'] ?? $current->getDateFin());
        $duration = self::durationBetween($dateDebut, $dateFin);
        $total = $duration * $vehicle->getPrixJour();

        $oldVehicle = $this->vehicules->findById($current->getVehiculeId());
        if ($vehicleChanged) $this->vehicules->update($vehicleId, ['statut' => 'Loué']);
        try {
            $updated = $this->contrats->update($id, [
                'locataireId' => $tenantId,
                'vehiculeId' => $vehicleId,
                'dateDebut' => $dateDebut,
                'dateFin' => $dateFin,
                'prixTotal' => $total,
                'statut' => 'En cours',
            ]);
            if ($vehicleChanged && $oldVehicle) $this->vehicules->update($oldVehicle->getId(), ['statut' => 'Disponible']);
        } catch (Throwable $exception) {
            if ($vehicleChanged) $this->vehicules->update($vehicleId, ['statut' => 'Disponible']);
            throw $exception;
        }
        if (!$updated) $this->notFound('Contrat introuvable.');
        return $this->response($this->enrich($updated), 'Contrat mis à jour.');
    }

    public function close(int $id): array
    {
        $contract = $this->contrats->findById($id);
        if (!$contract) $this->notFound('Contrat introuvable.');
        if ($contract->getStatut() === 'Terminé') return $this->response($this->enrich($contract), 'Ce contrat est déjà terminé.');

        $updated = $this->contrats->update($id, [
            'locataireId' => $contract->getLocataireId(),
            'vehiculeId' => $contract->getVehiculeId(),
            'dateDebut' => $contract->getDateDebut(),
            'dateFin' => $contract->getDateFin(),
            'prixTotal' => $contract->getPrixTotal(),
            'statut' => 'Terminé',
        ]);
        if (!$updated) $this->notFound('Le contrat n’a pas pu être clôturé.');
        try {
            $updatedVehicle = $this->vehicules->update($contract->getVehiculeId(), ['statut' => 'Disponible']);
            if (!$updatedVehicle) throw new \RuntimeException('Le véhicule associé au contrat est introuvable.');
        } catch (Throwable $exception) {
            $this->contrats->update($id, [
                'locataireId' => $contract->getLocataireId(),
                'vehiculeId' => $contract->getVehiculeId(),
                'dateDebut' => $contract->getDateDebut(),
                'dateFin' => $contract->getDateFin(),
                'prixTotal' => $contract->getPrixTotal(),
                'statut' => 'En cours',
            ]);
            throw $exception;
        }
        return $this->response($this->enrich($updated), 'Contrat clôturé. Le véhicule est de nouveau disponible.');
    }

    public function destroy(int $id): array
    {
        $contract = $this->contrats->findById($id);
        if (!$contract) $this->notFound('Contrat introuvable.');
        if ($contract->getStatut() === 'En cours') $this->conflict('Clôturez le contrat avant de le supprimer.');
        $this->contrats->delete($id);
        return $this->response(null, 'Contrat supprimé.');
    }

    private function enrich(ContratLocation $contract): array
    {
        $tenant = $this->locataires->findById($contract->getLocataireId());
        $vehicle = $this->vehicules->findById($contract->getVehiculeId());
        return array_merge($contract->jsonSerialize(), [
            'locataireNom' => $tenant ? $tenant->getPrenom() . ' ' . $tenant->getNom() : 'Locataire supprimé',
            'locataireCin' => $tenant?->getCin() ?? '—',
            'vehiculeLabel' => $vehicle ? $vehicle->getMarque() . ' ' . $vehicle->getModele() : 'Véhicule supprimé',
            'marque' => $vehicle?->getMarque() ?? '',
            'modele' => $vehicle?->getModele() ?? '',
            'immatriculation' => $vehicle?->getImmatriculation() ?? '—',
            'dureeJours' => self::durationBetween($contract->getDateDebut(), $contract->getDateFin()),
        ]);
    }

    private static function durationBetween(string $start, string $end): int
    {
        $startDate = DateTimeImmutable::createFromFormat('!Y-m-d', $start);
        $startErrors = DateTimeImmutable::getLastErrors();
        $endDate = DateTimeImmutable::createFromFormat('!Y-m-d', $end);
        $endErrors = DateTimeImmutable::getLastErrors();
        if (!$startDate || !$endDate || $startDate->format('Y-m-d') !== $start || $endDate->format('Y-m-d') !== $end
            || (is_array($startErrors) && ($startErrors['warning_count'] > 0 || $startErrors['error_count'] > 0))
            || (is_array($endErrors) && ($endErrors['warning_count'] > 0 || $endErrors['error_count'] > 0))) {
            throw new \InvalidArgumentException('Les dates doivent respecter le format AAAA-MM-JJ.');
        }
        if ($endDate <= $startDate) throw new \InvalidArgumentException('La date de fin doit être après la date de début.');
        return (int) $startDate->diff($endDate)->days;
    }
}

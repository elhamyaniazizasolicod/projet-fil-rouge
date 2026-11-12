<?php
declare(strict_types=1);

namespace AutoLoc\Services;

use AutoLoc\DAO\ContratDAO;
use AutoLoc\DAO\LocataireDAO;

final class LocataireService extends BaseService
{
    public function __construct(private ?LocataireDAO $locataires = null, private ?ContratDAO $contrats = null)
    {
        $this->locataires ??= new LocataireDAO();
        $this->contrats ??= new ContratDAO();
    }

    public function index(): array
    {
        return $this->response($this->locataires->findAll());
    }

    public function store(array $input): array
    {
        return $this->created($this->locataires->create($input), 'Locataire ajouté avec succès.');
    }

    public function update(int $id, array $input): array
    {
        $locataire = $this->locataires->update($id, $input);
        if (!$locataire) $this->notFound('Locataire introuvable.');
        return $this->response($locataire, 'Locataire mis à jour.');
    }

    public function destroy(int $id): array
    {
        if (!$this->locataires->findById($id)) $this->notFound('Locataire introuvable.');
        if ($this->contrats->hasOngoingForLocataire($id)) {
            $this->conflict('Impossible de supprimer un locataire ayant une location en cours.');
        }
        $this->locataires->delete($id);
        return $this->response(null, 'Locataire supprimé.');
    }
}

<?php
declare(strict_types=1);

use AutoLoc\Classes\HttpException;
use AutoLoc\Services\CategorieService;
use AutoLoc\Services\ContratService;
use AutoLoc\Services\LocataireService;
use AutoLoc\Services\StatsService;
use AutoLoc\Services\VehiculeService;

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function sendJson(array $result): never
{
    http_response_code((int) ($result['status'] ?? 200));
    echo json_encode($result['body'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

try {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $resource = strtolower(trim((string) ($_GET['resource'] ?? '')));
    $id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
    if ($id === false || ($id !== null && $id <= 0)) {
        throw new HttpException('Identifiant invalide.', 400);
    }

    $rawBody = file_get_contents('php://input');
    $input = [];
    if ($rawBody !== false && trim($rawBody) !== '') {
        $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) throw new HttpException('Le corps JSON doit être un objet.', 400);
        $input = $decoded;
    }

    $service = match ($resource) {
        'stats' => new StatsService(),
        'categories' => new CategorieService(),
        'vehicules' => new VehiculeService(),
        'locataires' => new LocataireService(),
        'contrats' => new ContratService(),
        default => throw new HttpException('Ressource API inconnue.', 404),
    };

    $result = match ($resource) {
        'stats' => $method === 'GET'
            ? $service->index()
            : throw new HttpException('Méthode non autorisée pour les statistiques.', 405),
        'categories', 'vehicules', 'locataires' => match ($method) {
            'GET' => $service->index(),
            'POST' => $service->store($input),
            'PUT', 'PATCH' => $id ? $service->update($id, $input) : throw new HttpException('Un identifiant est requis pour modifier cet élément.', 400),
            'DELETE' => $id ? $service->destroy($id) : throw new HttpException('Un identifiant est requis pour supprimer cet élément.', 400),
            default => throw new HttpException('Méthode HTTP non autorisée.', 405),
        },
        'contrats' => match (true) {
            $method === 'GET' => $service->index(),
            $method === 'POST' && (($_GET['action'] ?? '') === 'cloturer') && $id !== null => $service->close($id),
            $method === 'POST' => $service->store($input),
            in_array($method, ['PUT', 'PATCH'], true) && $id !== null => $service->update($id, $input),
            $method === 'DELETE' && $id !== null => $service->destroy($id),
            default => throw new HttpException('Action ou méthode HTTP invalide pour les contrats.', 405),
        },
    };

    sendJson($result);
} catch (HttpException $exception) {
    sendJson(['status' => $exception->getStatusCode(), 'body' => ['success' => false, 'message' => $exception->getMessage(), 'data' => null]]);
} catch (InvalidArgumentException $exception) {
    sendJson(['status' => 400, 'body' => ['success' => false, 'message' => $exception->getMessage(), 'data' => null]]);
} catch (JsonException $exception) {
    sendJson(['status' => 400, 'body' => ['success' => false, 'message' => 'JSON invalide : vérifiez le format des données envoyées.', 'data' => null]]);
} catch (Throwable $exception) {
    error_log('[AutoLoc API] ' . $exception);
    sendJson(['status' => 500, 'body' => ['success' => false, 'message' => 'Une erreur interne est survenue. Consultez le journal PHP pour plus de détails.', 'data' => null]]);
}

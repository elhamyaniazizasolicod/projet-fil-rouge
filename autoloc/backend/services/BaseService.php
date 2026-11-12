<?php
declare(strict_types=1);

namespace AutoLoc\Services;

use AutoLoc\Classes\HttpException;

abstract class BaseService
{
    /** Standard service response consumed by the single API entry point. */
    protected function response(mixed $data = null, string $message = 'Opération réussie.', int $status = 200): array
    {
        return ['status' => $status, 'body' => ['success' => true, 'message' => $message, 'data' => $data]];
    }

    protected function created(mixed $data, string $message = 'Élément créé.'): array
    {
        return $this->response($data, $message, 201);
    }

    protected function notFound(string $message = 'Élément introuvable.'): never
    {
        throw new HttpException($message, 404);
    }

    protected function conflict(string $message): never
    {
        throw new HttpException($message, 409);
    }
}

<?php

abstract class BaseService {
    /**
     * Lit et décode le corps de la requête JSON (php://input).
     */
    protected function getJsonInput(): array {
        $raw = file_get_contents("php://input");
        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Envoie une réponse JSON formatée avec un code HTTP.
     */
    protected function sendResponse(int $statusCode, array $data): void {
        http_response_code($statusCode);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Envoie une réponse de succès standardisée.
     */
    protected function sendSuccess(string $message, mixed $data = null, int $statusCode = 200): void {
        $response = [
            "success" => true,
            "message" => $message
        ];
        if ($data !== null) {
            $response["data"] = $data;
        }
        $this->sendResponse($statusCode, $response);
    }

    /**
     * Envoie une réponse d'erreur standardisée.
     */
    protected function sendError(string $message, int $statusCode = 500): void {
        $this->sendResponse($statusCode, [
            "success" => false,
            "message" => $message
        ]);
    }
}


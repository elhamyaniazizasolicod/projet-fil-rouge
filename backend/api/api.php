<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../services/CategorieService.php";
require_once __DIR__ . "/../services/VehiculeService.php";
require_once __DIR__ . "/../services/LocataireService.php";
require_once __DIR__ . "/../services/ContratService.php";

$resource = $_GET["resource"] ?? "";
$action = $_GET["action"] ?? "";
$method = $_SERVER["REQUEST_METHOD"];
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

switch ($resource) {
    case "stats":
        (new ContratService())->stats();
        break;

    case "categories":
        $service = new CategorieService();
        if ($method === "GET") {
            $service->lister();
        } elseif ($method === "POST") {
            $service->ajouter();
        } elseif ($method === "DELETE") {
            $service->supprimer($id);
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Méthode non autorisée"]);
        }
        break;

    case "vehicules":
        $service = new VehiculeService();
        if ($method === "GET") {
            $service->lister();
        } elseif ($method === "POST") {
            $service->ajouter();
        } elseif ($method === "DELETE") {
            $service->supprimer($id);
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Méthode non autorisée"]);
        }
        break;

    case "locataires":
        $service = new LocataireService();
        if ($method === "GET") {
            $service->lister();
        } elseif ($method === "POST") {
            $service->ajouter();
        } elseif ($method === "DELETE") {
            $service->supprimer($id);
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Méthode non autorisée"]);
        }
        break;

    case "contrats":
        $service = new ContratService();
        if ($method === "GET") {
            $service->lister();
        } elseif ($method === "POST") {
            if ($action === "cloturer") {
                $service->cloturer($id);
            } else {
                $service->ajouter();
            }
        } elseif ($method === "DELETE") {
            $service->supprimer($id);
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Méthode non autorisée"]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Ressource inconnue. Ressources disponibles : stats, categories, vehicules, locataires, contrats"
        ]);
        break;
}


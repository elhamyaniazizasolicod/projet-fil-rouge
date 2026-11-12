<?php
    require_once __DIR__ . '/../services/MatiereService.php';
    header("Content-Type: application/json");
    $methode=$_SERVER["REQUEST_METHOD"];
    $service= new MatiereService();
    if($methode=="GET"){
        echo json_encode($service->AfficherMatiere());
    }
    if($methode=="POST"){
        echo json_encode($service->ajouterMatiere());
    }
?>
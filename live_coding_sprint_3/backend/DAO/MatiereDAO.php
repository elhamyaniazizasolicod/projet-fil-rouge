
<?php
require_once __DIR__ . '/../class/Matiere.php';
class MatiereDAO{
    public static $dataFile= __DIR__ . "/../data/gestionMatiere.json";

    public function AfficherMatiere(){
        $jsonData=file_get_contents(self::$dataFile);
        $data=json_decode($jsonData,true);
        $matieres=[];
        foreach($data as $index=>$item){
           $nom=$item["nom"] ?? $item["name"] ?? null;
           if(!is_string($nom)){
               throw new UnexpectedValueException("Missing subject name in JSON record " . ($index + 1));
           }
           $matiere=new Matiere($item["id"],$nom,$item["description"]);
           $matieres[]=$matiere;
        }
           return $matieres;
    }

    public function ajouterMatiere($nom,$description){
            $data=[];
            $matieres=$this->AfficherMatiere();
            $id=count($matieres)+1;
            if(trim($nom)=="" || trim($description)==""){
                throw new Error("tout les champs sont obligatoire");
            }
            else{
                $matieres[]=new Matiere($id,$nom,$description);
                foreach ($matieres as $matiere){
                    $data[]=[
                        "id"=>$matiere->getId(),
                        "nom"=>$matiere->getNom(),
                        "description"=>$matiere->getDescription(),
                    ];
                }

                file_put_contents(self::$dataFile,json_encode($data,JSON_PRETTY_PRINT));
            }
        }

}



?>
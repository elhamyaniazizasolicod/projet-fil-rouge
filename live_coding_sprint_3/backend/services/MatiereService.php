<?php
require_once __DIR__ .'/../DAO/MatiereDAO.php' ;
require_once __DIR__ .'/BaseService.php';

class MatiereService{
    private MatiereDAO $matiereDAO;

    public function __construct(){
    $this->matiereDAO= new MatiereDAO();
    }
    public function AfficherMatiere() {
    $matieres= $this->matiereDAO->AfficherMatiere();
        $data=[];
        foreach($matieres as $matiere){

            $data[]=[
                "id"=>$matiere->getId(),
                "nom"=>$matiere->getNom(),
                "description"=>$matiere->getDescription()
            ];
        }
         return $data;
    
        }
        
        public function ajouterMatiere(){
            $input=json_decode(file_get_contents("php://input"),true);

            try{
                http_response_code(200);
                $this->matiereDAO->ajouterMatiere($input["nom"],$input["description"]);
                return ["success" =>true, "message" => "Matiere est bien ajouter"];
            }catch(Error $e){
                http_response_code();
                return ["success"=>false,"message"=>$e->getMessage()];
            }
            
        }
}
?>
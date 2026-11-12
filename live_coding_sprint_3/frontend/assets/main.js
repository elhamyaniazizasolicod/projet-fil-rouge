const API_URL="http://localhost:8000/backend/api/api.php";
const tbody=document.getElementById("table-categories-body")
const form=document.getElementById("form-categorie")
const nom=document.getElementById("nom")
const description=document.getElementById("description")
const btnshowform=document.getElementById("btn-show-form")
const formSection=document.getElementById("section-form")
const btnSubmit=document.getElementById("btn-submit")
const btnTxt=document.getElementById("btn-text")
const spinner=document.getElementById("spinner")

function AfficherMatiere(){
fetch(API_URL)
.then(response=>{
    if(!response.ok){
        throw new Error("Impossible de charger les matières")
    }
    return response.json()
})
.then(matiere=>{
    tbody.innerHTML=""
    matiere.forEach(matiere => {
        tbody.innerHTML+=`
        <tr>
            <td class="p-4 text-sm font-semibold text-gray-500">${matiere.nom}</td>
            <td class="p-4 text-sm font-semibold text-gray-500">${matiere.description}</td>
        
        </tr>`
        
    });
})
.catch(error=>{
    showToast(error.message,"error")
})
}


function showToast(message,type="success"){
    const container=document.getElementById("toast-container")
    const toast=document.createElement("div")
    toast.classList="p-5 m-4 rounded "


    if(type=="success"){
        toast.classList.add("bg-green-500")

    }else{
          toast.classList.add("bg-red-500")
    }
    toast.textContent=message;
    container.appendChild(toast)
    setTimeout(()=>{
        toast.remove()

    },3000)
}

form.addEventListener("submit",function(e){
    e.preventDefault();
    const data={
        nom:nom.value,
        description:description.value
    }
    spinner.classList.remove("hidden")
    btnTxt.textContent="Enregistrer en cours..."
    btnSubmit.disabled=true

    fetch(API_URL,{
        method:"POST",
        headers:{
            "Content-Type":"application/json"

        },
        body:JSON.stringify(data)   
    })
    .then(async response=>{
        const result=await response.json()
        if(!response.ok){
            throw new Error(result.error || "Impossible d'ajouter la matière")
        }
        return result
    })
    .then(()=>{
        showToast("added success","success")
        form.reset()
        AfficherMatiere()
    }).catch(error=>{
        showToast(error.message,"error")
    }).finally(()=>{
        spinner.classList.add("hidden")
        btnSubmit.disabled=false
    })

})

btnshowform.addEventListener("click",function(){
    formSection.classList.remove("hidden")
    btnTxt.textContent="Enregistrer"
    btnSubmit.disabled=false

})
document.addEventListener("DOMContentLoaded",AfficherMatiere())
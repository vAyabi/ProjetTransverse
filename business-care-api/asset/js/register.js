//pour faire en sorte de ne pas afficher les deux forms en même temps
function toggleForm() {
    const typeCompte = document.getElementById('typeCompte').value;
    document.getElementById('entrepriseForm').style.display = 'none';
    document.getElementById('prestataireForm').style.display = 'none';
    
    if(typeCompte) {
        document.getElementById(typeCompte + 'Form').style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // récupérer l'ID de l'entreprise
    const entrepriseId = document.getElementById('dashboard-container').dataset.entrepriseId;
    console.log('ID entreprise:', entrepriseId);
    
    // permet de charger les détails de l'entreprise
    console.log('Chargement des détails de l\'entreprise...');
    fetch(`/business-care-api/api/entreprise/findOne.php?id=${entrepriseId}`)
        .then(response => {
            console.log('Statut de réponse entreprise:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Données entreprise:', data);
            if (data.status === 'success' && data.data) {
                document.getElementById('entreprise-nom').textContent = data.data.nom || '';
                document.getElementById('entreprise-formule').textContent = data.data.type_formule.charAt(0).toUpperCase() + data.data.type_formule.slice(1);
            } else {
                console.error('Format de réponse inattendu:', data);
            }
        })
        .catch(error => console.error('Erreur entreprise:', error));
    
    // permet de charger les salariés
    console.log('Chargement des salariés...');
    fetch(`/business-care-api/api/salarie/findByEntreprise.php?id_entreprise=${entrepriseId}`)
        .then(response => {
            console.log('Statut de réponse salariés:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Données salariés:', data);
            let count = 0;
            if (data.status === 'success') {
                if (Array.isArray(data.data)) {
                    count = data.data.length;
                } else if (data.data && Array.isArray(data.data.salaries)) {
                    count = data.data.salaries.length;
                }
                console.log('Nombre de salariés:', count);
            }
            document.getElementById('count-salaries').textContent = count;
        })
        .catch(error => console.error('Erreur salariés:', error));
    
    // permet de charger les événements
    console.log('Chargement des événements...');
    fetch(`/business-care-api/api/evenement/findByEntreprise.php?id_entreprise=${entrepriseId}`)
        .then(response => {
            console.log('Statut de réponse événements:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Données événements:', data);
            let count = 0;
            if (data.status === 'success') {
                const now = new Date();
                if (Array.isArray(data.data)) {
                    count = data.data.filter(event => new Date(event.date_debut) > now).length;
                } else if (data.data && Array.isArray(data.data.evenements)) {
                    count = data.data.evenements.filter(event => new Date(event.date_debut) > now).length;
                }
                console.log('Nombre d\'événements à venir:', count);
            }
            document.getElementById('count-evenements').textContent = count;
        })
        .catch(error => console.error('Erreur événements:', error));
    
    // puis charger les contrats
    console.log('Chargement des contrats...');
    fetch(`/business-care-api/api/Contrat/findByEntreprise.php?id_entreprise=${entrepriseId}`)
        .then(response => {
            console.log('Statut de réponse contrats:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Données contrats:', data);
            let contratActif = null;
            
            if (data.status === 'success') {
                if (Array.isArray(data.data)) {
                    contratActif = data.data.find(c => c.statut === 'actif');
                } else if (data.data && Array.isArray(data.data.contrats)) {
                    contratActif = data.data.contrats.find(c => c.statut === 'actif');
                } else if (data.data && data.data.statut === 'actif') {
                    contratActif = data.data;
                }
                
                console.log('Contrat actif trouvé:', contratActif);
            }
            
            if (contratActif) {
                document.getElementById('statut-contrat').innerHTML = '<span class="badge bg-success">Actif</span>';
                
                const facturationInfo = document.getElementById('facturation-info');
                if (facturationInfo) {
                    facturationInfo.classList.remove('d-none');
                    
                    document.getElementById('payment-type').textContent = contratActif.type_paiement.charAt(0).toUpperCase() + contratActif.type_paiement.slice(1);
                    document.getElementById('payment-amount').textContent = parseFloat(contratActif.montant_total).toFixed(2) + ' €';
                    document.getElementById('payment-start').textContent = new Date(contratActif.date_debut).toLocaleDateString('fr-FR');
                    document.getElementById('payment-end').textContent = new Date(contratActif.date_fin).toLocaleDateString('fr-FR');
                }
            } else {
                document.getElementById('statut-contrat').innerHTML = '<span class="badge bg-warning">Inactif</span>';
            }
        })
        .catch(error => console.error('Erreur contrats:', error));
});
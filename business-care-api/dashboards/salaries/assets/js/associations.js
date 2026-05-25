// Initialiser Stripe
const stripe = Stripe(stripePublicKey);
let card = null;

document.addEventListener('DOMContentLoaded', function() {
    // Gérer l'affichage initial
    document.querySelectorAll('select.type-select').forEach(select => {
        select.addEventListener('change', handleTypeChange);
        handleTypeChange({target: select}); // Déclencher l'événement au chargement
    });

    // Gérer les soumissions
    document.querySelectorAll('.participation-form').forEach(form => {
        form.addEventListener('submit', handleSubmit);
    });
});

// Gérer le changement de type
function handleTypeChange(event) {
    const select = event.target;
    const form = select.closest('form');
    const donSection = form.querySelector('[id^="don-section-"]');
    const materielSection = form.querySelector('[id^="materiel-section-"]');
    const type = select.value;

    // Cacher toutes les sections
    donSection.style.display = 'none';
    materielSection.style.display = 'none';

    // Afficher la section appropriée
    if (type === 'don_financier') {
        donSection.style.display = 'block';
        const cardElement = donSection.querySelector('[id^="card-element-"]');
        if (!card) {
            card = stripe.elements().create('card');
            card.mount(cardElement);
        }
    } else if (type === 'don_materiel') {
        materielSection.style.display = 'block';
    }
}

// Gérer la soumission du formulaire
async function handleSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const type = form.querySelector('.type-select').value;

    if (type === 'don_financier') {
        const amount = form.querySelector('[name="amount"]').value;
        const errorDiv = form.querySelector('.payment-error');
        const submitButton = form.querySelector('button[type="submit"]');

        if (!amount || amount < 0.50) {
            errorDiv.textContent = 'Le montant minimum du don est de 0.50€';
            return;
        }

        submitButton.disabled = true;

        try {
            const formData = new FormData();
            formData.append('type_participation', type);
            formData.append('id_association', form.querySelector('[name="id_association"]').value);
            formData.append('amount', amount);

            const response = await fetch('add_participation.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Erreur réseau');
            }

            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            const result = await stripe.confirmCardPayment(data.clientSecret, {
                payment_method: {card}
            });

            if (result.error) {
                errorDiv.textContent = result.error.message;
            } else {
                location.reload();
            }
        } catch (error) {
            errorDiv.textContent = error.message;
        } finally {
            submitButton.disabled = false;
        }
    } else {
        form.submit();
    }
}
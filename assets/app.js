import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// ----------------------- Jour/Nuit -------------------------------

let bout = document.getElementById("bouton");
let icon = document.getElementById("iconDeco");
let mode = document.body;

let save = localStorage.getItem("theme") || "light";
 
    mode.classList.remove("light", "dark");
    mode.classList.add(save);

    if (icon) {
        icon.src = save === "dark" 
        ? icon.dataset.dark 
        : icon.dataset.light;
    }

bout.addEventListener("click", function(e) {
    e.preventDefault();

    let theme = mode.classList.contains("light") ? "dark" : "light";

    mode.classList.remove("light", "dark");
    mode.classList.add(theme);
    

    localStorage.setItem("theme", theme);

    if (icon) {
        icon.src = theme === "dark" 
        ? icon.dataset.dark 
        : icon.dataset.light;
    }

});



// ----------------------- Effacer addFlash -------------------------------



document.querySelectorAll(".delete-btn").forEach(button => {
    
    button.addEventListener("click", (e) => {
        e.preventDefault();

        button.closest(".alert").style.display = "none";
    });
});


// ----------------------- Bouton +/- panier -------------------------------



document.querySelectorAll('.quantityM, .quantityP, .quantitySup').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        const parent = btn.closest('.panierArt');
        const opId = parent.dataset.opId;
        let action = '';

        if (btn.classList.contains('quantityP')) action = 'increase';
        else if (btn.classList.contains('quantityM')) action = 'decrease';
        else if (btn.classList.contains('quantitySup')) action = 'remove';

        const formData = new FormData();
        formData.append('action', action);

        const response = await fetch(`/panier/update/${opId}`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const data = await response.json();


        if (action === 'remove' || data.quantity === 0) {
            parent.remove();
        } else {
            parent.querySelector('.quantity').textContent = data.quantity;
            parent.querySelector('.line-total').textContent = data.lineTotal + ' €';
        }

        // Mettre à jour le récap
        document.querySelector('.recap .total-quantity').textContent = data.totalQuantity;
        document.querySelector('.recap .total-price').textContent = data.totalPrice + ' €';
    });
});


// ----------------------- Payer -------------------------------



// Assure-toi que Stripe.js est chargé
// <script src="https://js.stripe.com/v3/"></script>

const stripe = Stripe('pk_test_51Sh819LYNBUdrngnXI0fpgGTc3Q1TWQkvNbFGVKowrFmueJe1DFMq35zuf1I3GFJ37Rrwhnd98nqhOrlqTothtuv00URg6FM7l');

const payButton = document.querySelector('.paye');
if (payButton) {

    document.querySelector('.paye').addEventListener('click', async () => {
        try {
            // Crée la session côté serveur
            const response = await fetch('/panier/create-session', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json();

            // Redirige vers Stripe Checkout
            const result = await stripe.redirectToCheckout({
                sessionId: data.id
            });

            if (result.error) {
                // Affiche l'erreur s'il y en a
                alert(result.error.message);
            }
        } catch (err) {
            console.error(err);
            alert('Erreur lors de la création de la session Stripe.');
        }
    });

}


// ----------------------- Prestation accordéon -------------------------------


document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('.presta-title').forEach(title => {
        title.addEventListener('click', () => {
            const content = title.nextElementSibling;
            content.style.display = (content.style.display === 'block') ? 'none' : 'block';
        });
    });
});


// ----------------------- Etoiles Avis -------------------------------


const stars = document.querySelectorAll('.star-rating label');
const inputNote = document.querySelector('.note-hidden');

stars.forEach(star => {
  star.addEventListener('click', function() {
    const value = this.getAttribute('for').replace('star','');
    inputNote.value = value; // met à jour l'input Symfony caché
  });
});
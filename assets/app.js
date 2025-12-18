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



// ----------------------- Jour/Nuit -------------------------------



document.querySelectorAll(".delete-btn").forEach(button => {
    
    button.addEventListener("click", (e) => {
        e.preventDefault();

        button.closest(".alert").style.display = "none";
    });
});



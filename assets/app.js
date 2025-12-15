import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// -------------- Jour/Nuit ----------------

let bout = document.getElementById("bouton");
let icon = document.getElementById("icon");
let mode = document.body;

let save = localStorage.getItem("theme") || "light";
 
    mode.classList.remove("light", "dark");
    mode.classList.add(save);
    icon.src = save === "light" ? lampOn : lampOff;

bout.addEventListener("click", function(e) {
    e.preventDefault();
    let theme = mode.classList.contains("light") ? "dark" : "light";

    mode.classList.remove("light", "dark");
    mode.classList.add(theme);

    icon.src = theme === "light" ? lampOn : lampOff;

    localStorage.setItem("theme", theme);
});


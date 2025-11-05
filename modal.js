// Elemente holen
const modal = document.getElementById("cardModal");
const openBtn = document.getElementById("openModalBtn");
const closeBtn = document.querySelector(".close-btn");

// Öffnen
openBtn.onclick = function() {
  modal.style.display = "block";
};

// Schließen über X
closeBtn.onclick = function() {
  modal.style.display = "none";
};

// Schließen bei Klick ausserhalb
window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
};
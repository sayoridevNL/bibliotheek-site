// ==========================================
// RATING SYSTEEM LOGICA
// ==========================================

// We wachten tot de HTML volledig geladen is ('DOMContentLoaded') voordat we scripts uitvoeren.
// Dit voorkomt foutmeldingen omdat elementen (zoals knoppen) anders nog niet bestaan.
document.addEventListener('DOMContentLoaded', () => {
    
    // 1. SELECTEREN VAN ELEMENTEN
    // We koppelen JavaScript variabelen aan de HTML-elementen via hun ID.
    const openRatingBtn = document.getElementById("openRatingBtn"); // Knop "Rate & Review"
    const modal = document.getElementById("feedbackModal");         // De pop-up zelf
    const cancelBtn = document.getElementById("cancelBtn");         // Annuleer knop
    const submitBtn = document.getElementById("submitFeedback");    // Verzend knop
    const feedbackText = document.getElementById("feedbackText");   // Tekstvak
    const message = document.getElementById("message");             // Plek voor succes/fout bericht

    // We stoppen de 5 sterren in een Array (lijst) zodat we er makkelijk doorheen kunnen 'loopen'.
    const stars = [
        document.getElementById("star1"),
        document.getElementById("star2"),
        document.getElementById("star3"),
        document.getElementById("star4"),
        document.getElementById("star5")
    ];

    let currentRating = 0; // Houdt bij hoeveel sterren de gebruiker heeft aangeklikt.

    // 2. MODAL OPENEN EN SLUITEN
    // Als je op de knop klikt, zetten we de display van 'none' naar 'flex' (zichtbaar).
    openRatingBtn.addEventListener("click", () => {
        modal.style.display = "flex";
        resetStars(); // Sterren weer grijs maken bij openen.
    });

    function closeModal() {
        modal.style.display = "none"; // Verberg de pop-up
        feedbackText.value = "";      // Maak tekstvak leeg
        currentRating = 0;            // Reset score
    }

    // 3. STERREN KLEUREN (VISUEEL)
    // Deze functie kleurt de sterren goud op basis van de selectie.
    // Voorbeeld: Als rating 3 is, kleuren index 0, 1 en 2 goud. Index 3 en 4 blijven grijs.
    function setStarRating(rating) {
        currentRating = rating;
        stars.forEach((star, index) => {
            // Als de index van de ster kleiner is dan de rating, wordt hij goud.
            if (index < rating) {
                star.style.color = "#FFD700"; // Goud
            } else {
                star.style.color = "#e0e0e0"; // Grijs
            }
        });
    }

    // Functie om alles weer grijs te maken
    function resetStars() {
        stars.forEach(star => star.style.color = "#e0e0e0");
    }

    // We voegen aan ELKE ster een 'click event' toe.
    // Index begint bij 0, maar mensen tellen vanaf 1. Dus: index + 1.
    stars.forEach((star, index) => {
        star.addEventListener("click", () => setStarRating(index + 1));
    });

    cancelBtn.addEventListener("click", closeModal);

    // ==========================================
    // 4. DATA VERSTUREN NAAR PHP (FETCH API)
    // ==========================================
    submitBtn.addEventListener("click", () => {
        // Validatie: heeft de gebruiker wel sterren gekozen?
        if (currentRating === 0) {
            alert("Please select a star rating!");
            return; // Stop de functie hier.
        }

        // FETCH: Dit is de moderne manier om met de server te praten (AJAX).
        // We sturen data naar 'rate_boek.php' zonder dat de pagina herlaadt.
        
        

        fetch("rate_boek.php", { 
            method: "POST", // We sturen data (POST), we halen niet alleen op (GET).
            headers: {
                "Content-Type": "application/json" // We vertellen PHP: "Hier komt JSON data aan".
            },
            // JSON.stringify zet ons JavaScript object om naar een tekststring die PHP kan lezen.
            // BOEK_ID moet ergens in je HTML/PHP gedefinieerd zijn (vaak in een <script> tag).
            body: JSON.stringify({ 
                boek_id: BOEK_ID,         // Welk boek?
                rating: currentRating,    // Hoeveel sterren?
                feedback: feedbackText.value // Wat is de tekst?
            }) 
        })
        .then(res => res.json()) // Wacht op antwoord en zet het om van tekst naar JS object.
        .then(data => {
            // Hier verwerken we het antwoord van PHP (bijv: { success: true })
            if (data.success) { 
                // SUCCES: Toon groen bericht
                message.style.display = "block";
                message.style.backgroundColor = "#32a852";
                message.innerHTML = "Thanks for your review!";

                closeModal(); // Sluit pop-up

                // Wacht 3 seconden (3000ms) en herlaad dan de pagina.
                // Dit is nodig zodat het nieuwe gemiddelde (sterren) zichtbaar wordt op de pagina.
                setTimeout(() => { 
                    message.innerHTML = "";
                    message.style.display = "none";
                    location.reload(); // 🔁 Pagina verversen
                }, 3000);
            } else {
                alert("Something went wrong"); 
            }
        })
        .catch(err => {
            // Als de server helemaal niet bereikbaar is of PHP crasht.
            console.error(err);
            alert("Server error");
        });
    });

    // Sluit de modal als je 'naast' de pop-up klikt (op de donkere achtergrond)
    window.addEventListener("click", (e) => {
        if (e.target === modal) closeModal();
    });
});
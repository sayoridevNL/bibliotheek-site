// script.js

// ==========================================
// 1. STATUS VARIABELEN
// ==========================================
// Hier bewaren we de data die we straks ophalen.
let quizData = []; // De vragen en antwoorden
let allBooks = []; // Alle boeken uit de database

let currentStep = 0; // Bij welke vraag zijn we? (0 = eerste vraag)
let userAnswers = {}; // Hier slaan we op wat de gebruiker kiest (bijv: {0: 'thriller', 1: 'kort'})

document.addEventListener("DOMContentLoaded", () => {
    
    // 2. ELEMENTEN SELECTEREN
    // We pakken alle HTML onderdelen die we moeten aanpassen (teksten, knoppen, progress bars).
    const quizContent = document.getElementById("quizContent");
    const resultScreen = document.getElementById("resultScreen");
    const quizFooter = document.getElementById("quizFooter");
    const restartContainer = document.getElementById("restartContainer");
    const questionText = document.getElementById("questionText");
    const subtitleText = document.getElementById("subtitleText");
    const optionsGrid = document.getElementById("optionsGrid");
    const stepIndicator = document.getElementById("stepIndicator");
    const progressBar = document.getElementById("progressBar");
    const nextBtn = document.getElementById("nextBtn"); 
    const backBtn = document.getElementById("backBtn"); 

    // Veiligheidscheck: als knoppen missen, stop het script om errors te voorkomen.
    if (!nextBtn || !backBtn) return; 

    // ==========================================
    // 3. DATA OPHALEN (ASYNCHROON)
    // ==========================================
    // 'async' betekent dat deze functie even kan pauzeren ('await') om op data te wachten.
    async function initQuiz() {
        try {
            // STAP A: Haal de vragen op (uit een lokaal bestand)
            const response = await fetch('questions.json');
            if (!response.ok) throw new Error("Could not load data.");
            const fullData = await response.json();
            quizData = fullData.questions;

            // STAP B: Haal de boeken op (uit de PHP database API)
            // Dit is het bestand dat we eerder maakten (code 3 uit je vorige vraag).
            const bookData = await fetch('booksdata.php');
            allBooks = await bookData.json();

            // Als alles binnen is, start de eerste vraag.
            loadQuestion();
        } catch (error) {
            // Foutafhandeling als bestanden missen of server down is.
            console.error(error);
            questionText.innerText = "Error loading data.";
            subtitleText.innerText = "Please ensure you are running on a local server.";
        }
    }

    // HULPFUNCTIE: Vertaalt quiz-taal naar database-taal.
    // In de quiz heet het "shortread", in de database heet het "kort".
    function mapLength(length){
        if(length === "shortread") return "kort";
        if(length === "mediumread") return "middelmatig";
        if(length === "longread"|| length === "novel") return "lang"; 
    }

    // ==========================================
    // 4. QUIZ LOGICA (VRAAG LADEN)
    // ==========================================
    function loadQuestion() {
        // Zorg dat we het quiz scherm zien en niet het resultatenscherm
        quizContent.classList.remove("hidden");
        quizFooter.classList.remove("hidden");
        resultScreen.classList.add("hidden");
        restartContainer.classList.add("hidden");

        // Pak de data voor de huidige stap
        const data = quizData[currentStep];

        // Vul de teksten in de HTML
        questionText.innerText = data.question;
        subtitleText.innerText = data.subtitle;
        stepIndicator.innerText = `Question ${currentStep + 1} of ${quizData.length}`;

        // Bereken breedte van de blauwe balk bovenaan
        const progressPercent = ((currentStep + 1) / quizData.length) * 100;
        progressBar.style.width = `${progressPercent}%`;

        // Maak het grid leeg voordat we nieuwe opties toevoegen
        optionsGrid.innerHTML = "";

        // Loop door de opties van de huidige vraag en maak HTML kaarten
        data.options.forEach((opt) => {
            const card = document.createElement("div");
            card.classList.add("card");
            
            // Als de gebruiker dit al eerder had gekozen (bij teruggaan), markeer als geselecteerd.
            if (userAnswers[currentStep] === opt.value) {
                card.classList.add("selected");
            }

            card.innerHTML = `<div class="icon">${opt.icon}</div><div class="label">${opt.label}</div>`;
            
            // Klik event: sla het antwoord op
            card.onclick = () => selectOption(card, opt.value);
            optionsGrid.appendChild(card);
        });

        // Knoppen logica: Verberg 'Back' bij vraag 1. Verander 'Next' naar 'Finish' bij laatste vraag.
        backBtn.style.visibility = currentStep === 0 ? "hidden" : "visible";
        if (currentStep === quizData.length - 1){ 
            nextBtn.innerText = "Finish";
        } else {
            nextBtn.innerText = "Next →";
        }
        // Knop staat uit totdat je iets kiest.
        nextBtn.disabled = !userAnswers[currentStep];
    }

    // UI Functie: Visueel selecteren van een kaartje
    function selectOption(card, value) {
        const cards = optionsGrid.querySelectorAll(".card");
        cards.forEach(c => c.classList.remove("selected")); // Deselecteer de rest
        card.classList.add("selected"); // Selecteer de geklikte
        
        userAnswers[currentStep] = value; // Sla antwoord op in geheugen
        nextBtn.disabled = false; // Zet 'Volgende' knop aan
    }

    // ==========================================
    // 5. MATCHING ALGORITME (RESULTAAT)
    // ==========================================
    // Dit is het brein van de quiz. Hier berekenen we welk boek wint.
    
    

    function showResult() {
        // Verzamel de antwoorden van de gebruiker in een net object
        const answers = {
            genre: userAnswers[0],          // Vraag 1 was Genre
            length: mapLength(userAnswers[1]), // Vraag 2 was Lengte (vertaald naar NL)
            theme: userAnswers[3]           // Vraag 3 of 4 was Thema
        }

        console.log("User answers:", answers); // Handig voor debuggen in de console (F12)

        let bestBooks = []; // Lijst voor winnaars
        let highestScore = -1; // Hoogste score tot nu toe

        // Loop door ALLE boeken uit de database
        allBooks.forEach(book => { 
            let score = 0; // Elk boek begint op 0 punten

            // Data opschonen: spaties weg en alles kleine letters maken voor eerlijke vergelijking.
            const bookGenre = book.genre.trim().toLowerCase();
            const bookLength = book.lengte.trim().toLowerCase(); 
            // Thema's splitsen op komma's (omdat een boek meerdere thema's kan hebben)
            const bookThemes = book.thema.split(',').map(t => t.trim().toLowerCase());

            const userGenre = answers.genre.trim().toLowerCase(); 
            const userLength = answers.length.trim().toLowerCase();
            const userTheme = answers.theme.trim().toLowerCase();

            // PUNTENTELLING:
            // Genre is het belangrijkst (+8 punten)
            if(bookGenre === userGenre) score += 8;
            
            // Lengte is minder belangrijk (+2 punten)
            if(bookLength === userLength) score += 2;
            
            // Thema is gemiddeld belangrijk (+4 punten). We kijken of het thema IN de lijst staat.
            if(bookThemes.includes(userTheme)) score += 4; 

            console.log(`Book: ${book.naam} | Genre: ${book.genre} | Score: ${score}`);

            // VERGELIJKING:
            // Is dit boek beter dan de vorige hoogste score?
            if(score > highestScore){
                highestScore = score;
                bestBooks = [book]; // Reset de lijst, dit is de nieuwe nummer 1
            } else if(score === highestScore){
                bestBooks.push(book); // Gelijkspel! Voeg toe aan de lijst.
            }
        })

        // KIES WINNAAR:
        let bestBook;
        if(bestBooks.length > 0){
            // Als er meerdere winnaars zijn (gelijkspel), kies er willekeurig eentje.
            bestBook = bestBooks[Math.floor(Math.random() * bestBooks.length)];
        } else {
            bestBook = allBooks[0]; // Fallback: Als er niks gevonden is, pak het eerste boek.
        }
        
        console.log("Chosen book:", bestBook);

        // REDIRECT:
        // Stuur de gebruiker door naar de PHP detailpagina van het winnende boek.
        window.location.href = `boek.php?id=${bestBook.id}` 
    }

    // ==========================================
    // 6. EVENT LISTENERS (KNOPPEN)
    // ==========================================

    nextBtn.addEventListener("click",()=>{
        // Zolang we niet bij de laatste vraag zijn: ga eentje verder.
        if (currentStep < quizData.length - 1) {
            currentStep++;
            loadQuestion();
        } else {
            // Anders: bereken resultaat.
            showResult();
        }
    });

    backBtn.addEventListener("click", () => {
        if (currentStep > 0) {
            currentStep--;
            loadQuestion();
        }
    });

    document.getElementById("restartBtn").addEventListener("click", () => {
        currentStep = 0;
        userAnswers = {};
        loadQuestion();
    });

    // START DE QUIZ
    initQuiz();
});
let quizData = [];
let allBooks = [];
let currentStep = 0;
let userAnswers = {}; // Stores user choices: {0: "adventure", 1: "short", ...}

document.addEventListener("DOMContentLoaded", () => {
    // Select Elements based on your HTML
    const optionsGrid = document.getElementById("optionsGrid");
    const questionText = document.getElementById("questionText");
    const subtitleText = document.getElementById("subtitleText");
    const stepIndicator = document.getElementById("stepIndicator");
    const progressBar = document.getElementById("progressBar");
    const nextBtn = document.getElementById("nextBtn");
    const backBtn = document.getElementById("backBtn");

    async function initQuiz() {
        try {
            // 1. Fetch Questions
            const qRes = await fetch('questions.json');
            const qData = await qRes.json();
            quizData = qData.questions;

            // 2. Fetch Books from Database
            const bRes = await fetch('bookdata.php');
            allBooks = await bRes.json();

            console.log("Books loaded:", allBooks.length);

            // 3. Start Quiz
            loadQuestion();
        } catch (error) {
            console.error("Error initializing:", error);
            questionText.innerText = "Error loading content.";
            subtitleText.innerText = "Please ensure bookdata.php is reachable.";
        }
    }

    // Helper: Translate English quiz values to Dutch DB values
    function mapLengthToDutch(lengthValue) {
        if (lengthValue === "shortread") return "kort";
        if (lengthValue === "mediumread") return "middelmatig";
        if (lengthValue === "longread" || lengthValue === "novel") return "lang";
        return lengthValue;
    }

    function loadQuestion() {
        const data = quizData[currentStep];
        
        // Update Text
        questionText.innerText = data.question;
        subtitleText.innerText = data.subtitle;
        stepIndicator.innerText = `Question ${currentStep + 1} of ${quizData.length}`;

        // Update Progress Bar
        const progressPercent = ((currentStep + 1) / quizData.length) * 100;
        progressBar.style.width = `${progressPercent}%`;

        // Clear and Rebuild Options
        optionsGrid.innerHTML = "";
        data.options.forEach((opt) => {
            const card = document.createElement("div");
            card.classList.add("card");
            // Highlight if previously selected
            if (userAnswers[currentStep] === opt.value) card.classList.add("selected");

            card.innerHTML = `<div class="icon">${opt.icon}</div><div class="label">${opt.label}</div>`;
            
            // Click Event
            card.onclick = () => {
                // Remove selected class from others
                optionsGrid.querySelectorAll(".card").forEach(c => c.classList.remove("selected"));
                // Add to clicked
                card.classList.add("selected");
                // Save answer
                userAnswers[currentStep] = opt.value;
                // Enable Next button
                nextBtn.disabled = false;
            };
            optionsGrid.appendChild(card);
        });

        // Button States
        backBtn.style.visibility = currentStep === 0 ? "hidden" : "visible";
        
        // Change Text to "Finish" on last step
        if (currentStep === quizData.length - 1) {
            nextBtn.innerText = "Finish";
        } else {
            nextBtn.innerText = "Next →";
        }

        // Disable Next until selection is made (unless already answered)
        nextBtn.disabled = !userAnswers[currentStep];
    }

    function calculateResult() {
        // Prepare User Preferences
        // Assuming Order: Q1=Genre, Q2=Length, Q4=Theme (Last question)
        const selectedGenre = userAnswers[0] || "";
        const selectedLength = mapLengthToDutch(userAnswers[1] || "");
        // We take the last answer as the theme
        const selectedTheme = userAnswers[quizData.length - 1] || "";

        console.log("Looking for:", selectedGenre, selectedLength, selectedTheme);

        let bestBooks = [];
        let highestScore = -1;

        allBooks.forEach(book => {
            let score = 0;
            // Normalize DB strings
            const bGenre = (book.genre || "").toLowerCase().trim();
            const bLength = (book.lengte || "").toLowerCase().trim();
            const bThemes = (book.thema || "").toLowerCase();

            // Scoring Logic
            if (bGenre === selectedGenre.toLowerCase()) score += 10;
            if (bLength === selectedLength.toLowerCase()) score += 5;
            if (bThemes.includes(selectedTheme.toLowerCase())) score += 8;

            if (score > highestScore) {
                highestScore = score;
                bestBooks = [book]; // Reset list with new leader
            } else if (score === highestScore) {
                bestBooks.push(book); // Add to tie list
            }
        });

        // If no books match perfectly, fallback to all books to pick random
        if (bestBooks.length === 0) bestBooks = allBooks;

        // Pick random from best matches
        const finalBook = bestBooks[Math.floor(Math.random() * bestBooks.length)];
        
        return finalBook ? finalBook.id : null;
    }

    // Next / Finish Button Logic
    nextBtn.addEventListener("click", () => {
        if (currentStep < quizData.length - 1) {
            // Normal Next
            currentStep++;
            loadQuestion();
        } else {
            // FINISH CLICKED
            nextBtn.innerText = "Finding Book...";
            const bookId = calculateResult();
            
            if (bookId) {
                // REDIRECT TO BOEK.PHP
                window.location.href = `boek.php?id=${bookId}`;
            } else {
                alert("Geen boeken gevonden in de database!");
            }
        }
    });

    // Back Button Logic
    backBtn.addEventListener("click", () => {
        if (currentStep > 0) {
            currentStep--;
            loadQuestion();
        }
    });

    initQuiz();
});
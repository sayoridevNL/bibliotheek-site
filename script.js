// script.js

// 1. State Variables
// We initialize them as empty, waiting for the Fetch to fill them.
let quizData = []; 
let bookDatabase = {}; 

let currentStep = 0;
let userAnswers = {};

// 2. Select Elements (No changes here)
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

// Result Elements
const bookTitle = document.getElementById("bookTitle");
const bookAuthor = document.getElementById("bookAuthor");
const bookDescription = document.getElementById("bookDescription");
const bookCover = document.getElementById("bookCover");

// 3. FETCH DATA (Updated to handle the new structure)
async function initQuiz() {
    try {
        const response = await fetch('questions.json');
        if (!response.ok) throw new Error("Could not load data.");

        // We get the BIG object { questions: [...], books: {...} }
        const fullData = await response.json();

        // Assign the parts to our variables
        quizData = fullData.questions;
        bookDatabase = fullData.books;

        // Start the quiz
        loadQuestion();
    } catch (error) {
        console.error(error);
        questionText.innerText = "Error loading data.";
        subtitleText.innerText = "Please ensure you are running on a local server.";
    }
}

// 4. Main Logic
function loadQuestion() {
    quizContent.classList.remove("hidden");
    quizFooter.classList.remove("hidden");
    resultScreen.classList.add("hidden");
    restartContainer.classList.add("hidden");

    const data = quizData[currentStep];

    questionText.innerText = data.question;
    subtitleText.innerText = data.subtitle;
    stepIndicator.innerText = `Question ${currentStep + 1} of ${quizData.length}`;

    const progressPercent = ((currentStep + 1) / quizData.length) * 100;
    progressBar.style.width = `${progressPercent}%`;

    optionsGrid.innerHTML = "";

    data.options.forEach((opt) => {
        const card = document.createElement("div");
        card.classList.add("card");
        
        if (userAnswers[currentStep] === opt.value) {
            card.classList.add("selected");
        }

        card.innerHTML = `<div class="icon">${opt.icon}</div><div class="label">${opt.label}</div>`;
        card.onclick = () => selectOption(card, opt.value);
        optionsGrid.appendChild(card);
    });

    backBtn.style.visibility = currentStep === 0 ? "hidden" : "visible";
    nextBtn.innerText = currentStep === quizData.length - 1 ? "Finish" : "Next →";
    nextBtn.disabled = !userAnswers[currentStep];
}

function selectOption(card, value) {
    const cards = optionsGrid.querySelectorAll(".card");
    cards.forEach(c => c.classList.remove("selected"));
    card.classList.add("selected");
    
    userAnswers[currentStep] = value;
    nextBtn.disabled = false;
}

function showResult() {
    quizContent.classList.add("hidden");
    quizFooter.classList.add("hidden");
    resultScreen.classList.remove("hidden");
    restartContainer.classList.remove("hidden");

    // Logic: Look up the book based on the first answer (Genre)
    const genreChoice = userAnswers[0]; 
    
    // Access the 'bookDatabase' that we fetched from JSON
    const match = bookDatabase[genreChoice] || bookDatabase["fiction"];

    bookTitle.innerText = match.title;
    bookAuthor.innerText = match.author;
    bookDescription.innerText = match.desc;
    bookCover.style.background = match.color;
}

// 5. Event Listeners
nextBtn.addEventListener("click", () => {
    if (currentStep < quizData.length - 1) {
        currentStep++;
        loadQuestion();
    } else {
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

// START
initQuiz();
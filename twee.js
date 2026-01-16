// script.js

// 1. State Variables
// We initialize them as empty, waiting for the Fetch to fill them.
let quizData = []; 
let allBooks = [];

let currentStep = 0;
let userAnswers = {};

// load everythign in dom to make sure everything is loaded before
document.addEventListener("DOMContentLoaded", () => {
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
const nextBtn = document.getElementById("nextBtn"); if (!nextBtn) return; // niet handig voor errors, weet niet hoe ik anders moet fixen
const backBtn = document.getElementById("backBtn"); if (!backBtn) return; // ^^

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

        // load the quizdata
        quizData = fullData.questions;

        // load the books
        const bookData = await fetch('booksdata.php');
        allBooks = await bookData.json();
        // bookDatabase = fullData.books;

        // Start the quiz
        loadQuestion();
    } catch (error) {
        console.error(error);
        questionText.innerText = "Error loading data.";
        subtitleText.innerText = "Please ensure you are running on a local server.";
    }
}

// functie for connecting database data to quiz-answers 
function mapLength(length){
    if(length === "shortread") return "kort";
    if(length === "mediumread") return "middelmatig";
    if(length === "longread"|| length === "novel") return "lang"; // geen novel lengte in db dus lang
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
    // nextBtn.innerText = currentStep === quizData.length - 1 ? "Finish" : "Next →";
    if (currentStep === quizData.length - 1){ 
        nextBtn.innerText = "Finish";
    } else {
        nextBtn.innerText = "Next →";
    }
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
    const answers = {
        genre: userAnswers[0],
        length: mapLength(userAnswers[1]),
        // age optional, maybe add to db?
        theme: userAnswers[3]
    }

    console.log("User answers:", answers); // for debuggin

    //score books (matching answers get scores, highest scores)
    let bestBooks = [];
    let highestScore = -1;

    allBooks.forEach(book => { 
        let score = 0; // score starts at 0

        const bookGenre = book.genre.trim().toLowerCase();
        const bookLength = book.lengte.trim().toLowerCase(); 
        const bookThemes = book.thema.split(',').map(t => t.trim().toLowerCase());
        // bookthemes: since db has multiple themes in one table, split them by ','
        // tolowercase to catch errors or accidental Capital Letters

        const userGenre = answers.genre.trim().toLowerCase(); 
        const userLength = answers.length.trim().toLowerCase();
        const userTheme = answers.theme.trim().toLowerCase();

        if(bookGenre === userGenre) score += 8;
        if(bookLength === userLength) score += 2;
        if(bookThemes.includes(userTheme)) score += 4; // if theme includes answer

        console.log(`Book: ${book.naam} | Genre: ${book.genre} | Score: ${score}`);

        // if(book.genre === answers.genre) score +=3;
        // if(book.length === answers.length) score +=2;
        // if(book.theme === answers.theme) score +=1;

        if(score > highestScore){
            highestScore = score;
            bestBooks = [book]; // nieuwe lijst
        } else if(score === highestScore){
            bestBooks.push(book); // voeg toe aan lijst
        }
    })
    let bestBook;
    if(bestBooks.length > 0){
        bestBook = bestBooks[Math.floor(Math.random() * bestBooks.length)];
    } else {
        bestBook = allBooks[0]; // fallback
    }
    console.log("Chosen book:", bestBook);

    // if(!bestBook) bestBook = allBooks[0]; eerdere fallback, was error
    window.location.href = `boek.php?id=${bestBook.id}` 
    // for debugging: make ^ a comment so you can use console to see what's wrong

}

// 5. Event Listeners 

nextBtn.addEventListener("click",()=>{
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

});
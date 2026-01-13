document.getElementById("BoekForm").addEventListener("submit", function(event) {
            event.preventDefault();
        let genre = document.getElementById("genre").value;
        let length = document.getElementById("lengt").value;
        if (genre === "fiction") {
    if (length === "short") {
        recommendation = "The Old Man and the Sea - Ernest Hemingway (Een korte, krachtige klassieker over strijd en trots.)";
    } else if (length === "medium") {
        recommendation = "The Great Gatsby - F. Scott Fitzgerald (Een gemiddelde lengte, perfect voor een weekend lezen.)";
    } else if (length === "long") {
        recommendation = "The Lord of the Rings - J.R.R. Tolkien (Een episch, dik boek waar je weken in verdwijnt.)";
    }

// 2. NON-FICTION Logic
} else if (genre === "non-fiction") {
    if (length === "short") {
        recommendation = "We Should All Be Feminists - Chimamanda Ngozi Adichie (Zeer kort maar inspirerend essay.)";
    } else if (length === "medium") {
        recommendation = "Sapiens: A Brief History of Humankind - Yuval Noah Harari (Gemiddeld dik, zit vol interessante inzichten.)";
    } else if (length === "long") {
        recommendation = "Thinking, Fast and Slow - Daniel Kahneman (Een dikke pil vol psychologische diepgang.)";
    }

// 3. MYSTERY Logic
} else if (genre === "mystery") {
    if (length === "short") {
        recommendation = "The Hound of the Baskervilles - Arthur Conan Doyle (Een korter Sherlock Holmes mysterie.)";
    } else if (length === "medium") {
        recommendation = "Gone Girl - Gillian Flynn (Een moderne thriller van gemiddelde lengte met veel twists.)";
    } else if (length === "long") {
        recommendation = "The Girl with the Dragon Tattoo - Stieg Larsson (Een lang, complex en duister mysterie.)";
    }
}
        alert("Aanbevolen boek: " + recommendation);
        });
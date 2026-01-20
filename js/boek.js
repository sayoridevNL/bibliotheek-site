// logica voor rating 

document.addEventListener('DOMContentLoaded', () => {
    const openRatingBtn = document.getElementById("openRatingBtn");
    const modal = document.getElementById("feedbackModal");
    const cancelBtn = document.getElementById("cancelBtn");
    const submitBtn = document.getElementById("submitFeedback");
    const feedbackText = document.getElementById("feedbackText");
    const message = document.getElementById("message");

    const stars = [
        document.getElementById("star1"),
        document.getElementById("star2"),
        document.getElementById("star3"),
        document.getElementById("star4"),
        document.getElementById("star5")
    ];

    let currentRating = 0;

    openRatingBtn.addEventListener("click", () => {
        modal.style.display = "flex";
        resetStars();
    });

    function closeModal() {
        modal.style.display = "none";
        feedbackText.value = "";
        currentRating = 0;
    }

    function setStarRating(rating) {
        currentRating = rating;
        stars.forEach((star, index) => {
            if (index < rating) {
                star.style.color = "#FFD700";
            } else {
                star.style.color = "#e0e0e0";
            }
        });
    }

    function resetStars() {
        stars.forEach(star => star.style.color = "#e0e0e0");
    }

    stars.forEach((star, index) => {
        star.addEventListener("click", () => setStarRating(index + 1));
    });

    cancelBtn.addEventListener("click", closeModal);

    submitBtn.addEventListener("click", () => {
    if (currentRating === 0) {
        alert("Please select a star rating!");
        return;
    }

    fetch("rate_boek.php", { // lett: gets from rate_boek.php (makes sure data gets inserted in database)
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ // makes data into json so it can be read in rate_boek.php
            boek_id: BOEK_ID,
            rating: currentRating,
            feedback: feedbackText.value
        }) 
    })
    .then(res => res.json()) 
    .then(data => {
        if (data.success) { // if its success show success message
            message.style.display = "block";
            message.style.backgroundColor = "#32a852";
            message.innerHTML = "Thanks for your review!";

            closeModal();

            setTimeout(() => { // timeout for fade
                message.innerHTML = "";
                message.style.display = "none";
                location.reload(); // 🔁 pas NA de timeout
            }, 3000);
            // alert("Thanks for your review!");

            // closeModal();
            // location.reload(); // update gemiddelde rating
        } else {
            alert("Something went wrong"); 
        }
    })
    .catch(err => {
        console.error(err);
        alert("Server error");
    });
});

    window.addEventListener("click", (e) => {
        if (e.target === modal) closeModal();
    });
});
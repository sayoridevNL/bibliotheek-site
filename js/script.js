document.addEventListener('DOMContentLoaded', () => {
    const openRatingBtn = document.getElementById("openRatingBtn");
    const modal = document.getElementById("feedbackModal");
    const cancelBtn = document.getElementById("cancelBtn");
    const submitBtn = document.getElementById("submitFeedback");
    const feedbackText = document.getElementById("feedbackText");

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

        const data = {
            rating: currentRating,
            feedback: feedbackText.value
        };

        console.log("Feedback Submitted:", data);
        alert(`Thank you! You rated this ${currentRating} stars.`);
        closeModal();
    });

    window.addEventListener("click", (e) => {
        if (e.target === modal) closeModal();
    });
});
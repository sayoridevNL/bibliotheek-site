document.addEventListener('DOMContentLoaded', () => {
    let star1 = document.getElementById("star1");
    let star2 = document.getElementById("star2");
    let star3 = document.getElementById("star3");
    let star4 = document.getElementById("star4");
    let star5 = document.getElementById("star5");

    function setstar(rating) {
        let stars = [star1, star2, star3, star4, star5];
        for (let i = 0; i < stars.length; i++) {
            if (i < rating) {
                stars[i].style.color = "#FFD700"; // Gold color
            } else {
                stars[i].style.color = "#e0e0e0"; // Light gray
            }
        }
    }

    star1.addEventListener("click", function() { setstar(1); });
    star2.addEventListener("click", function() { setstar(2); });
    star3.addEventListener("click", function() { setstar(3); });
    star4.addEventListener("click", function() { setstar(4); });
    star5.addEventListener("click", function() { setstar(5); });
});
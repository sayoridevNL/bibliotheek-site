<?php
$db = "bibliotheek";
$host = "localhost";
$username = "root";
$password = "";

try {
  $conn = new PDO("mysql:host=$host;dbname=$db", $username, $password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  die("Connection failed: " . $e->getMessage());
}

$boek = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM boeken WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $boek = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $boek ? htmlspecialchars($boek['naam']) : 'Book Not Found'; ?> - Book Match</title>
    <link rel="stylesheet" href="css/boek.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

    <div class="main-container">
        
        <?php if ($boek): ?>
        <div class="header-section">
            <div class="pill-badge">
                📖 Perfect Match Found!
            </div>
            <h1>We Found Your Next Great Read</h1>
        </div>

        <div class="book-card">
            
            <div class="cover-section">
                <img src="<?php echo htmlspecialchars($boek['cover']); ?>" alt="<?php echo htmlspecialchars($boek['naam']); ?>">
            </div>

            <div class="info-section">
                <h2 class="book-title"><?php echo htmlspecialchars($boek['naam']); ?></h2>
                <div class="author">
                    <i class="fa fa-user-circle-o"></i> <?php echo htmlspecialchars($boek['schrijver']); ?>
                </div>

                <p class="description">
                    <?php echo nl2br(htmlspecialchars($boek['beschrijving'])); ?>
                </p>

                <div class="meta-tags">
                    <span class="tag"><i class="fa fa-tag"></i> <?php echo htmlspecialchars($boek['genre']); ?></span>
                    <span class="tag"><i class="fa fa-clock-o"></i> <?php echo htmlspecialchars($boek['lengte']); ?></span>
                </div>

                <div class="themes">
                    <span class="theme-label">Themes:</span>
                    <span class="theme-pill"><?php echo htmlspecialchars($boek['thema']); ?></span>
                </div>

                <div id="rating">
                    <p class="rate-label">Enjoyed the match?</p>
                    <button class="btn btn-secondary" id="openRatingBtn" style="width: auto; padding: 8px 16px; font-size: 0.85rem;">
                        ⭐ Rate & Review
                    </button>
                </div>

            </div>
        </div>

        <div class="action-buttons">
            <button class="btn btn-primary">📦 Rent This Book</button>
            <button class="btn btn-secondary" onclick="window.location.href='boeken.php'">🔄 Find Another Book</button>
            <button class="btn btn-tertiary" onclick="window.location.href='index.php'">🏠 Back to Home</button>
        </div>

        <?php else: ?>
        <div class="header-section">
            <h1>Oeps!</h1>
            <p><?php echo isset($_GET['id']) ? "Boek niet gevonden." : "Geen boek geselecteerd."; ?></p>
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="window.location.href='boeken.php'">Terug naar overzicht</button>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <div id="feedbackModal" class="modal-overlay">
        <div class="modal-content">
            <h3>How was this book?</h3>
            
            <div class="stars-container" style="margin-bottom: 15px;">
                <button class="star" id="star1">★</button>
                <button class="star" id="star2">★</button>
                <button class="star" id="star3">★</button>
                <button class="star" id="star4">★</button>
                <button class="star" id="star5">★</button>
            </div>

            <textarea id="feedbackText" placeholder="Share your feedback (optional)..."></textarea>
            
            <div class="modal-actions">
                <button id="cancelBtn" class="btn btn-tertiary">Cancel</button>
                <button id="submitFeedback" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </div>

    <script src="js/boek.js"></script>
</body>
</html>
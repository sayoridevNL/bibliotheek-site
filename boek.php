<?php
// database connectie
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
    // gets id from book that's been clicked
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM boeken WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $boek = $stmt->fetch(PDO::FETCH_ASSOC);

    // checks if book has been lent out
    $stmt2 = $conn->prepare("SELECT * FROM uitleningen WHERE boek_id = :id AND terug_op IS NULL");
    $stmt2->bindParam(':id', $id); // verbind boek id
    $stmt2->execute();
    $uitgeleend = $stmt2->fetch(PDO::FETCH_ASSOC);
}

if (isset($_POST['terugbrengen'])) { // when 'return/terugbrengen' btn is clicked
    // haalt eerst boek id en email van formulier
    $boek_id = $_POST['boek_id'];
    $email = trim($_POST['email']);

    // check of email matcht bij het boek
    $stmt = $conn->prepare("
        SELECT u.id
        FROM uitleningen u
        JOIN gebruikers g ON u.gebruiker_id = g.id
        WHERE u.boek_id = :boek_id
        AND u.terug_op IS NULL
        AND g.email = :email
    ");
    $stmt->execute([
        'boek_id' => $boek_id,
        'email' => $email
    ]);

    $uitlening = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($uitlening) {
        // update terug_op so book can be borrowed again
        $stmt = $conn->prepare("
            UPDATE uitleningen
            SET terug_op = NOW()
            WHERE id = :id
        ");
        $stmt->execute(['id' => $uitlening['id']]); 

        echo "<p id='successMsg' style='color: green; '>Boek succesvol teruggebracht!</p>
        <script>
            setTimeout(function() {
            document.getElementById('successMsg').style.display = 'none';
            }, 3000); // 3000 ms = 3 seconden
        </script>
        ";
    } else {
        echo "<p id='successMsg' style='color: red;'>Dit email heeft dit boek niet geleend.</p>
        <script>
            setTimeout(function() {
            document.getElementById('successMsg').style.display = 'none';
            }, 3000); // 3000 ms = 3 seconden
        </script>
        ";
    }
}

// boek lenen
if (isset($_POST['lenen-func'])) { // when 'borrow/leen' btn is clicked
    $boek_id = $_POST['boek_id']; // book id
    $naam = ($_POST['naam']); // name of user (input)
    $email = ($_POST['email']); // email of user (input)

    // checkt whether user exists already
    $stmt = $conn->prepare("SELECT id FROM gebruikers WHERE email = :email");
    $stmt->execute(['email' => $email]); // searches for email in database
    $gebruiker = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($gebruiker) { // if user exists, get user id
        $gebruiker_id = $gebruiker['id'];
    } else {
        // else 'insert' data in database to create new user
    $stmt = $conn->prepare("
        INSERT INTO gebruikers (naam, email)
        VALUES (:naam, :email)
    ");
    $stmt->execute([ // bind values
        'naam' => $naam,
        'email' => $email
    ]);

    $gebruiker_id = $conn->lastInsertId(); // get id of newly created user
    }

    // register borrowed book in database
    $stmt = $conn->prepare("
        INSERT INTO uitleningen (boek_id, gebruiker_id, uitgeleend_op)
        VALUES (:boek_id, :gebruiker_id, NOW())
    ");
    $stmt->execute([
        'boek_id' => $boek_id,
        'gebruiker_id' => $gebruiker_id
    ]);

    // goes back to book page so form resets
    header("Location: boek.php?id=$boek_id");
    exit;
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
            <?php 
            if (!$uitgeleend){
                echo "<form method='POST'>";
                echo "<button class='btn btn-primary loaning' name='lenen'>📦 Rent This Book</button>";
                echo "</form>";
            } else {
                echo "<form method='POST'>";
                echo "<button class='btn btn-primary loaning' name='terug'>📦 Return This Book</button>";
                echo "</form>";
            }
            ?>
            <!-- <button class="btn btn-primary">📦 Rent This Book</button> -->
            <button class="btn btn-secondary" onclick="window.location.href='boeken.php'">🔄 Find Another Book</button>
            <button class="btn btn-tertiary" onclick="window.location.href='index.php'">🏠 Back to Home</button>
        </div>
        <?php
        if (isset($_POST['lenen']))  {
            echo "
            <form method='POST'>
                <input type='hidden' name='boek_id' value='$id'>
                <label>
                    <input type='text' name='naam' placeholder='Your naam*' required>
                </label>

                <label>
                    <input type='email' name='email' placeholder='Your email*' required>
                </label>

                <label>
                    <input type='text' name='telefoon' placeholder='Your telefoonnummer'>
                </label>

                <button type='submit' name='lenen-func' class='leen-button'>
                    Rent this book
                </button>
            </form>
        ";
        } elseif (isset($_POST['terug'])) {
            echo "
            <form method='POST'>
                <input type='hidden' name='boek_id' value='$id'>
                <label>
                    <input type='text' name='naam' placeholder='Your naam*' required>
                </label>

                <label>
                    <input type='email' name='email' placeholder='Your email*' required>
                </label>

                <label>
                    <input type='text' name='telefoon' placeholder='Your telefoonnummer'>
                </label>

                <button type='submit' name='terugbrengen' class='leen-button'>
                    Return this book
                </button>
            </form>
        ";
        }
        ?>

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

    <div id="loanModal" class="modal-overlay">
    <div class="modal-content">
        <h3 id="loanTitle">Rent this book</h3>

        <form method="POST">
            <input type="hidden" name="boek_id" value="<?= $id ?>">
            <input type="hidden" name="action" id="loanAction">

            <input type="text" name="naam" placeholder="Your name*" required>
            <input type="email" name="email" placeholder="Your email*" required>
            <input type="text" name="telefoon" placeholder="Your phone">

            <div class="modal-actions">
                <button type="button" id="cancelLoan" class="btn btn-tertiary">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    Confirm
                </button>
            </div>
        </form>
    </div>
</div>

    <script src="js/boek.js"></script>
</body>
</html>
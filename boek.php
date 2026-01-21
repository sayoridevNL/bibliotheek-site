<?php
// ==========================================
// 1. CONFIGURATIE & DATABASE VERBINDING
// ==========================================
$db = "bibliotheek"; 
$host = "localhost";
$username = "root";
$password = "";

try {
    // VERBINDING MAKEN:
    // We gebruiken PDO. Dit is veiliger en moderner dan mysqli.
    $conn = new PDO("mysql:host=$host;dbname=$db", $username, $password);
    
    // FOUTMELDINGEN AANZETTEN:
    // Als er iets misgaat met een SQL-query, zorgt deze regel ervoor dat PHP een duidelijke foutmelding geeft
    // in plaats van een wit scherm.
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Als de verbinding mislukt (bijv. server down), vangen we de fout op en tonen we deze tekst.
    echo "Verbinding mislukt: " . $e->getMessage();
}

// Variabelen leeg initialiseren zodat we geen foutmeldingen krijgen als de pagina net laadt.
$boek = null;
$uitgeleend = null;

// ==========================================
// 2. PAGINA LADEN (GET REQUEST)
// ==========================================
// We kijken of er een ID in de URL staat (bijv. boek.php?id=5)
if (isset($_GET['id'])) {
    
    // BEVEILIGING (Sanitization):
    // We dwingen de input om een nummer te zijn met intval().
    // Dit voorkomt dat hackers rare tekst of code in de URL typen (SQL Injection).
    $id = intval($_GET['id']);

    // QUERY 1: BOEK GEGEVENS OPHALEN
    // We bereiden de query voor met een 'placeholder' (:id).
    $stmt = $conn->prepare("SELECT * FROM boeken WHERE id = :id");
    $stmt->bindParam(':id', $id); // Hier koppelen we het veilige ID aan de placeholder.
    $stmt->execute(); // Voer de query uit.
    
    // Haal het resultaat op als een associatieve array (bijv. $boek['naam']).
    $boek = $stmt->fetch(PDO::FETCH_ASSOC);

    // QUERY 2: CHECK OF HET BOEK IS UITGELEEND
    // We kijken in de tabel 'uitleningen'.
    // Een boek is 'weg' als er een rij is waar 'terug_op' nog NULL (leeg) is.
    $stmt2 = $conn->prepare("SELECT * FROM uitleningen WHERE boek_id = :id AND terug_op IS NULL");
    $stmt2->bindParam(':id', $id); 
    $stmt2->execute();
    $uitgeleend = $stmt2->fetch(PDO::FETCH_ASSOC); // Als dit 'true' is, is het boek momenteel weg.
}

// ==========================================
// 3. BOEK TERUGBRENGEN (POST REQUEST)
// ==========================================
// Dit stukje code draait alleen als iemand op de knop "Return this book" heeft geklikt.
if (isset($_POST['terugbrengen'])) { 
    $boek_id = $_POST['boek_id'];
    $email = trim($_POST['email']); // trim() haalt per ongeluk gekopieerde spaties weg.

    // VALIDATIE:
    // We controleren eerst of deze email wel echt degene is die DIT boek heeft geleend.
    // We gebruiken een JOIN om de tabel 'uitleningen' aan 'gebruikers' te koppelen.
    $stmt = $conn->prepare("
        SELECT u.id
        FROM uitleningen u
        JOIN gebruikers g ON u.gebruiker_id = g.id
        WHERE u.boek_id = :boek_id
        AND u.terug_op IS NULL      -- Het moet een actieve lening zijn
        AND g.email = :email        -- Het emailadres moet kloppen
    ");
    $stmt->execute([
        'boek_id' => $boek_id,
        'email' => $email
    ]);

    $uitlening = $stmt->fetch(PDO::FETCH_ASSOC);

    // Als $uitlening bestaat, klopt de combinatie boek + email.
    if ($uitlening) {
        // UPDATE QUERY:
        // We vullen de kolom 'terug_op' met de huidige tijd (NOW()).
        // In de database betekent dit: het boek is terug.
        $stmt = $conn->prepare("
            UPDATE uitleningen
            SET terug_op = NOW()
            WHERE id = :id
        ");
        $stmt->execute(['id' => $uitlening['id']]); 

        // FEEDBACK VOOR DE GEBRUIKER:
        // Een klein stukje JavaScript injecteren om een succesmelding te tonen.
        echo "<p id='successMsg' style='color: green;'>Boek succesvol teruggebracht!</p>
        <script>
            setTimeout(function() {
            document.getElementById('successMsg').style.display = 'none';
            }, 3000); 
        </script>";
        
        // STATUS UPDATE:
        // We zetten deze variabele op null zodat de knop hieronder verandert van 'Terugbrengen' naar 'Lenen'.
        $uitgeleend = null; 

    } else {
        // FOUTMELDING: Email klopt niet bij de lener.
        echo "<p id='successMsg' style='color: red;'>Dit email heeft dit boek niet geleend.</p>
        <script>
            setTimeout(function() {
            document.getElementById('successMsg').style.display = 'none';
            }, 3000); 
        </script>";
    }
}

// ==========================================
// 4. BOEK LENEN (POST REQUEST)
// ==========================================
// Dit draait als iemand het formulier invult om te lenen.
if (isset($_POST['lenen-func'])) { 
    $boek_id = $_POST['boek_id']; 
    $naam = ($_POST['naam']); 
    $email = ($_POST['email']); 

    // STAP 1: CHECK OF DE GEBRUIKER BESTAAT
    // We zoeken de gebruiker op basis van email.
    $stmt = $conn->prepare("SELECT id FROM gebruikers WHERE email = :email");
    $stmt->execute(['email' => $email]); 
    $gebruiker = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($gebruiker) { 
        // BESTAANDE GEBRUIKER: We pakken zijn/haar ID.
        $gebruiker_id = $gebruiker['id'];
    } else {
        // NIEUWE GEBRUIKER: We moeten deze persoon eerst toevoegen (INSERT).
        $stmt = $conn->prepare("
            INSERT INTO gebruikers (naam, email)
            VALUES (:naam, :email)
        ");
        $stmt->execute([ 
            'naam' => $naam,
            'email' => $email
        ]);
        // lastInsertId() geeft ons het ID dat de database net automatisch heeft aangemaakt.
        $gebruiker_id = $conn->lastInsertId(); 
    }

    // STAP 2: DE LENING REGISTREREN
    // We maken een nieuwe regel in de tabel 'uitleningen'.
    $stmt = $conn->prepare("
        INSERT INTO uitleningen (boek_id, gebruiker_id, uitgeleend_op)
        VALUES (:boek_id, :gebruiker_id, NOW())
    ");
    $stmt->execute([
        'boek_id' => $boek_id,
        'gebruiker_id' => $gebruiker_id
    ]);

    // STAP 3: HERLADEN (REDIRECT)
    // We sturen de gebruiker terug naar dezelfde pagina om het formulier te 'resetten'.
    // Dit voorkomt dat als je op F5 drukt, het boek per ongeluk 2x wordt geleend.
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
            <div class="pill-badge">📖 Perfect Match Found!</div>
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
            // Hier beslissen we welke knop we tonen.
            if (!$uitgeleend){
                // SITUATIE 1: Boek is er. Toon "Huur dit boek".
                echo "<form method='POST'>";
                echo "<button class='btn btn-primary loaning' name='lenen'>📦 Rent This Book</button>";
                echo "</form>";
            } else {
                // SITUATIE 2: Boek is weg. Toon "Breng terug".
                echo "<form method='POST'>";
                echo "<button class='btn btn-primary loaning' name='terug'>📦 Return This Book</button>";
                echo "</form>";
            }
            ?>
            <button class="btn btn-secondary" onclick="window.location.href='boeken.php'">🔄 Find Another Book</button>
            <button class="btn btn-tertiary" onclick="window.location.href='index.php'">🏠 Back to Home</button>
        </div>

        <?php
        // FORMULIEREN TONEN
        // Als op "Rent" is geklikt, tonen we de inputvelden voor lenen.
        if (isset($_POST['lenen']))  {
            echo "
            <form method='POST'>
                <input type='hidden' name='boek_id' value='$id'>
                <label><input type='text' name='naam' placeholder='Your naam*' required></label>
                <label><input type='email' name='email' placeholder='Your email*' required></label>
                <label><input type='text' name='telefoon' placeholder='Your telefoonnummer'></label>
                <button type='submit' name='lenen-func' class='leen-button'>Rent this book</button>
            </form>
            ";
        // Als op "Return" is geklikt, tonen we de inputvelden voor terugbrengen.
        } elseif (isset($_POST['terug'])) {
            echo "
            <form method='POST'>
                <input type='hidden' name='boek_id' value='$id'>
                <label><input type='text' name='naam' placeholder='Your naam*' required></label>
                <label><input type='email' name='email' placeholder='Your email*' required></label>
                <label><input type='text' name='telefoon' placeholder='Your telefoonnummer'></label>
                <button type='submit' name='terugbrengen' class='leen-button'>Return this book</button>
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
</div>
    <script src="js/boek.js"></script>
</body>
</html>
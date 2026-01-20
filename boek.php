<?php
// --- 1. DATABASE CONNECTION ---
$host = "localhost";
$db   = "bibliotheek";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// --- 2. HANDLE FORM LOGIC ---

// Handle Borrowing (Final Step)
if (isset($_POST['lenen-func'])) {
    $boek_id = $_POST['boek_id'];
    $naam = $_POST['naam'];
    $email = $_POST['email'];

    // Check/Create User
    $stmt = $conn->prepare("SELECT id FROM gebruikers WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $gebruiker = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($gebruiker) {
        $gebruiker_id = $gebruiker['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO gebruikers (naam, email) VALUES (:naam, :email)");
        $stmt->execute(['naam' => $naam, 'email' => $email]);
        $gebruiker_id = $conn->lastInsertId();
    }

    // Insert Loan
    $stmt = $conn->prepare("INSERT INTO uitleningen (boek_id, gebruiker_id, uitgeleend_op) VALUES (:b_id, :g_id, NOW())");
    $stmt->execute(['b_id' => $boek_id, 'g_id' => $gebruiker_id]);

    header("Location: boek.php?id=$boek_id&status=success");
    exit;
}

// Handle Return (Final Step)
$returnMessage = "";
if (isset($_POST['terugbrengen'])) {
    $boek_id = $_POST['boek_id'];
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT u.id FROM uitleningen u JOIN gebruikers g ON u.gebruiker_id = g.id WHERE u.boek_id = :b_id AND u.terug_op IS NULL AND g.email = :email");
    $stmt->execute(['b_id' => $boek_id, 'email' => $email]);
    $uitlening = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($uitlening) {
        $stmt = $conn->prepare("UPDATE uitleningen SET terug_op = NOW() WHERE id = :id");
        $stmt->execute(['id' => $uitlening['id']]);
        $returnMessage = "Boek succesvol teruggebracht!";
        $returnStatus = "success";
    } else {
        $returnMessage = "Dit emailadres heeft dit boek niet geleend.";
        $returnStatus = "error";
    }
}

// --- 3. FETCH BOOK DATA ---
$boek = null;
$uitgeleend = false;

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM boeken WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $boek = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($boek) {
        // Check availability
        $stmt2 = $conn->prepare("SELECT * FROM uitleningen WHERE boek_id = :id AND terug_op IS NULL");
        $stmt2->execute(['id' => $id]);
        $uitgeleend = $stmt2->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $boek ? htmlspecialchars($boek['naam']) : 'Boek niet gevonden'; ?></title>
    <link rel="stylesheet" href="css/boek.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        /* Specific tweaks for the interaction section */
        .form-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            border: 1px solid #e9ecef;
            animation: fadeIn 0.3s ease-in;
        }
        .form-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        .status-msg {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }
        .status-success { background-color: #d4edda; color: #155724; }
        .status-error { background-color: #f8d7da; color: #721c24; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="main-container" style="max-width: 900px; margin: 0 auto; padding: 40px 20px;">
        
        <?php if ($boek): ?>
        
        <div class="header-section" style="text-align: center; margin-bottom: 40px;">
            <div class="pill-badge" style="background: #e3f2fd; color: #0984e3; padding: 8px 16px; border-radius: 50px; display: inline-block; font-weight: 600; font-size: 0.9rem; margin-bottom: 15px;">
                📖 Perfect Match Found!
            </div>
            <h1 style="font-family: 'Playfair Display', serif; font-size: 2.5rem; color: #2d3436; margin: 0;">
                We Found Your Next Great Read
            </h1>
            
            <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                <div class="status-msg status-success" style="margin-top: 20px;">🎉 Boek succesvol geleend! Veel leesplezier.</div>
            <?php endif; ?>
            <?php if (!empty($returnMessage)): ?>
                <div class="status-msg <?php echo ($returnStatus == 'success') ? 'status-success' : 'status-error'; ?>" style="margin-top: 20px;">
                    <?php echo $returnMessage; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="book-card" style="background: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); padding: 40px; display: flex; gap: 40px; flex-wrap: wrap;">
            
            <div class="cover-section" style="flex: 0 0 300px;">
                <img src="<?php echo !empty($boek['cover']) ? htmlspecialchars($boek['cover']) : 'img/default_cover.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($boek['naam']); ?>" 
                     style="width: 100%; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.12);">
            </div>

            <div class="info-section" style="flex: 1; min-width: 300px;">
                <h2 class="book-title" style="font-family: 'Playfair Display', serif; font-size: 2rem; margin-top: 0; margin-bottom: 10px;">
                    <?php echo htmlspecialchars($boek['naam']); ?>
                </h2>
                <div class="author" style="color: #636e72; font-size: 1.1rem; margin-bottom: 20px;">
                    <i class="fa fa-user-circle-o"></i> <?php echo htmlspecialchars($boek['schrijver']); ?>
                </div>

                <p class="description" style="line-height: 1.8; color: #4a5459; margin-bottom: 25px;">
                    <?php echo nl2br(htmlspecialchars($boek['beschrijving'])); ?>
                </p>

                <div class="meta-tags" style="display: flex; gap: 10px; margin-bottom: 25px;">
                    <span class="tag" style="background: #f1f2f6; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; color: #57606f;">
                        <i class="fa fa-tag"></i> <?php echo htmlspecialchars($boek['genre']); ?>
                    </span>
                    <span class="tag" style="background: #f1f2f6; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; color: #57606f;">
                        <i class="fa fa-clock-o"></i> <?php echo htmlspecialchars($boek['lengte']); ?>
                    </span>
                </div>

                <div class="themes" style="margin-bottom: 30px;">
                    <span class="theme-label" style="font-weight: 600; margin-right: 10px;">Thema:</span>
                    <span class="theme-pill" style="color: #0984e3;"><?php echo htmlspecialchars($boek['thema']); ?></span>
                </div>
                
                <div id="rating">
                    <button class="btn" id="openRatingBtn" style="background: none; border: 2px solid #dfe6e9; padding: 8px 16px; border-radius: 50px; cursor: pointer; color: #636e72; font-weight: 600; transition: all 0.2s;">
                        ⭐ Rate & Review
                    </button>
                </div>
            </div>
        </div>

        <div class="action-buttons" style="margin-top: 40px; text-align: center;">
            
            <?php 
            // 1. If form is active, show form. 
            // 2. If not active, show initial buttons.
            
            if (isset($_POST['lenen']) && !$uitgeleend) {
                // --- BORROW FORM ---
                echo "<div class='form-container'>";
                echo "<h3>Vul je gegevens in om te lenen:</h3>";
                echo "<form method='POST'>";
                echo "<input type='hidden' name='boek_id' value='$id'>";
                echo "<input type='text' name='naam' placeholder='Jouw naam' required>";
                echo "<input type='email' name='email' placeholder='Jouw email' required>";
                echo "<div style='display:flex; gap:10px; justify-content:center; margin-top:10px;'>";
                echo "<a href='boek.php?id=$id' class='btn' style='background:#ccc; color:white; padding:12px 24px; text-decoration:none; border-radius:8px;'>Annuleren</a>";
                echo "<button type='submit' name='lenen-func' class='btn btn-primary' style='background:#0984e3; color:white; border:none; padding:12px 24px; border-radius:8px; cursor:pointer; font-weight:bold;'>Bevestig Lenen</button>";
                echo "</div>";
                echo "</form>";
                echo "</div>";

            } elseif (isset($_POST['terug']) && $uitgeleend) {
                // --- RETURN FORM ---
                echo "<div class='form-container'>";
                echo "<h3>Wie brengt dit boek terug?</h3>";
                echo "<form method='POST'>";
                echo "<input type='hidden' name='boek_id' value='$id'>";
                echo "<input type='email' name='email' placeholder='Email gebruikt bij lenen' required>";
                echo "<div style='display:flex; gap:10px; justify-content:center; margin-top:10px;'>";
                echo "<a href='boek.php?id=$id' class='btn' style='background:#ccc; color:white; padding:12px 24px; text-decoration:none; border-radius:8px;'>Annuleren</a>";
                echo "<button type='submit' name='terugbrengen' class='btn' style='background:#636e72; color:white; border:none; padding:12px 24px; border-radius:8px; cursor:pointer; font-weight:bold;'>Bevestig Terugbrengen</button>";
                echo "</div>";
                echo "</form>";
                echo "</div>";

            } else {
                // --- INITIAL BUTTONS ---
                
                // Borrow/Return Toggle
                echo "<form method='POST' style='display:inline;'>";
                if (!$uitgeleend) {
                    echo "<button type='submit' name='lenen' class='btn btn-primary' style='background:#0984e3; color:white; border:none; padding:14px 28px; border-radius:50px; font-size:1rem; font-weight:bold; cursor:pointer; margin: 10px; box-shadow: 0 4px 15px rgba(9,132,227,0.3);'>📦 Rent This Book</button>";
                } else {
                    echo "<button type='submit' name='terug' class='btn' style='background:#fab1a0; color:#d63031; border:none; padding:14px 28px; border-radius:50px; font-size:1rem; font-weight:bold; cursor:pointer; margin: 10px;'>↩️ Return Book</button>";
                }
                echo "</form>";

                // --- NEW BUTTONS ADDED HERE ---
                
                // Take the Quiz Button
                echo "<button class='btn btn-secondary' onclick=\"window.location.href='quiz.html'\" style='background:white; border:2px solid #dfe6e9; color:#2d3436; padding:14px 28px; border-radius:50px; font-size:1rem; font-weight:bold; cursor:pointer; margin: 10px;'>🧩 Take the Quiz</button>";
                
                // Search Button
                echo "<button class='btn btn-secondary' onclick=\"window.location.href='boeken.php'\" style='background:white; border:2px solid #dfe6e9; color:#2d3436; padding:14px 28px; border-radius:50px; font-size:1rem; font-weight:bold; cursor:pointer; margin: 10px;'>🔍 Search</button>";

                // Home Button (Kept as tertiary option)
                echo "<button class='btn btn-tertiary' onclick=\"window.location.href='index.html'\" style='background:transparent; color:#636e72; border:none; padding:14px 28px; font-size:1rem; cursor:pointer; margin: 10px;'>🏠 Home</button>";
            }
            ?>
        </div>

        <?php else: ?>
        
        <div class="header-section" style="text-align: center; margin-top: 100px;">
            <h1>Oeps!</h1>
            <p><?php echo isset($_GET['id']) ? "We konden dit boek niet vinden." : "Geen boek geselecteerd."; ?></p>
            <div class="action-buttons" style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="window.location.href='quiz.html'" style="background:#0984e3; color:white; border:none; padding:12px 24px; border-radius:50px; cursor:pointer;">Terug naar Quiz</button>
                <button class="btn btn-secondary" onclick="window.location.href='boeken.php'" style="background:white; border:2px solid #dfe6e9; color:#2d3436; padding:12px 24px; border-radius:50px; cursor:pointer; margin-left: 10px;">🔍 Search</button>
            </div>
        </div>
        
        <?php endif; ?>

    </div>

    <div id="feedbackModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
        <div class="modal-content" style="background:white; padding:30px; border-radius:16px; width:90%; max-width:400px; text-align:center;">
            <h3>How was this book?</h3>
            <div class="stars-container" style="margin: 20px 0; font-size: 2rem; color: #dfe6e9;">
                <span class="star" style="cursor:pointer;">★</span>
                <span class="star" style="cursor:pointer;">★</span>
                <span class="star" style="cursor:pointer;">★</span>
                <span class="star" style="cursor:pointer;">★</span>
                <span class="star" style="cursor:pointer;">★</span>
            </div>
            <textarea id="feedbackText" placeholder="Share your feedback (optional)..." style="width:100%; height:80px; margin-bottom:20px; padding:10px; border-radius:8px; border:1px solid #dfe6e9;"></textarea>
            <div class="modal-actions" style="display:flex; justify-content:space-between;">
                <button id="cancelBtn" onclick="document.getElementById('feedbackModal').style.display='none'" style="background:none; border:none; cursor:pointer; color:#636e72;">Cancel</button>
                <button id="submitFeedback" style="background:#0984e3; color:white; border:none; padding:8px 20px; border-radius:6px; cursor:pointer;">Submit</button>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('feedbackModal');
        const openBtn = document.getElementById('openRatingBtn');
        if(openBtn) {
            openBtn.addEventListener('click', () => {
                modal.style.display = 'flex';
            });
        }
        
        // Star Interaction
        const stars = document.querySelectorAll('.star');
        stars.forEach((star, index) => {
            star.addEventListener('click', () => {
                stars.forEach((s, i) => {
                    if (i <= index) s.style.color = '#fdcb6e'; // Gold
                    else s.style.color = '#dfe6e9'; // Grey
                });
            });
        });
    </script>
</body>
</html>
<?php
// connectie met database hier

$db = "bibliotheek"; // naam van database
$host = "localhost";
$username = "root";
$password = "";

try {
  $conn = new PDO("mysql:host=$host;dbname=$db", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  echo "Connected successfully"; // kan later weg als er iedereen connectie heeft
} catch(PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <!-- <script src="script.js"></script> -->
    <script src="twee.js"></script> <!-- goede script aanvragen -->
    <link rel="stylesheet" href="css/boek.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        button{
    background-color: #007BFF;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1em;
    font-weight: bold;
}
    </style>
</head>
<body>

    <div class="boek-detail">
        <?php
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']); // gets id from book that's been clicked

            $stmt = $conn->prepare("SELECT * FROM boeken WHERE id = :id"); 
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $boek = $stmt->fetch(PDO::FETCH_ASSOC); // gets book from database

            if (isset($_POST['terugbrengen'])) { // when 'return/terugbrengen' btn is clicked
            // haalt eerd boek id en email van formulier
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

                echo "<p style='color: green;'>Boek succesvol teruggebracht!</p>";
            } else {
                echo "<p style='color: red;'>Dit email heeft dit boek niet geleend.</p>";
            }
        }


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

            if ($boek) { // if book exists
            
                // checkt of boek al is uitgeleend
                $stmt2 = $conn->prepare("SELECT * FROM uitleningen WHERE boek_id = :id AND terug_op IS NULL");
                $stmt2->bindParam(':id', $id); // verbind boek id
                $stmt2->execute();
                $uitgeleend = $stmt2->fetch(PDO::FETCH_ASSOC);

                echo "<a href='boeken.php' class='terug-button'>&larr; Terug</a>";
                echo "<div class='boek-cont'>";

                echo "<div class='boek-detail'>";
                echo "<img src='" . htmlspecialchars($boek['cover']) . "' alt='" . htmlspecialchars($boek['naam']) . "'>";
                echo "</div>";
                echo "<div class='boek-info'>";
                echo "<h1>" . htmlspecialchars($boek['naam']) . "</h1>";
                echo "<p style='color: grey;'>" . htmlspecialchars($boek['schrijver']) . "</p>";
                echo "<p>" . nl2br(htmlspecialchars($boek['beschrijving'])) . "</p>";
                echo "<div class='tabs-cont'>";
                echo "<p class='small-tabs'><i class='fa fa-tag' aria-hidden='true'></i>" . " " . htmlspecialchars($boek['genre']) . "</p>";
                echo "<p class='small-tabs'><i class='fa fa-clock-o' aria-hidden='true'></i>" . " " . htmlspecialchars($boek['lengte']) . "</p>";
                echo "</div>";
                echo "<h5>Thema:</h5>";
                echo "<p>" . htmlspecialchars($boek['thema']) . "</p>";
                echo "</div>";
                echo "</div>";

                if(!$uitgeleend){
                    echo "<form method='POST'>";
                    echo "<button class='leen-button' name='lenen'>Leen boek</button>";
                    echo "</form>";
                } else {
                    echo "<form method='POST'>";
                    echo "<button class='leen-button' name='terug'>Breng boek terug</button>";
                    echo "</form>";
                }
            } else { // if book doesn't exist
                echo "<p>Boek niet gevonden.</p>"; // error msgs
            }
        } else {
            echo "<p>Geen boek geselecteerd.</p>"; // error msgs
        }

        if (isset($_POST['lenen']))  {
            echo "
            <form method='POST'>
                <input type='hidden' name='boek_id' value='$id'>
                <label>
                    <input type='text' name='naam' placeholder='Jouw naam' required>
                </label>

                <label>
                    <input type='email' name='email' placeholder='Jouw email' required>
                </label>

                <label>
                    <input type='text' name='telefoon' placeholder='Jouw telefoonnummer'>
                </label>

                <button type='submit' name='lenen-func' class='leen-button'>
                    Leen dit boek
                </button>
            </form>
        ";
        } elseif (isset($_POST['terug'])) {
            echo "
            <form method='POST'>
                <input type='hidden' name='boek_id' value='$id'>
                <label>
                    <input type='text' name='naam' placeholder='Jouw naam' required>
                </label>

                <label>
                    <input type='email' name='email' placeholder='Jouw email' required>
                </label>

                <label>
                    <input type='text' name='telefoon' placeholder='Jouw telefoonnummer'>
                </label>

                <button type='submit' name='terugbrengen' class='leen-button'>
                    Breng dit boek terug
                </button>
            </form>
        ";
        }
        ?>
    </div>
    
    
</body>
</html>
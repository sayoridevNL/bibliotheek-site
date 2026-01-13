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
    <script src="script.js"></script>
    <link rel="stylesheet" href="boek.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

    <div class="boek-detail">
        <?php
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT * FROM boeken WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $boek = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($boek) {
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
            } else {
                echo "<p>Boek niet gevonden.</p>";
            }
        } else {
            echo "<p>Geen boek geselecteerd.</p>";
        }
        ?>
    </div>
    
    
</body>
</html>
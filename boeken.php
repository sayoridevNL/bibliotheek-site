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
    <title>Boeken</title>
    <link rel="stylesheet" href="boek.css">
</head>
<body>
  <?php
        $stmt = $conn->prepare("SELECT * FROM boeken");
        $stmt->execute();

        echo "<div class='boeken-container'>";
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // als er geen resultaten zijn
        if(count($result) === 0) {
          echo "<p>Geen boeken gevonden.</p>";
        }
        
        foreach ($result as $row) {
          echo "<div class='boek'>";
          echo "<a href='boek.php?id=" . $row['id'] . "' class='boek-link'>";
          echo "<img src='" . htmlspecialchars($row['cover']) . "' alt='" . htmlspecialchars($row['naam']) . "'>";
          echo "<h2>" . htmlspecialchars($row['naam']) . "</h2>";
          echo "</a>";
          echo "<p>Auteur: " . htmlspecialchars($row['schrijver']) . "</p>";
          echo "<div class='tabs-cont'>";
          echo "<p class='small-tabs'> " . htmlspecialchars($row['genre']) . "</p>";
          echo "<p class='small-tabs'> " . htmlspecialchars($row['lengte']) . "</p>";
          echo "</div>";
          echo "</div>";
        }
        ?>
        
</body>
</html>
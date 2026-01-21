<?php
// ==========================================
// 1. DATABASE CONNECTIE
// ==========================================
$db = "bibliotheek"; 
$host = "localhost";
$username = "root";
$password = "";

try {
    // Verbinding maken met PDO
    $conn = new PDO("mysql:host=$host;dbname=$db", $username, $password);
  
    // Als er een fout is, stop en geef een melding (EXCEPTION)
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  
    // LET OP: Deze echo hieronder zie je op het scherm. 
    // Als de site af is, moet je deze regel weghalen, anders staat er boven je site "Connected successfully".
    echo "Connected successfully"; 
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
    <link rel="stylesheet" href="css/boek.css">
</head>
<body>
  <?php
        // ==========================================
        // 2. ALLE BOEKEN OPHALEN
        // ==========================================
        
        // We bereiden een query voor die ALLES (*) selecteert uit de tabel 'boeken'.
        $stmt = $conn->prepare("SELECT * FROM boeken");
        
        // Voer de query uit op de database.
        $stmt->execute();

        echo "<div class='boeken-container'>";
        
        // HAAL DATA OP:
        // fetchAll() pakt alle rijen tegelijk en stopt ze in de variabele $result.
        // Elke rij is een array met gegevens (id, naam, schrijver, etc.).
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ==========================================
        // 3. CHECK OP RESULTATEN
        // ==========================================
        // Als de lijst leeg is (lengte is 0), tonen we een melding.
        if(count($result) === 0) {
            echo "<p>Geen boeken gevonden.</p>";
        }
        
        // ==========================================
        // 4. DE LUS (LOOP)
        // ==========================================
        // We gaan met een 'foreach' door elk boek in de lijst heen.
        // Bij elke slag van de lus is $row één boek.
        foreach ($result as $row) {
            echo "<div class='boek'>";
            
            // DYNAMISCHE LINK:
            // We plakken het ID van het boek achter de link: boek.php?id=1, id=2, enz.
            echo "<a href='boek.php?id=" . $row['id'] . "' class='boek-link'>";
            
            // VEILIGHEID:
            // htmlspecialchars() is cruciaal. Als een hacker een boeknaam ' <script>hack</script> ' noemt,
            // zorgt deze functie dat het gewoon als tekst wordt getoond en niet wordt uitgevoerd.
            echo "<img src='" . htmlspecialchars($row['cover']) . "' alt='" . htmlspecialchars($row['naam']) . "'>";
            echo "<h2>" . htmlspecialchars($row['naam']) . "</h2>";
            echo "</a>";
            
            echo "<p>Auteur: " . htmlspecialchars($row['schrijver']) . "</p>";
            
            // Extra info blokjes
            echo "<div class='tabs-cont'>";
            echo "<p class='small-tabs'> " . htmlspecialchars($row['genre']) . "</p>";
            echo "<p class='small-tabs'> " . htmlspecialchars($row['lengte']) . "</p>";
            echo "</div>";
            
            echo "</div>"; // Einde van de kaart voor dit boek
        }
        echo "</div>"; // Einde van de grote container
        ?>
        
</body>
</html>
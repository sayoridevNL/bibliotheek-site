<?php
// Database verbinding
$db = "bibliotheek"; 
$host = "localhost";
$username = "root";
$password = "";

try {
  $conn = new PDO("mysql:host=$host;dbname=$db", $username, $password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
}

// ==========================================
// COMPLEXE QUERY UITLEG
// ==========================================
$stmt = $conn->prepare("
    SELECT 
        b.cover,
        b.id,
        b.naam,
        b.schrijver,
        b.cover,
        -- Bereken het gemiddelde van de ratings, afgerond op 1 decimaal (bijv. 4.5)
        ROUND(AVG(r.rating), 1) AS avg_rating,
        -- Tel hoeveel ratings er in totaal zijn voor dit boek
        COUNT(r.id) AS rating_count
    FROM boeken b
    -- We verbinden de tabel 'boeken' met 'boek_ratings'
    JOIN boek_ratings r ON b.id = r.boek_id
    -- We groeperen per boek, zodat we het gemiddelde per boek kunnen berekenen
    GROUP BY b.id
    -- We tonen alleen boeken die minstens 1 rating hebben
    HAVING rating_count > 0
    -- Sorteer eerst op hoogste cijfer, daarna op meeste stemmen
    ORDER BY avg_rating DESC, rating_count DESC
    -- Toon alleen de top 10
    LIMIT 10
");

$stmt->execute();
$boeken = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topboeken</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/boeken.css"> 
    <style>
        /* Wat simpele CSS styling voor de bovenbalk */
        #top-part{
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
        }
        .home-btn2{
            position: absolute;
            width: 80px;
            margin-left: 20px;
        }
    </style>
</head>
<body>
    <div id="top-part">
        <br><button onclick="location.href='index.html'" class="home-btn2">Home</button>
        <h1>⭐ Top Rated Books</h1><br>
    </div>
    <h4 style="text-align: center; font-style: italic; color: #1f3a5f;">See which books are popular right now and make your choice!</h4>

<?php 
// Check of de array $boeken leeg is
if (empty($boeken)): ?> 
    <p>No ratings yet.</p>
<?php else: ?>
    <div class="boeken-container">
        <?php foreach ($boeken as $boek): ?>
            <div class='boek'>
                <a href="boek.php?id=<?php echo $boek['id']; ?>" style="color: black; text-decoration: none;">
                
                <img src="<?php echo htmlspecialchars($boek['cover']) ?>" alt="<?php echo htmlspecialchars($boek['cover']) ?>">
                
                <strong class='boek-link'><?php echo htmlspecialchars($boek['naam']); ?></strong><br>
                by <?php echo htmlspecialchars($boek['schrijver']); ?><br>
                
                ⭐ <?php echo $boek['avg_rating']; ?> / 5
                (<?php echo $boek['rating_count']; ?> reviews)<br>
                
                <a href="boek.php?id=<?php echo $boek['id']; ?>" class='boek-link' style="color: blue;">View book</a>
                </a>
        </div>
        <?php endforeach; ?>
        </div>
<?php endif; ?>

</body>
</html>
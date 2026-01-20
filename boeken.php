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

// --- 2. SEARCH LOGIC ---
$search = isset($_GET['q']) ? $_GET['q'] : '';
$genre = isset($_GET['genre']) ? $_GET['genre'] : 'Alle Genres';

$query = "SELECT * FROM boeken WHERE (naam LIKE :search OR schrijver LIKE :search)";
if ($genre !== 'Alle Genres') {
    $query .= " AND genre = :genre";
}

$stmt = $conn->prepare($query);
$stmt->bindValue(':search', '%' . $search . '%');
if ($genre !== 'Alle Genres') {
    $stmt->bindValue(':genre', $genre);
}
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bibliotheek - Zoeken</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
:root {
    --bg-paper: #f9f9f3;
    --navy-deep: #1f3a5f;
    --gold-accent: #d3a574;
    --text-main: #2a2a2a;
    --white: #ffffff;
}

body {
    font-family: 'Poppins', sans-serif;
    background-color: var(--bg-paper);
    margin: 0;
    padding: 20px;
}

.container { max-width: 1200px; margin: auto; }

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
}

.header h1 {
    font-family: 'Playfair Display', serif;
    color: var(--navy-deep);
}

.subtitel { color: var(--gold-accent); }

.home-btn {
    background: var(--navy-deep);
    color: white;
    padding: 10px 25px;
    border-radius: 4px;
    text-decoration: none;
}

.zoek-en-filters {
    background: white;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 40px;
}

.search-bar input {
    width: 100%;
    padding: 12px;
    border: 2px solid #eee;
    border-radius: 4px;
}

.filters {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.filters select {
    padding: 10px;
    border-radius: 4px;
}

.btn-search {
    background: var(--navy-deep);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
}

.boeken-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 30px;
}

.boek {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    position: relative;
}

/* RENTED STATE */
.boek.rented {
    opacity: 0.5;
    pointer-events: none;
}

.boek img {
    width: 100%;
    height: 300px;
    object-fit: cover;
    border-radius: 4px;
}

.rented-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 300px;
    background: rgba(0,0,0,0.6);
    color: white;
    font-size: 1.8rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    text-transform: uppercase;
}

.boek h2 {
    font-family: 'Playfair Display', serif;
    color: var(--navy-deep);
    margin: 10px 0;
}

.tabs-cont {
    display: flex;
    justify-content: center;
    gap: 5px;
}

.small-tabs {
    font-size: 0.7rem;
    border: 1px solid var(--navy-deep);
    padding: 2px 8px;
    border-radius: 20px;
}
</style>
</head>

<body>

<div class="container">
<header class="header">
    <div>
        <h1>Bekijk Onze Collectie</h1>
        <p class="subtitel">Gevonden boeken: <?php echo count($result); ?></p>
    </div>
    <a href="index.html" class="home-btn">Home</a>
</header>

<form method="GET" class="zoek-en-filters">
    <div class="search-bar">
        <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Zoek op titel of auteur...">
    </div>

    <div class="filters">
        <select name="genre">
            <option>Alle Genres</option>
            <?php
            $genres = ['Fictie','Non-Fiction','Horror','Grafische Roman','Mysterie','Romantiek','Komedie','Poëzie','Fantasy','Science'];
            foreach ($genres as $g) {
                $selected = ($genre == $g) ? 'selected' : '';
                echo "<option value='$g' $selected>$g</option>";
            }
            ?>
        </select>
        <button class="btn-search">Zoeken</button>
    </div>
</form>

<div class="boeken-container">
<?php foreach ($result as $row): 
    $isRented = $row['is_rented'] == 1;
?>
    <div class="boek <?php echo $isRented ? 'rented' : ''; ?>">
        
        <?php if ($isRented): ?>
            <div class="rented-overlay">Rented</div>
        <?php endif; ?>

        <?php if (!$isRented): ?>
            <a href="boek.php?id=<?php echo $row['id']; ?>">
        <?php endif; ?>

        <img src="<?php echo htmlspecialchars($row['cover']); ?>" alt="">
        <h2><?php echo htmlspecialchars($row['naam']); ?></h2>

        <?php if (!$isRented): ?>
            </a>
        <?php endif; ?>

        <p>Auteur: <?php echo htmlspecialchars($row['schrijver']); ?></p>

        <div class="tabs-cont">
            <span class="small-tabs"><?php echo htmlspecialchars($row['genre']); ?></span>
            <span class="small-tabs"><?php echo htmlspecialchars($row['lengte']); ?></span>
        </div>
    </div>
<?php endforeach; ?>
</div>
</div>

</body>
</html>

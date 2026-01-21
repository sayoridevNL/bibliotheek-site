<?php
// Connectie instellingen
$db = "bibliotheek"; 
$host = "localhost";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // HTTP code 500 betekent: Internal Server Error.
    http_response_code(500);
    echo json_encode(['success' => false]);
    exit; // Stop direct met de code
}

// ==========================================
// JSON DATA LEZEN
// ==========================================
// Omdat JavaScript de data vaak als ruwe JSON stuurt (via fetch/axios), zit het NIET in $_POST.
// We moeten de 'input stream' lezen met file_get_contents("php://input").
// json_decode(..., true) maakt er daarna een PHP array van.
$data = json_decode(file_get_contents("php://input"), true); 

// DATA UITLEZEN EN VEILIG MAKEN
$boek_id = intval($data['boek_id'] ?? 0); // Gebruik 0 als er geen ID is
$rating = intval($data['rating'] ?? 0);   // Zorg dat het een heel getal is
$feedback = trim($data['feedback'] ?? ''); // Haal spaties weg

// VALIDATIE:
// Check of het ID geldig is (>0) en of de rating tussen 1 en 5 ligt.
if ($boek_id <= 0 || $rating < 1 || $rating > 5) {
    http_response_code(400); // 400 betekent: Bad Request (je stuurde foute data).
    echo json_encode(['success' => false]);
    exit;
}

// OPSLAAN IN DATABASE
$stmt = $conn->prepare("
    INSERT INTO boek_ratings (boek_id, rating, feedback)
    VALUES (:boek_id, :rating, :feedback)
");

// Bind de waarden en voer uit
$stmt->execute([
    'boek_id' => $boek_id,
    'rating' => $rating,
    'feedback' => $feedback
]);

// STUUR SUCCES BERICHT TERUG
// JavaScript ontvangt dit en weet dat de sterren zijn opgeslagen.
echo json_encode(['success' => true]);
?>
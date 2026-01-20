<?php
$db = "bibliotheek"; 
$host = "localhost";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false]);
    exit;
}

// reads JSON-data which was sent from Javascript
$data = json_decode(file_get_contents("php://input"), true); // 

$boek_id = intval($data['boek_id'] ?? 0); // ID for book that's being rated
$rating = intval($data['rating'] ?? 0); // stars 1-5
$feedback = trim($data['feedback'] ?? ''); // optional textreview

// validation: books has to be valid and rating has to be between 1-5
if ($boek_id <= 0 || $rating < 1 || $rating > 5) {
    http_response_code(400); // badrequest
    echo json_encode(['success' => false]);
    exit;
}

// saved rating in database
$stmt = $conn->prepare("
    INSERT INTO boek_ratings (boek_id, rating, feedback)
    VALUES (:boek_id, :rating, :feedback)
");

// bind values and execute
$stmt->execute([
    'boek_id' => $boek_id,
    'rating' => $rating,
    'feedback' => $feedback
]);

// sends success msg to Javascript so it knows everything worked
echo json_encode(['success' => true]);

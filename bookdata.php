<?php
// bookdata.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// --- STRATO DATABASE CONNECTION ---
$host = "localhost";
$db   = "bibliotheek";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all books
    $stmt = $conn->query("SELECT * FROM boeken");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($books);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => "Database connection failed: " . $e->getMessage()]);
}
?>
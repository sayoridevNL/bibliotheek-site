<?php
// haalt data uit de database en veranderd het naar json.
// daarna haalt twee.js het op 
// booksdata.php
header('Content-Type: application/json');
try{
    $conn = new PDO("mysql:host=localhost;dbname=bibliotheek", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->query("SELECT * FROM boeken");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($books, JSON_PRETTY_PRINT); //jsonprettyprint voor leesbaarheid

}catch (PDOException $e){
    echo json_encode(['error' => $e->getMessage()]);
}
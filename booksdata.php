<?php
// ==========================================
// 1. HEADER INSTELLEN
// ==========================================
// Dit is heel belangrijk. Hiermee vertellen we de browser: 
// "Ik stuur je geen HTML-pagina, maar ruwe JSON-data."
header('Content-Type: application/json');

// ==========================================
// 2. DATA OPHALEN
// ==========================================
try {
    // Verbinding maken
    $conn = new PDO("mysql:host=localhost;dbname=bibliotheek", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query uitvoeren. We gebruiken query() i.p.v. prepare() omdat er geen user-input is.
    // Dit is veilig zolang je geen variabelen ($var) in de string plakt.
    $stmt = $conn->query("SELECT * FROM boeken");
    
    // Resultaat ophalen als array.
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ==========================================
    // 3. VERTALEN NAAR JSON
    // ==========================================
    // json_encode() vertaalt de PHP-array naar een JavaScript-leesbare tekststring.
    // JSON_PRETTY_PRINT zorgt dat het netjes onder elkaar staat met spaties (makkelijker lezen voor ons).
    echo json_encode($books, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    // ==========================================
    // 4. FOUTAFHANDELING
    // ==========================================
    // Als de database faalt, sturen we een JSON-bericht terug met de fout.
    // Zo kan je JavaScript-code ("twee.js") zien dat er iets misging.
    echo json_encode(['error' => $e->getMessage()]);
}
?>
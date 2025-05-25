<?php
// config/db.php
define('DB_SERVER', 'localhost'); // lub adres Twojego serwera DB
define('DB_USERNAME', 'root');    // Twój użytkownik DB
define('DB_PASSWORD', '');        // Twoje hasło DB
define('DB_NAME', 'book_exchange_db'); // Nazwa Twojej bazy danych

// Nawiąż połączenie
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Sprawdź połączenie
if ($conn->connect_error) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}

// Ustawienie kodowania (opcjonalne, ale dobre praktyka)
$conn->set_charset("utf8mb4");

// Włącz raportowanie błędów dla mysqli (pomocne w dewelopmencie)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>
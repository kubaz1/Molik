<?php
// public/api_fetch_google_book.php

// session_start(); // Jeśli potrzebna autoryzacja do użycia tej funkcji

header('Content-Type: application/json');

$isbn = isset($_GET['isbn']) ? trim($_GET['isbn']) : null;
$apiKey = 'AIzaSyBEiBuY3KvJogbMchz4Wj0G3PUgcov2HhY'; // Przechowuj bezpiecznie, np. w zmiennej środowiskowej

if (!$isbn) {
    http_response_code(400);
    echo json_encode(['error' => 'Nie podano numeru ISBN']);
    exit;
}

// Walidacja ISBN (prosta)
if (!preg_match('/^[0-9]{10,13}X?$/i', $isbn)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nieprawidłowy format ISBN']);
    exit;
}

$googleApiUrl = "https://www.googleapis.com/books/v1/volumes?q=isbn:" . urlencode($isbn) . "&key=" . $apiKey;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $googleApiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Uwaga: na produkcji lepiej skonfigurować certyfikaty
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode != 200 || !$response) {
    http_response_code(502); // Bad Gateway or service unavailable
    echo json_encode(['error' => 'Nie udało się połączyć z Google Books API lub API zwróciło błąd.']);
    exit;
}

$data = json_decode($response, true);

if (isset($data['items'][0]['volumeInfo'])) {
    $volumeInfo = $data['items'][0]['volumeInfo'];
    $bookDetails = [
        'title' => $volumeInfo['title'] ?? '',
        'authors' => implode(', ', $volumeInfo['authors'] ?? []),
        'description' => $volumeInfo['description'] ?? '',
        'publishedDate' => $volumeInfo['publishedDate'] ?? '',
        'pageCount' => $volumeInfo['pageCount'] ?? '',
        'categories' => implode(', ', $volumeInfo['categories'] ?? []),
        'thumbnail' => $volumeInfo['imageLinks']['thumbnail'] ?? ($volumeInfo['imageLinks']['smallThumbnail'] ?? ''),
        'isbn10' => '',
        'isbn13' => ''
    ];

    if (isset($volumeInfo['industryIdentifiers'])) {
        foreach ($volumeInfo['industryIdentifiers'] as $identifier) {
            if ($identifier['type'] === 'ISBN_10') {
                $bookDetails['isbn10'] = $identifier['identifier'];
            } elseif ($identifier['type'] === 'ISBN_13') {
                $bookDetails['isbn13'] = $identifier['identifier'];
            }
        }
    }
    // Jeśli podano ISBN-10, a API zwróciło tylko ISBN-13 (lub odwrotnie), wybierz ten, który jest dostępny
    if(empty($bookDetails['isbn10']) && !empty($bookDetails['isbn13']) && strlen($isbn) == 10) {
        // Można by tu spróbować konwersji, ale na razie zostawiamy
    }


    echo json_encode(['success' => true, 'data' => $bookDetails]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Nie znaleziono książki o podanym numerze ISBN w Google Books.']);
}
?>
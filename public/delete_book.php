<?php
require_once '../config/db.php'; // Na początku dla $conn
require_once '../includes/header.php'; // Header po $conn, ale przed logiką dla sesji

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = "Musisz być zalogowany, aby usunąć książkę.";
    $_SESSION['flash_type'] = "warning";
    header("Location: login.php");
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];
$book_id_to_delete = null;
$errors = [];

if (isset($_GET['book_id']) && filter_var($_GET['book_id'], FILTER_VALIDATE_INT)) {
    $book_id_to_delete = (int)$_GET['book_id'];
} else {
    $_SESSION['flash_message'] = "Nieprawidłowe lub brakujące ID książki do usunięcia.";
    $_SESSION['flash_type'] = "danger";
    header("Location: profile.php"); // Lub books.php
    exit;
}

// Pobierz informacje o książce, aby sprawdzić właściciela i nazwę pliku zdjęcia
$stmt_check = $conn->prepare("SELECT owner_id, image_filename FROM books WHERE id = ?");
if ($stmt_check) {
    $stmt_check->bind_param("i", $book_id_to_delete);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($book_to_delete_data = $result_check->fetch_assoc()) {
        if ((int)$book_to_delete_data['owner_id'] !== $current_user_id) {
            $_SESSION['flash_message'] = "Nie masz uprawnień do usunięcia tej książki.";
            $_SESSION['flash_type'] = "danger";
            header("Location: books.php");
            exit;
        }
        $image_filename_to_delete = $book_to_delete_data['image_filename'];
    } else {
        $_SESSION['flash_message'] = "Nie znaleziono książki o podanym ID.";
        $_SESSION['flash_type'] = "danger";
        header("Location: profile.php");
        exit;
    }
    $stmt_check->close();
} else {
    $_SESSION['flash_message'] = "Błąd serwera podczas weryfikacji książki: " . $conn->error;
    $_SESSION['flash_type'] = "danger";
    header("Location: profile.php");
    exit;
}


// Potwierdzenie usunięcia (jeśli nie ma JavaScriptu lub jako dodatkowy krok)
// Dla prostoty, używamy JavaScript confirm() w linku. Jeśli to zawiedzie, można dodać tu formularz POST z potwierdzeniem.
// Załóżmy, że użytkownik potwierdził przez JavaScript.

$conn->begin_transaction();
try {
    // 1. Usuń powiązane wątki wiadomości (lub oznacz je jako dotyczące usuniętej książki)
    // W schemacie mamy ON DELETE SET NULL dla related_book_id w message_threads,
    // więc bezpośrednie usuwanie wątków nie jest konieczne, jeśli chcemy je zachować z related_book_id = NULL.
    // Jeśli chcemy całkiem usunąć wątki powiązane z książką:
    /*
    $stmt_delete_threads = $conn->prepare("DELETE FROM message_threads WHERE related_book_id = ?");
    if (!$stmt_delete_threads) throw new Exception("Błąd przygotowania usuwania wątków: " . $conn->error);
    $stmt_delete_threads->bind_param("i", $book_id_to_delete);
    $stmt_delete_threads->execute();
    $stmt_delete_threads->close();
    */
    // Zostajemy przy ON DELETE SET NULL.

    // 2. Usuń książkę z bazy danych
    $stmt_delete_book = $conn->prepare("DELETE FROM books WHERE id = ? AND owner_id = ?");
    if (!$stmt_delete_book) throw new Exception("Błąd przygotowania usuwania książki: " . $conn->error);
    $stmt_delete_book->bind_param("ii", $book_id_to_delete, $current_user_id);
    $stmt_delete_book->execute();

    if ($stmt_delete_book->affected_rows > 0) {
        // 3. Jeśli książka została usunięta z DB, usuń plik zdjęcia
        if ($image_filename_to_delete) {
            $image_path = __DIR__ . '/uploads/book_images/' . $image_filename_to_delete;
            if (file_exists($image_path) && is_writable($image_path)) { // Sprawdź czy plik istnieje i jest zapisywalny
                unlink($image_path);
                // Brak obsługi błędu unlink celowo, aby nie blokować sukcesu usunięcia z DB
            }
        }
        $_SESSION['flash_message'] = "Książka została pomyślnie usunięta.";
        $_SESSION['flash_type'] = "success";
    } else {
        // To nie powinno się zdarzyć jeśli wcześniejsze sprawdzenie właściciela przeszło
        throw new Exception("Nie udało się usunąć książki z bazy lub nie masz uprawnień.");
    }
    $stmt_delete_book->close();
    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash_message'] = "Wystąpił błąd podczas usuwania książki: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
}

header("Location: profile.php"); // Przekieruj z powrotem na profil
exit;

// Footer nie jest potrzebny, bo zawsze jest redirect
?>
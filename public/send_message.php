<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Upewnij się, że użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = "Musisz być zalogowany, aby wysłać wiadomość.";
    $_SESSION['flash_type'] = "warning";
    header("Location: login.php" . (isset($_GET['book_id']) ? '?redirect=' . urlencode('book_details.php?id=' . $_GET['book_id']) : ''));
    exit;
}

$receiver_id = null;
$book_id = null;
$book_title = "Nieokreślona książka"; // Domyślny tytuł, jeśli książka nie zostanie znaleziona
$receiver_username = "Nieznany użytkownik";
$errors = [];
$message_content = '';

// Walidacja parametrów GET
if (isset($_GET['receiver_id']) && filter_var($_GET['receiver_id'], FILTER_VALIDATE_INT)) {
    $receiver_id = (int)$_GET['receiver_id'];
} else {
    $errors[] = "Nieprawidłowy odbiorca wiadomości.";
}

if (isset($_GET['book_id']) && filter_var($_GET['book_id'], FILTER_VALIDATE_INT)) {
    $book_id = (int)$_GET['book_id'];
} else {
    // Book_id jest opcjonalny dla ogólnej wiadomości, ale tutaj zakładamy, że jest powiązany z książką
    $errors[] = "Nieprawidłowe ID książki, której dotyczy wiadomość.";
}

// Nie można wysyłać wiadomości do samego siebie
if ($receiver_id === $_SESSION['user_id']) {
    $_SESSION['flash_message'] = "Nie możesz wysłać wiadomości do samego siebie.";
    $_SESSION['flash_type'] = "warning";
    header("Location: " . (isset($book_id) ? "book_details.php?id=$book_id" : "index.php"));
    exit;
}


// Pobierz informacje o książce i odbiorcy, jeśli ID są prawidłowe
if (empty($errors)) {
    // Pobierz nazwę użytkownika odbiorcy
    $stmt_receiver = $conn->prepare("SELECT username FROM users WHERE id = ?");
    if ($stmt_receiver) {
        $stmt_receiver->bind_param("i", $receiver_id);
        $stmt_receiver->execute();
        $result_receiver = $stmt_receiver->get_result();
        if ($user_data = $result_receiver->fetch_assoc()) {
            $receiver_username = $user_data['username'];
        } else {
            $errors[] = "Nie znaleziono odbiorcy.";
        }
        $stmt_receiver->close();
    }

    // Pobierz tytuł książki
    $stmt_book = $conn->prepare("SELECT title FROM books WHERE id = ? AND owner_id = ?");
    if ($stmt_book) {
        $stmt_book->bind_param("ii", $book_id, $receiver_id); // Upewnij się, że książka należy do odbiorcy
        $stmt_book->execute();
        $result_book = $stmt_book->get_result();
        if ($book_data = $result_book->fetch_assoc()) {
            $book_title = $book_data['title'];
        } else {
            $errors[] = "Nie znaleziono określonej książki należącej do tego użytkownika lub ID książki jest nieprawidłowe.";
        }
        $stmt_book->close();
    }
}


// Obsługa wysyłania formularza
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($errors)) {
    $message_content = trim($_POST['message_content']);
    $sender_id = $_SESSION['user_id'];
    // receiver_id i book_id są już ustawione z GET i walidowane

    if (empty($message_content)) {
        $errors[] = "Treść wiadomości nie może być pusta.";
    }

    if (empty($errors)) {
        $conn->begin_transaction(); // Rozpocznij transakcję

        try {
            // Krok 1: Znajdź lub stwórz wątek konwersacji.
            // Wątek jest unikalny dla pary użytkowników i konkretnej książki.
            $thread_id = null;

            // Najpierw sprawdź, czy wątek dla tej pary i książki już istnieje
            $stmt_find_thread = $conn->prepare(
                "SELECT t.id FROM message_threads t
                 JOIN message_participants mp1 ON t.id = mp1.thread_id AND mp1.user_id = ?
                 JOIN message_participants mp2 ON t.id = mp2.thread_id AND mp2.user_id = ?
                 WHERE t.related_book_id = ?"
            );
            $stmt_find_thread->bind_param("iii", $sender_id, $receiver_id, $book_id);
            $stmt_find_thread->execute();
            $result_thread = $stmt_find_thread->get_result();
            if ($existing_thread = $result_thread->fetch_assoc()) {
                $thread_id = $existing_thread['id'];
            }
            $stmt_find_thread->close();

            if (!$thread_id) {
                // Stwórz nowy wątek
                $subject = "Zapytanie dotyczące książki: " . $book_title;
                $stmt_create_thread = $conn->prepare("INSERT INTO message_threads (related_book_id, subject, last_message_at) VALUES (?, ?, NOW())");
                $stmt_create_thread->bind_param("is", $book_id, $subject);
                $stmt_create_thread->execute();
                $thread_id = $stmt_create_thread->insert_id;
                $stmt_create_thread->close();

                // Dodaj uczestników do wątku
                $stmt_add_participant1 = $conn->prepare("INSERT INTO message_participants (thread_id, user_id) VALUES (?, ?)");
                $stmt_add_participant1->bind_param("ii", $thread_id, $sender_id);
                $stmt_add_participant1->execute();
                $stmt_add_participant1->close();

                $stmt_add_participant2 = $conn->prepare("INSERT INTO message_participants (thread_id, user_id) VALUES (?, ?)");
                $stmt_add_participant2->bind_param("ii", $thread_id, $receiver_id);
                $stmt_add_participant2->execute();
                $stmt_add_participant2->close();
            } else {
                // Zaktualizuj czas ostatniej wiadomości dla istniejącego wątku
                $stmt_update_thread_time = $conn->prepare("UPDATE message_threads SET last_message_at = NOW() WHERE id = ?");
                $stmt_update_thread_time->bind_param("i", $thread_id);
                $stmt_update_thread_time->execute();
                $stmt_update_thread_time->close();
            }


            // Krok 2: Dodaj wiadomość do wątku
            $stmt_insert_message = $conn->prepare("INSERT INTO messages (thread_id, sender_id, content) VALUES (?, ?, ?)");
            $stmt_insert_message->bind_param("iis", $thread_id, $sender_id, $message_content);
            $stmt_insert_message->execute();
            $stmt_insert_message->close();

            $conn->commit(); // Zatwierdź transakcję

            $_SESSION['flash_message'] = "Wiadomość została wysłana!";
            $_SESSION['flash_type'] = "success";
            header("Location: view_thread.php?thread_id=" . $thread_id); // Przekieruj do widoku wątku
            exit;

        } catch (Exception $e) {
            $conn->rollback(); // Wycofaj transakcję w razie błędu
            $errors[] = "Wystąpił błąd podczas wysyłania wiadomości: " . $e->getMessage();
        }
    }
}
?>

<div class="send-message-container">
    <h2>Wyślij wiadomość do <?php echo htmlspecialchars($receiver_username); ?></h2>
    <p>Dotyczy książki: <strong><?php echo htmlspecialchars($book_title); ?></strong></p>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($errors) || $_SERVER["REQUEST_METHOD"] != "POST"): // Pokaż formularz jeśli nie ma błędów krytycznych lub nie było POST ?>
        <form action="send_message.php?receiver_id=<?php echo $receiver_id; ?>&book_id=<?php echo $book_id; ?>" method="post">
            <div>
                <label for="message_content">Twoja wiadomość:</label><br>
                <textarea id="message_content" name="message_content" rows="8" required><?php echo htmlspecialchars($message_content); ?></textarea>
            </div>
            <div>
                <button type="submit" class="button">Wyślij wiadomość</button>
            </div>
        </form>
    <?php else: ?>
         <p><a href="book_details.php?id=<?php echo $book_id; ?>">Wróć do szczegółów książki</a></p>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
if(isset($conn)) $conn->close();
?>
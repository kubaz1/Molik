<?php
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = "Musisz być zalogowany, aby zobaczyć wiadomości.";
    $_SESSION['flash_type'] = "warning";
    header("Location: login.php");
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];
$thread_id = null;
$thread_details = null;
$messages_in_thread = [];
$errors = [];
$reply_content_form = '';

if (isset($_GET['thread_id']) && filter_var($_GET['thread_id'], FILTER_VALIDATE_INT)) {
    $thread_id = (int)$_GET['thread_id'];
} else {
    $errors[] = "Nieprawidłowe lub brakujące ID wątku.";
}

// Sprawdź, czy użytkownik jest uczestnikiem tego wątku
if ($thread_id && empty($errors)) {
    $stmt_check_participant = $conn->prepare("SELECT COUNT(*) as count FROM message_participants WHERE thread_id = ? AND user_id = ?");
    if ($stmt_check_participant) {
        $stmt_check_participant->bind_param("ii", $thread_id, $current_user_id);
        $stmt_check_participant->execute();
        $result_check = $stmt_check_participant->get_result()->fetch_assoc();
        if ($result_check['count'] == 0) {
            $errors[] = "Nie masz dostępu do tego wątku lub wątek nie istnieje.";
        }
        $stmt_check_participant->close();
    } else {
        $errors[] = "Błąd serwera podczas weryfikacji uczestnika wątku: " . $conn->error;
    }
}

// Pobierz szczegóły wątku i wiadomości, jeśli nie ma błędów
if ($thread_id && empty($errors)) {
    // Oznacz wiadomości w tym wątku jako przeczytane przez bieżącego użytkownika (wysłane przez innych)
    $stmt_mark_read = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE thread_id = ? AND sender_id != ? AND is_read = FALSE");
    if ($stmt_mark_read) {
        $stmt_mark_read->bind_param("ii", $thread_id, $current_user_id);
        $stmt_mark_read->execute();
        $stmt_mark_read->close();
    } else {
        // $errors[] = "Błąd serwera przy oznaczaniu wiadomości jako przeczytane."; // Można dodać logowanie
    }

    // Pobierz szczegóły wątku (z kim rozmawiamy, o czym)
    $stmt_thread = $conn->prepare("
        SELECT t.id AS thread_id, t.subject, t.related_book_id,
               b.title AS book_title, b.id AS actual_book_id,
               other_user.id AS other_user_id, other_user.username AS other_user_username, other_user.avatar_url AS other_user_avatar
        FROM message_threads t
        INNER JOIN message_participants mp_current ON t.id = mp_current.thread_id AND mp_current.user_id = ?
        INNER JOIN message_participants mp_other ON t.id = mp_other.thread_id AND mp_other.user_id != ?
        INNER JOIN users other_user ON mp_other.user_id = other_user.id
        LEFT JOIN books b ON t.related_book_id = b.id
        WHERE t.id = ?
    ");
    if ($stmt_thread) {
        $stmt_thread->bind_param("iii", $current_user_id, $current_user_id, $thread_id);
        $stmt_thread->execute();
        $result_thread_details = $stmt_thread->get_result();
        if ($result_thread_details->num_rows === 1) {
            $thread_details = $result_thread_details->fetch_assoc();
        } else {
            $errors[] = "Nie znaleziono szczegółów wątku lub nie masz do niego dostępu.";
        }
        $stmt_thread->close();
    } else {
        $errors[] = "Błąd serwera podczas pobierania szczegółów wątku: " . $conn->error;
    }

    if (empty($errors) && $thread_details) {
        $stmt_messages_list = $conn->prepare("
            SELECT m.id, m.sender_id, m.content, m.sent_at, u.username AS sender_username
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.thread_id = ?
            ORDER BY m.sent_at ASC
        ");
        if ($stmt_messages_list) {
            $stmt_messages_list->bind_param("i", $thread_id);
            $stmt_messages_list->execute();
            $result_messages = $stmt_messages_list->get_result();
            while ($row = $result_messages->fetch_assoc()) {
                $messages_in_thread[] = $row;
            }
            $stmt_messages_list->close();
        } else {
            $errors[] = "Błąd serwera podczas pobierania wiadomości z wątku: " . $conn->error;
        }
    }
}

// Obsługa wysyłania odpowiedzi
if ($_SERVER["REQUEST_METHOD"] == "POST" && $thread_id && empty($errors) && isset($_POST['reply_content']) && $thread_details) {
    $reply_content_form = trim($_POST['reply_content']);

    if (empty($reply_content_form)) {
        $errors[] = "Treść odpowiedzi nie może być pusta.";
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $stmt_insert_reply = $conn->prepare("INSERT INTO messages (thread_id, sender_id, content) VALUES (?, ?, ?)");
            $stmt_insert_reply->bind_param("iis", $thread_id, $current_user_id, $reply_content_form);
            $stmt_insert_reply->execute();
            $stmt_insert_reply->close();

            $stmt_update_thread_time = $conn->prepare("UPDATE message_threads SET last_message_at = NOW() WHERE id = ?");
            $stmt_update_thread_time->bind_param("i", $thread_id);
            $stmt_update_thread_time->execute();
            $stmt_update_thread_time->close();

            $conn->commit();
            header("Location: view_thread.php?thread_id=" . $thread_id); // Odświeżenie strony
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Błąd serwera podczas wysyłania odpowiedzi: " . $e->getMessage();
        }
    }
}
?>

<div class="view-thread-page">
    <?php if (!empty($errors) && !$thread_details): ?>
        <div class="errors" style="margin: 20px;">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
            <p><a href="messages.php" class="button">Wróć do listy wiadomości</a></p>
        </div>
    <?php elseif ($thread_details): ?>
        <div class="thread-title-bar">
            <a href="messages.php" class="back-to-messages" title="Wróć do wiadomości">&#10094;</a>
                <div class="thread-avatar-placeholder"> {/* Ta klasa nadal może być używana do ogólnego stylowania kontenera avatara /}
                <img src="uploads/avatars/<?php echo htmlspecialchars($thread_details['other_user_avatar'] ?: 'default_avatar.png'); ?>" 
                     alt="Avatar <?php echo htmlspecialchars($thread_details['other_user_username']); ?>" 
                     class="thread-title-avatar-img"> {/ Dodajemy nową klasę dla samego obrazka */}
            </div>
            <div class="thread-info-title">
                <h2><a href="profile.php?user_id=<?php echo $thread_details['other_user_id']; ?>"><?php echo htmlspecialchars($thread_details['other_user_username']); ?></a></h2>
                <?php if ($thread_details['book_title'] && $thread_details['actual_book_id']): ?>
                    <p class="thread-context">Dotyczy książki: <a href="book_details.php?id=<?php echo $thread_details['actual_book_id']; ?>"><?php echo htmlspecialchars($thread_details['book_title']); ?></a></p>
                <?php elseif ($thread_details['subject']): ?>
                    <p class="thread-context">Temat: <?php echo htmlspecialchars($thread_details['subject']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($errors) && $_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <div class="errors" style="margin: 0 15px 15px 15px;">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="message-list">
            <?php if (empty($messages_in_thread)): ?>
                <p class="no-messages">Brak wiadomości w tym wątku. Napisz pierwszą!</p>
            <?php else: ?>
                <?php foreach ($messages_in_thread as $message): ?>
                    <div class="message-item <?php echo ((int)$message['sender_id'] === $current_user_id) ? 'sent' : 'received'; ?>">
                        <div class="message-sender">
                            <?php /* <strong><?php echo ((int)$message['sender_id'] === $current_user_id) ? "Ty" : htmlspecialchars($message['sender_username']); ?></strong> */ ?>
                        </div>
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                            <span class="message-timestamp">
                                <?php
                                try {
                                    $date = new DateTime($message['sent_at']);
                                    $today = new DateTime('today');
                                    $yesterday = new DateTime('yesterday');
                                    if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
                                        echo $date->format('H:i');
                                    } elseif ($date->format('Y-m-d') === $yesterday->format('Y-m-d')) {
                                        echo 'Wczoraj ' . $date->format('H:i');
                                    } else {
                                        echo $date->format('d.m.y H:i');
                                    }
                                } catch (Exception $e) {
                                    echo htmlspecialchars($message['sent_at']);
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="reply-form-container">
            <form action="view_thread.php?thread_id=<?php echo $thread_id; ?>" method="post">
                <textarea name="reply_content" rows="1" placeholder="Wpisz swoją odpowiedź..." required oninput='this.style.height = "";this.style.height = this.scrollHeight + "px"'><?php echo htmlspecialchars($reply_content_form); ?></textarea>
                <button type="submit" class="button" title="Wyślij">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </form>
        </div>
    <?php else: ?>
        <div style="padding: 20px;">
            <p>Nie można załadować wątku. Sprawdź, czy podany adres URL jest poprawny.</p>
            <p><a href="messages.php" class="button">Wróć do listy wiadomości</a></p>
        </div>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>
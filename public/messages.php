<?php
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = "Musisz być zalogowany, aby zobaczyć swoje wiadomości.";
    $_SESSION['flash_type'] = "warning";
    header("Location: login.php");
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];
$threads = [];

// Zapytanie do pobrania wątków, w których uczestniczy użytkownik,
// wraz z informacją o drugiej osobie w konwersacji, jej avatarze i tytule książki
$stmt = $conn->prepare("
    SELECT
        t.id AS thread_id,
        t.subject,
        COALESCE(t.last_message_at, t.created_at) AS effective_last_activity,
        b.title AS book_title,
        b.id AS book_id,
        other_user.id AS other_user_id,
        other_user.username AS other_user_username,
        other_user.avatar_url AS other_user_avatar, /* Dodano avatar rozmówcy */
        (SELECT m.content FROM messages m WHERE m.thread_id = t.id ORDER BY m.sent_at DESC LIMIT 1) AS last_message_content,
        (SELECT m.sender_id FROM messages m WHERE m.thread_id = t.id ORDER BY m.sent_at DESC LIMIT 1) AS last_message_sender_id,
        (SELECT COUNT(*) FROM messages m WHERE m.thread_id = t.id AND m.sender_id != ? AND m.is_read = FALSE) AS unread_messages_count
    FROM message_threads t
    INNER JOIN message_participants mp ON t.id = mp.thread_id AND mp.user_id = ?
    INNER JOIN message_participants mp_other ON t.id = mp_other.thread_id AND mp_other.user_id != ?
    INNER JOIN users other_user ON mp_other.user_id = other_user.id
    LEFT JOIN books b ON t.related_book_id = b.id
    ORDER BY effective_last_activity DESC
");

if ($stmt) {
    $stmt->bind_param("iii", $current_user_id, $current_user_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $threads[] = $row;
    }
    $stmt->close();
} else {
    echo "<div class='errors'><p>Błąd serwera podczas pobierania wątków wiadomości.</p></div>";
}
?>

<div class="messages-page">
    <h2>Moje Wiadomości</h2>

    <?php if (empty($threads)): ?>
        <p class="no-messages-info">Nie masz jeszcze żadnych wiadomości ani konwersacji.</p>
    <?php else: ?>
        <ul class="thread-list">
            <?php foreach ($threads as $thread): ?>
                <li class="thread-item <?php echo ($thread['unread_messages_count'] > 0) ? 'unread' : ''; ?>">
                    <a href="view_thread.php?thread_id=<?php echo $thread['thread_id']; ?>" class="thread-link">
                        <div class="thread-avatar-area">
                            <img src="uploads/avatars/<?php echo htmlspecialchars($thread['other_user_avatar'] ?: 'default_avatar.png'); ?>"
                                 alt="Avatar <?php echo htmlspecialchars($thread['other_user_username']); ?>" class="thread-avatar-img">
                        </div>
                        <div class="thread-content-summary">
                            <div class="thread-header">
                                <span class="thread-participant"><?php echo htmlspecialchars($thread['other_user_username']); ?></span>
                                <span class="thread-timestamp">
                                    <?php
                                    try {
                                        $date = new DateTime($thread['effective_last_activity']);
                                        $today = new DateTime('today');
                                        $yesterday = new DateTime('yesterday');
                                        if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
                                            echo $date->format('H:i');
                                        } elseif ($date->format('Y-m-d') === $yesterday->format('Y-m-d')) {
                                            echo 'Wczoraj';
                                        } else {
                                            echo $date->format('d.m.Y');
                                        }
                                    } catch (Exception $e) { echo htmlspecialchars($thread['effective_last_activity']); }
                                    ?>
                                </span>
                            </div>
                            <div class="last-message-preview">
                                <?php
                                if ($thread['last_message_content']) {
                                    $prefix = ((int)$thread['last_message_sender_id'] === $current_user_id) ? "Ty: " : "";
                                    echo "<span class='message-prefix'>" . $prefix . "</span>" . htmlspecialchars(mb_substr($thread['last_message_content'], 0, 45)) . (mb_strlen($thread['last_message_content']) > 45 ? "..." : "");
                                } else {
                                    echo "<em>Rozpocznij konwersację...</em>";
                                }
                                ?>
                                <?php if ($thread['unread_messages_count'] > 0): ?>
                                    <span class="unread-badge"><?php echo $thread['unread_messages_count']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>
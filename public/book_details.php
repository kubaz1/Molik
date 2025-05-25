<?php
require_once '../config/db.php';
require_once '../includes/header.php';

$book_id = null;
$book = null;
$errors = [];

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $book_id = (int)$_GET['id'];
} else {
    $errors[] = "Nieprawidłowe lub brakujące ID książki.";
}

if (empty($errors) && $book_id) {
    $stmt = $conn->prepare(
        "SELECT b.id, b.title, b.author, b.isbn, b.description, b.price, b.status,
                b.created_at, b.image_filename, b.owner_id, u.username AS owner_username
         FROM books b
         JOIN users u ON b.owner_id = u.id
         WHERE b.id = ?"
    );
    if ($stmt) {
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $book = $result->fetch_assoc();
        } else {
            $errors[] = "Nie znaleziono książki o podanym ID.";
        }
        $stmt->close();
    } else {
        $errors[] = "Błąd serwera podczas przygotowania zapytania o książkę: " . $conn->error;
    }
}
// $conn->close(); // Przeniesione do footer.php
?>

<div class="book-details-page">
    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
        <p><a href="books.php" class="button">Wróć do listy książek</a></p>
    <?php elseif ($book): ?>
        <div class="book-details-header">
            <h2><?php echo htmlspecialchars($book['title']); ?></h2>
            <p class="author-info">Autor: <?php echo htmlspecialchars($book['author']); ?></p>
        </div>

        <div class="book-details-content">
            <div class="book-details-image-column">
                <img src="uploads/book_images/<?php echo htmlspecialchars($book['image_filename'] ?: 'placeholder.png'); ?>"
                     alt="Okładka <?php echo htmlspecialchars($book['title']); ?>" class="book-image-detail">
            </div>
            <div class="book-details-info-column">
                <?php if (!empty($book['isbn'])): ?>
                    <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?></p>
                <?php endif; ?>
                <p><strong>Data dodania:</strong> <?php
                    try { $date = new DateTime($book['created_at']); echo $date->format('d.m.Y H:i'); }
                    catch (Exception $e) { echo htmlspecialchars($book['created_at']); }
                ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($book['status'])); ?></p>
                <p><strong>Wystawione przez:</strong> <a href="profile.php?user_id=<?php echo $book['owner_id']; ?>"><?php echo htmlspecialchars($book['owner_username']); ?></a></p>

                <?php if ($book['price'] !== null): ?>
                    <p class="book-price"><strong>Cena:</strong> <?php echo htmlspecialchars(number_format($book['price'], 2, ',', '.')); ?> zł</p>
                <?php else: ?>
                    <p class="book-price"><strong>Wymiana / Za darmo</strong></p>
                <?php endif; ?>

                <?php if (!empty($book['description'])): ?>
                    <div class="book-description">
                        <h3>Opis:</h3>
                        <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                    </div>
                <?php endif; ?>

                 <div class="book-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['user_id'] == $book['owner_id']): ?>
                            <p><em>To jest Twoja książka.</em></p>
                     <a href="delete_book.php?book_id=<?php echo $book['id']; ?>" class="button button-danger" onclick="return confirm('Czy na pewno chcesz usunąć tę książkę? Spowoduje to również usunięcie powiązanych z nią wątków wiadomości.');">Usuń to ogłoszenie</a>
                            <?php elseif ($book['status'] == 'available'): ?>
                            <a href="send_message.php?receiver_id=<?php echo $book['owner_id']; ?>&book_id=<?php echo $book['id']; ?>" class="button button-primary">Skontaktuj się z wystawiającym</a>
                        <?php else: ?>
                            <p><em>To ogłoszenie jest już nieaktywne.</em></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><a href="login.php?redirect=<?php echo urlencode("book_details.php?id=" . $book['id']); ?>" class="button">Zaloguj się</a>, aby skontaktować się z wystawiającym.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <p>Nie udało się załadować szczegółów książki. Możliwe, że została usunięta lub podano nieprawidłowy adres.</p>
        <p><a href="books.php" class="button">Wróć do listy książek</a></p>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
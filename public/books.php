<?php
require_once '../config/db.php';
require_once '../includes/header.php';

$books = [];
$sql = "SELECT b.id, b.title, b.author, b.price, b.isbn, b.owner_id, b.image_filename, u.username as owner_username
        FROM books b
        JOIN users u ON b.owner_id = u.id
        WHERE b.status = 'available'
        ORDER BY b.created_at DESC";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}
$conn->close();
?>

<h2>Dostępne Książki</h2>

<?php if (!empty($books)): ?>
    <div class="books-list">
        <?php foreach ($books as $book): ?>
            <div class="book-item">
                <?php if (!empty($book['image_filename'])): ?>
                    <div class="book-image-container">
                        <img src="uploads/book_images/<?php echo htmlspecialchars($book['image_filename']); ?>" alt="Okładka <?php echo htmlspecialchars($book['title']); ?>" class="book-image-thumbnail">
                    </div>
                <?php else: ?>
                    <div class="book-image-container">
                        <img src="uploads/book_images/placeholder.png" alt="Brak zdjęcia" class="book-image-thumbnail"> </div>
                <?php endif; ?>
                <div class="book-info">
                    <h3><a href="book_details.php?id=<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?></a></h3>
                    <p><strong>Autor:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                    <?php if ($book['isbn']): ?>
                        <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?></p>
                    <?php endif; ?>
                    <?php if ($book['price'] !== null): ?>
                        <p><strong>Cena:</strong> <?php echo htmlspecialchars(number_format($book['price'], 2, ',', '.')); ?> zł</p>
                    <?php else: ?>
                        <p><strong>Do wymiany / Cena nieokreślona</strong></p>
                    <?php endif; ?>
                    <p><em>Wystawione przez: <a href="profile.php?user_id=<?php echo $book['owner_id']; ?>"><?php echo htmlspecialchars($book['owner_username']); ?></a></em></p>
                    <p><a href="book_details.php?id=<?php echo $book['id']; ?>" class="button-details">Zobacz szczegóły</a></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>Obecnie brak dostępnych książek.</p>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
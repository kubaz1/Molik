<?php
require_once '../config/db.php';
require_once '../includes/header.php';
?>

<div class="hero-section">
    <h2>Witaj na Molik!</h2>
    <p>Znajdź unikalne tytuły, wymieniaj się lub sprzedawaj książki z innymi pasjonatami literatury.</p>
    <p>
        <a href="books.php" class="button button-primary">Przeglądaj Ogłoszenia</a>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="register.php" class="button">Dołącz do nas</a>
        <?php endif; ?>
    </p>
</div>

<section class="featured-books">
    <h3>Ostatnio Dodane Książki</h3>
    <div class="books-list">
        <?php
        $sql_latest = "SELECT b.id, b.title, b.author, b.price, b.image_filename, u.username as owner_username, b.owner_id
                       FROM books b
                       JOIN users u ON b.owner_id = u.id
                       WHERE b.status = 'available'
                       ORDER BY b.created_at DESC
                       LIMIT 4";
        $result_latest = $conn->query($sql_latest);
        if ($result_latest && $result_latest->num_rows > 0):
            while ($book = $result_latest->fetch_assoc()): ?>
                <div class="book-item">
                    <div class="book-image-container">
                        <a href="book_details.php?id=<?php echo $book['id']; ?>">
                            <img src="uploads/book_images/<?php echo htmlspecialchars($book['image_filename'] ?: 'placeholder.png'); ?>" alt="Okładka <?php echo htmlspecialchars($book['title']); ?>" class="book-image-thumbnail">
                        </a>
                    </div>
                    <div class="book-info">
                        <h4><a href="book_details.php?id=<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?></a></h4>
                        <p>Autor: <?php echo htmlspecialchars($book['author']); ?></p>
                        <?php if ($book['price'] !== null): ?>
                            <p class="price"><?php echo htmlspecialchars(number_format($book['price'], 2, ',', '.')); ?> zł</p>
                        <?php else: ?>
                            <p class="price">Wymiana / Za darmo</p>
                        <?php endif; ?>
                        <p class="owner">Wystawione przez: <a href="profile.php?user_id=<?php echo $book['owner_id']; ?>"><?php echo htmlspecialchars($book['owner_username']); ?></a></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Brak najnowszych książek do wyświetlenia.</p>
        <?php endif;
        if ($result_latest) $result_latest->free();
        ?>
    </div>
</section>

<?php
require_once '../includes/footer.php';
?>
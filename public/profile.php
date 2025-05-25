<?php
require_once '../config/db.php';
require_once '../includes/header.php';

$profile_user_id = null;
$profile_user_data = null;
$profile_user_books = [];
$is_own_profile = false;
$errors = [];

// Określ, czyj profil mamy wyświetlić
if (isset($_GET['user_id']) && filter_var($_GET['user_id'], FILTER_VALIDATE_INT)) {
    $profile_user_id = (int)$_GET['user_id'];
    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $profile_user_id) {
        $is_own_profile = true;
    }
} elseif (isset($_SESSION['user_id'])) {
    $profile_user_id = (int)$_SESSION['user_id'];
    $is_own_profile = true;
} else {
    $_SESSION['flash_message'] = "Musisz być zalogowany lub podać ID użytkownika, aby zobaczyć profil.";
    $_SESSION['flash_type'] = "warning";
    header("Location: login.php");
    exit;
}

// Pobierz dane użytkownika profilu
if ($profile_user_id) {
    $sql_user_fields = "id, username, created_at, profile_description, avatar_url";
    if ($is_own_profile) {
        $sql_user_fields .= ", email"; // Dodaj email tylko dla własnego profilu
    }
    $stmt_user = $conn->prepare("SELECT " . $sql_user_fields . " FROM users WHERE id = ?");
    if ($stmt_user) {
        $stmt_user->bind_param("i", $profile_user_id);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        if ($result_user->num_rows === 1) {
            $profile_user_data = $result_user->fetch_assoc();
        } else {
            $errors[] = "Nie znaleziono użytkownika o podanym ID.";
        }
        $stmt_user->close();
    } else {
        $errors[] = "Błąd serwera podczas przygotowania zapytania o użytkownika: " . $conn->error;
    }
}

// Jeśli użytkownik profilu został znaleziony, pobierz jego książki
if ($profile_user_data && empty($errors)) {
    $stmt_books = $conn->prepare(
        "SELECT id, title, author, price, status, image_filename
         FROM books WHERE owner_id = ? ORDER BY created_at DESC"
    );
    if ($stmt_books) {
        $stmt_books->bind_param("i", $profile_user_id);
        $stmt_books->execute();
        $result_books = $stmt_books->get_result();
        while ($row = $result_books->fetch_assoc()) {
            $profile_user_books[] = $row;
        }
        $stmt_books->close();
    } else {
        $errors[] = "Błąd serwera podczas pobierania książek użytkownika: " . $conn->error;
    }
}
?>

<div class="profile-page">
    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php elseif ($profile_user_data): ?>
        <div class="profile-header">
             <img src="uploads/avatars/<?php echo htmlspecialchars($profile_user_data['avatar_url'] ?: 'default_avatar.png'); ?>"
                  alt="Avatar użytkownika <?php echo htmlspecialchars($profile_user_data['username']); ?>" class="profile-avatar">
            <div class="profile-header-info">
                <h2>
                    <?php echo htmlspecialchars($profile_user_data['username']); ?>
                    <?php if ($is_own_profile): ?>
                        <span class="own-profile-badge">(To Twój profil)</span>
                    <?php endif; ?>
                </h2>
                <p class="profile-join-date">Dołączył/a: <?php
                    try { $date = new DateTime($profile_user_data['created_at']); echo $date->format('d F Y'); }
                    catch (Exception $e) { echo htmlspecialchars($profile_user_data['created_at']); }
                ?></p>
                 <?php if ($is_own_profile): ?>
                    <a href="edit_profile.php" class="button button-small">Edytuj profil</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-content">
            <div class="profile-description-section">
                <h3>O mnie:</h3>
                <?php if (!empty($profile_user_data['profile_description'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($profile_user_data['profile_description'])); ?></p>
                <?php elseif ($is_own_profile): ?>
                    <p>Nie dodałeś/aś jeszcze opisu swojego profilu. Możesz to zrobić w <a href="edit_profile.php">edycji profilu</a>.</p>
                <?php else: ?>
                    <p>Użytkownik nie dodał jeszcze opisu.</p>
                <?php endif; ?>
            </div>

            <?php if ($is_own_profile && isset($profile_user_data['email'])): ?>
            <div class="profile-email-section">
                <h3>Informacje prywatne:</h3>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($profile_user_data['email']); ?> (widoczny tylko dla Ciebie)</p>
            </div>
            <?php endif; ?>

            <hr>
            <h3>Książki wystawione przez <?php echo htmlspecialchars($profile_user_data['username']); ?>:</h3>
            <?php if (!empty($profile_user_books)): ?>
                <div class="books-list profile-books-list">
                    <?php foreach ($profile_user_books as $book): ?>
                        <div class="book-item">
                            <div class="book-image-container">
                                 <a href="book_details.php?id=<?php echo $book['id']; ?>">
                                    <img src="uploads/book_images/<?php echo htmlspecialchars($book['image_filename'] ?: 'placeholder.png'); ?>" alt="Okładka <?php echo htmlspecialchars($book['title']); ?>" class="book-image-thumbnail">
                                 </a>
                            </div>
                            <div class="book-info">
                                <h4><a href="book_details.php?id=<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?></a></h4>
                                <p>Autor: <?php echo htmlspecialchars($book['author']); ?></p>
                                <p>Status: <?php echo htmlspecialchars(ucfirst($book['status'])); ?></p>
                                <?php if ($book['price'] !== null): ?>
                                    <p class="price">Cena: <?php echo htmlspecialchars(number_format($book['price'], 2, ',', '.')); ?> zł</p>
                                <?php endif; ?>
                                 <?php if ($is_own_profile): ?>
                                    <div class="book-actions-owner">
                                        <a href="delete_book.php?book_id=<?php echo $book['id']; ?>" class="button button-small button-danger" onclick="return confirm('Czy na pewno chcesz usunąć tę książkę?');">Usuń</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php echo $is_own_profile ? "Nie wystawiłeś/aś" : "Użytkownik " . htmlspecialchars($profile_user_data['username']) . " nie wystawił/a"; ?> jeszcze żadnych książek.</p>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <p>Nie udało się załadować profilu użytkownika.</p>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>
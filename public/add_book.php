<?php
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = "Musisz być zalogowany, aby dodać książkę.";
    $_SESSION['flash_type'] = "warning";
    header("Location: login.php");
    exit;
}

$errors = [];
$title = $author = $isbn = $description = '';
$price = null;
$image_filename_db = null; // Nazwa pliku zdjęcia do zapisu w DB

// Definicja ścieżki do katalogu uploads
define('UPLOAD_DIR', __DIR__ . '/uploads/book_images/'); // __DIR__ to aktualny katalog (public)
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif']; // Dodaj 'image/gif' jeśli chcesz

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $isbn = trim($_POST['isbn']);
    $description = trim($_POST['description']);
    $price_input = trim($_POST['price']);

    if (empty($title)) { $errors[] = "Tytuł jest wymagany."; }
    if (empty($author)) { $errors[] = "Autor jest wymagany."; }
    if (!empty($price_input)) {
        if (!is_numeric($price_input) || $price_input < 0) {
            $errors[] = "Cena musi być poprawną liczbą nieujemną.";
        } else {
            $price = floatval($price_input);
        }
    } else {
        $price = null;
    }

    // Obsługa przesyłania pliku zdjęcia
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['image']['tmp_name'];
        $file_name = $_FILES['image']['name'];
        $file_size = $_FILES['image']['size'];
        $file_type = $_FILES['image']['type']; // MIME type od klienta, nie zawsze wiarygodny
        
        // Lepsza weryfikacja typu MIME po stronie serwera
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $real_mime_type = finfo_file($finfo, $file_tmp_path);
        finfo_close($finfo);

        if (!in_array($real_mime_type, $allowed_mime_types)) {
            $errors[] = "Niedozwolony typ pliku. Akceptowane są tylko JPG, PNG, GIF.";
        } elseif ($file_size > MAX_FILE_SIZE) {
            $errors[] = "Plik jest za duży. Maksymalny rozmiar to 2MB.";
        } else {
            // Generowanie unikalnej nazwy pliku, aby uniknąć konfliktów i problemów z nazwami
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $image_filename_db = uniqid('book_', true) . '.' . $file_extension;
            $destination_path = UPLOAD_DIR . $image_filename_db;

            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true); // Stwórz folder jeśli nie istnieje
            }

            if (!move_uploaded_file($file_tmp_path, $destination_path)) {
                $errors[] = "Nie udało się przenieść przesłanego pliku.";
                $image_filename_db = null; // Resetuj nazwę pliku jeśli błąd
            }
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
        // Jeśli plik został wysłany, ale wystąpił inny błąd niż "brak pliku"
        $errors[] = "Wystąpił błąd podczas przesyłania pliku zdjęcia. Kod błędu: " . $_FILES['image']['error'];
    }


    if (empty($errors)) {
        $owner_id = $_SESSION['user_id'];
        // Dodajemy image_filename do zapytania SQL
        $stmt = $conn->prepare("INSERT INTO books (owner_id, title, author, isbn, description, price, image_filename) VALUES (?, ?, ?, ?, ?, ?, ?)");
        // 'd' dla owner_id (integer), 's' dla stringów, 'd' dla price (decimal/double), 's' dla image_filename
        $stmt->bind_param("issssds", $owner_id, $title, $author, $isbn, $description, $price, $image_filename_db);

        if ($stmt->execute()) {
            $_SESSION['flash_message'] = "Książka została pomyślnie dodana!";
            $_SESSION['flash_type'] = "success";
            header("Location: books.php");
            exit;
        } else {
            $errors[] = "Błąd podczas dodawania książki: " . $stmt->error;
            // Jeśli wystąpił błąd zapisu do DB, a plik został już zapisany na serwerze, można go usunąć
            if ($image_filename_db && file_exists(UPLOAD_DIR . $image_filename_db)) {
                unlink(UPLOAD_DIR . $image_filename_db);
            }
        }
        $stmt->close();
    }
}
// $conn->close(); // Nie zamykaj tutaj, jeśli header.php jest na końcu
?>

<h2>Dodaj Nową Książkę</h2>

<?php if (!empty($errors)): ?>
    <div class="errors">
        <?php foreach ($errors as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form action="add_book.php" method="post" enctype="multipart/form-data">
    <div>
        <label for="title">Tytuł:</label><br>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
    </div>
    <div>
        <label for="author">Autor:</label><br>
        <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($author); ?>" required>
    </div>
   <div>
    <label for="isbn">ISBN:</label>
    <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($isbn); ?>">
    <button type="button" id="fetchGoogleBook" class="button button-small">Pobierz dane z Google</button>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isbnInput = document.getElementById('isbn');
    const titleInput = document.getElementById('title'); // Upewnij się, że inputy mają te ID
    const authorInput = document.getElementById('author');
    const descriptionInput = document.getElementById('description');
    const fetchButton = document.getElementById('fetchGoogleBook');

    if (fetchButton) {
        fetchButton.addEventListener('click', async function() {
            const isbnValue = isbnInput.value.trim().replace(/-/g, ''); // Usuń myślniki
            if (!isbnValue) {
                alert('Proszę wprowadzić numer ISBN.');
                return;
            }

            fetchButton.disabled = true;
            fetchButton.textContent = 'Pobieranie...';

            try {
                // Zmień ścieżkę, jeśli api_fetch_google_book.php jest w innym miejscu
                const response = await fetch(`api_fetch_google_book.php?isbn=${encodeURIComponent(isbnValue)}`);
                const result = await response.json();

                if (response.ok && result.success && result.data) {
                    const book = result.data;
                    titleInput.value = book.title || '';
                    authorInput.value = book.authors || ''; // Google zwraca tablicę autorów
                    descriptionInput.value = book.description || '';
                    // Możesz też wypełnić inne pola, jeśli masz je w formularzu
                    // np. kategorie, data publikacji etc.
                    // ISBN może być zaktualizowany do wersji 13-cyfrowej jeśli taka została znaleziona
                    if (book.isbn13 && isbnInput.value.length !== 13) { // Aktualizuj ISBN, jeśli jest w innym formacie
                        isbnInput.value = book.isbn13;
                    } else if (book.isbn10 && isbnInput.value.length !== 10) {
                         isbnInput.value = book.isbn10;
                    }

                    alert('Dane książki zostały pobrane!');
                } else {
                    alert('Błąd: ' + (result.error || 'Nie udało się pobrać danych książki. Sprawdź ISBN.'));
                }
            } catch (error) {
                console.error('Fetch error:', error);
                alert('Wystąpił błąd sieciowy podczas pobierania danych.');
            } finally {
                fetchButton.disabled = false;
                fetchButton.textContent = 'Pobierz dane z Google';
            }
        });
    }
});
</script>
    <div>
        <label for="description">Opis (opcjonalnie):</label><br>
        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
    </div>
    <div>
        <label for="price">Cena (zł, opcjonalnie, zostaw puste dla darmowej wymiany/nieokreślonej ceny):</label><br>
        <input type="number" step="0.01" id="price" name="price" value="<?php echo $price !== null ? htmlspecialchars($price) : ''; ?>">
    </div>
    <div>
        <label for="image">Okładka książki (opcjonalnie, max 2MB, JPG/PNG/GIF):</label><br>
        <input type="file" id="image" name="image" accept=".jpg, .jpeg, .png, .gif">
    </div>
    <div>
        <button type="submit">Dodaj Książkę</button>
    </div>
</form>

<?php
// $conn->close(); // Przenieś zamknięcie połączenia tutaj, jeśli jest potrzebne przed footerem
require_once '../includes/footer.php';
if(isset($conn)) $conn->close(); // Zamknij połączenie po załadowaniu footera
?>
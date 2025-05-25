<?php
require_once '../config/db.php'; // Na początku dla $conn
require_once '../includes/header.php'; // Header po $conn, ale przed logiką POST dla sesji

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = "Musisz być zalogowany, aby edytować profil.";
    $_SESSION['flash_type'] = "warning";
    header("Location: login.php");
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];
$user_data = null;
$errors = [];

// Ścieżki do avatarów
define('AVATAR_UPLOAD_DIR_REL', 'uploads/avatars/');
define('AVATAR_UPLOAD_DIR_ABS', __DIR__ . '/' . AVATAR_UPLOAD_DIR_REL);
define('MAX_AVATAR_SIZE', 1 * 1024 * 1024); // 1MB
$allowed_avatar_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

// Pobierz aktualne dane użytkownika do formularza
$stmt_fetch = $conn->prepare("SELECT username, email, profile_description, avatar_url FROM users WHERE id = ?");
if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $current_user_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    if ($result_fetch->num_rows === 1) {
        $user_data = $result_fetch->fetch_assoc();
    } else {
        // To nie powinno się zdarzyć dla zalogowanego użytkownika
        $_SESSION['flash_message'] = "Nie znaleziono danych użytkownika.";
        $_SESSION['flash_type'] = "danger";
        header("Location: profile.php");
        exit;
    }
    $stmt_fetch->close();
} else {
    // Błąd serwera
    $_SESSION['flash_message'] = "Błąd serwera podczas pobierania danych profilu.";
    $_SESSION['flash_type'] = "danger";
    header("Location: profile.php");
    exit;
}

// Inicjalizacja zmiennych formularza
$form_username = $user_data['username'];
$form_email = $user_data['email'];
$form_profile_description = $user_data['profile_description'] ?? '';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_username = trim($_POST['username']);
    $form_email = trim($_POST['email']);
    $form_profile_description = trim($_POST['profile_description']);

    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    $update_fields = [];
    $update_params = [];
    $update_types = "";

    // Walidacja nazwy użytkownika
    if ($form_username !== $user_data['username']) {
        if (empty($form_username)) { $errors[] = "Nazwa użytkownika nie może być pusta."; }
        elseif (strlen($form_username) < 3) { $errors[] = "Nazwa użytkownika musi mieć co najmniej 3 znaki."; }
        else {
            // Sprawdź unikalność nowej nazwy użytkownika
            $stmt_check_username = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt_check_username->bind_param("si", $form_username, $current_user_id);
            $stmt_check_username->execute();
            if ($stmt_check_username->get_result()->num_rows > 0) {
                $errors[] = "Ta nazwa użytkownika jest już zajęta.";
            }
            $stmt_check_username->close();
            if (empty($errors)) { // Dodaj do aktualizacji tylko jeśli nie ma błędów unikalności
                $update_fields[] = "username = ?";
                $update_params[] = $form_username;
                $update_types .= "s";
            }
        }
    }

    // Walidacja emaila
    if ($form_email !== $user_data['email']) {
        if (empty($form_email)) { $errors[] = "Email nie może być pusty."; }
        elseif (!filter_var($form_email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Nieprawidłowy format email."; }
        else {
            // Sprawdź unikalność nowego emaila
            $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_check_email->bind_param("si", $form_email, $current_user_id);
            $stmt_check_email->execute();
            if ($stmt_check_email->get_result()->num_rows > 0) {
                $errors[] = "Ten adres email jest już zarejestrowany.";
            }
            $stmt_check_email->close();
            if (empty($errors)) { // Dodaj do aktualizacji tylko jeśli nie ma błędów unikalności
                $update_fields[] = "email = ?";
                $update_params[] = $form_email;
                $update_types .= "s";
            }
        }
    }

    // Aktualizacja opisu profilu (jeśli się zmienił)
    if ($form_profile_description !== $user_data['profile_description']) {
        $update_fields[] = "profile_description = ?";
        $update_params[] = $form_profile_description;
        $update_types .= "s";
    }

    // Walidacja i zmiana hasła (jeśli podano nowe hasło)
    if (!empty($new_password)) {
        if (empty($current_password)) { $errors[] = "Podaj aktualne hasło, aby ustawić nowe."; }
        else {
            // Pobierz hash aktualnego hasła z bazy
            $stmt_pass = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt_pass->bind_param("i", $current_user_id);
            $stmt_pass->execute();
            $result_pass = $stmt_pass->get_result()->fetch_assoc();
            $stmt_pass->close();

            if ($result_pass && password_verify($current_password, $result_pass['password'])) {
                if (strlen($new_password) < 6) { $errors[] = "Nowe hasło musi mieć co najmniej 6 znaków."; }
                elseif ($new_password !== $confirm_new_password) { $errors[] = "Nowe hasła nie są zgodne."; }
                else {
                    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_fields[] = "password = ?";
                    $update_params[] = $new_hashed_password;
                    $update_types .= "s";
                }
            } else {
                $errors[] = "Aktualne hasło jest nieprawidłowe.";
            }
        }
    }

    // Obsługa przesyłania nowego avatara
    $new_avatar_filename = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['avatar']['tmp_name'];
        $file_name_original = $_FILES['avatar']['name'];
        $file_size = $_FILES['avatar']['size'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $real_mime_type = finfo_file($finfo, $file_tmp_path);
        finfo_close($finfo);

        if (!in_array($real_mime_type, $allowed_avatar_mime_types)) {
            $errors[] = "Niedozwolony typ pliku avatara. Akceptowane: JPG, PNG, GIF.";
        } elseif ($file_size > MAX_AVATAR_SIZE) {
            $errors[] = "Plik avatara jest za duży. Maksymalny rozmiar to 1MB.";
        } else {
            $file_extension = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));
            if (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                 $errors[] = "Niedozwolone rozszerzenie pliku avatara.";
            } else {
                $new_avatar_filename = 'avatar_' . $current_user_id . '_' . time() . '.' . $file_extension;
                $destination_path = AVATAR_UPLOAD_DIR_ABS . $new_avatar_filename;

                if (!is_dir(AVATAR_UPLOAD_DIR_ABS)) {
                    if (!mkdir(AVATAR_UPLOAD_DIR_ABS, 0755, true)) { $errors[] = "Nie udało się utworzyć katalogu na avatary."; }
                }
                if (empty($errors) && !move_uploaded_file($file_tmp_path, $destination_path)) {
                    $errors[] = "Nie udało się przenieść pliku avatara.";
                    $new_avatar_filename = null;
                } else if (empty($errors)) {
                    // Jeśli nowy avatar został pomyślnie przesłany, usuń stary (jeśli istniał i nie był domyślny)
                    if ($user_data['avatar_url'] && $user_data['avatar_url'] !== 'default_avatar.png' && file_exists(AVATAR_UPLOAD_DIR_ABS . $user_data['avatar_url'])) {
                        unlink(AVATAR_UPLOAD_DIR_ABS . $user_data['avatar_url']);
                    }
                    $update_fields[] = "avatar_url = ?";
                    $update_params[] = $new_avatar_filename;
                    $update_types .= "s";
                }
            }
        }
    } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors[] = "Błąd podczas przesyłania avatara. Kod: " . $_FILES['avatar']['error'];
    }


    // Jeśli są jakieś pola do zaktualizowania i nie ma błędów
    if (!empty($update_fields) && empty($errors)) {
        $sql_update = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $update_params[] = $current_user_id; // ID na końcu dla warunku WHERE
        $update_types .= "i";

        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update) {
            $stmt_update->bind_param($update_types, ...$update_params);
            if ($stmt_update->execute()) {
                $_SESSION['flash_message'] = "Profil zaktualizowany pomyślnie!";
                $_SESSION['flash_type'] = "success";
                // Zaktualizuj sesję jeśli zmieniono nazwę użytkownika
                if ($form_username !== $user_data['username']) {
                    $_SESSION['username'] = $form_username;
                }
                header("Location: profile.php");
                exit;
            } else {
                $errors[] = "Błąd podczas aktualizacji profilu w bazie: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $errors[] = "Błąd serwera przy przygotowaniu zapytania aktualizacji.";
        }
    } elseif (empty($update_fields) && empty($errors) && $_SERVER["REQUEST_METHOD"] == "POST" && !(isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK && $new_avatar_filename === null) && empty($new_password)) {
        // Jeśli nic się nie zmieniło i nie było błędu przy ładowaniu avatara i nie było próby zmiany hasła
         $_SESSION['flash_message'] = "Nie wprowadzono żadnych zmian.";
         $_SESSION['flash_type'] = "info";
         header("Location: profile.php");
         exit;
    }
}
?>

<div class="form-container edit-profile-page">
    <h2>Edytuj Swój Profil</h2>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="edit_profile.php" method="post" enctype="multipart/form-data">
        <fieldset>
            <legend>Podstawowe Informacje</legend>
            <div>
                <label for="username">Nazwa użytkownika:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($form_username); ?>" required minlength="3">
            </div>
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_email); ?>" required>
            </div>
            <div>
                <label for="profile_description">O mnie (opcjonalnie):</label>
                <textarea id="profile_description" name="profile_description" rows="5"><?php echo htmlspecialchars($form_profile_description); ?></textarea>
            </div>
        </fieldset>

        <fieldset>
            <legend>Zmień Avatar</legend>
            <div>
                <label>Aktualny avatar:</label>
                <img src="<?php echo AVATAR_UPLOAD_DIR_REL . htmlspecialchars($user_data['avatar_url'] ?: 'default_avatar.png'); ?>" alt="Aktualny avatar" class="profile-avatar-small">
            </div>
            <div>
                <label for="avatar">Nowy avatar (opcjonalnie, max 1MB, JPG/PNG/GIF):</label>
                <input type="file" id="avatar" name="avatar" accept=".jpg, .jpeg, .png, .gif">
            </div>
        </fieldset>

        <fieldset>
            <legend>Zmień Hasło (pozostaw puste, jeśli nie chcesz zmieniać)</legend>
            <div>
                <label for="current_password">Aktualne hasło:</label>
                <input type="password" id="current_password" name="current_password">
            </div>
            <div>
                <label for="new_password">Nowe hasło (min. 6 znaków):</label>
                <input type="password" id="new_password" name="new_password" minlength="6">
            </div>
            <div>
                <label for="confirm_new_password">Potwierdź nowe hasło:</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password" minlength="6">
            </div>
        </fieldset>

        <div>
            <button type="submit" class="button">Zapisz zmiany</button>
            <a href="profile.php" class="button button-secondary">Anuluj</a>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
<?php
require_once '../config/db.php';

$errors = [];
$username = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($username)) { $errors[] = "Nazwa użytkownika jest wymagana."; }
    if (empty($email)) { $errors[] = "Email jest wymagany."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Nieprawidłowy format email."; }
    if (empty($password)) { $errors[] = "Hasło jest wymagane."; }
    if ($password !== $password_confirm) { $errors[] = "Hasła nie są zgodne."; }

    // Sprawdzenie czy użytkownik lub email już istnieje
    if (empty($errors)) {
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $errors[] = "Użytkownik o tej nazwie lub adresie email już istnieje.";
        }
        $stmt_check->close();
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Haszowanie hasła
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed_password);

        if ($stmt->execute()) {
            if (session_status() == PHP_SESSION_NONE) { session_start(); }
            $_SESSION['flash_message'] = "Rejestracja pomyślna! Możesz się teraz zalogować.";
            $_SESSION['flash_type'] = "success";
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Błąd rejestracji: " . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close(); // Zamknij połączenie na końcu skryptu

require_once '../includes/header.php';
?>

<h2>Rejestracja</h2>

<?php if (!empty($errors)): ?>
    <div class="errors">
        <?php foreach ($errors as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form action="register.php" method="post">
    <div>
        <label for="username">Nazwa użytkownika:</label><br>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
    </div>
    <div>
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
    </div>
    <div>
        <label for="password">Hasło:</label><br>
        <input type="password" id="password" name="password" required>
    </div>
    <div>
        <label for="password_confirm">Potwierdź hasło:</label><br>
        <input type="password" id="password_confirm" name="password_confirm" required>
    </div>
    <div>
        <button type="submit">Zarejestruj</button>
    </div>
</form>

<?php require_once '../includes/footer.php'; ?>
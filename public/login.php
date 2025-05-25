<?php
require_once '../config/db.php';

$errors = [];
$username_or_email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = trim($_POST['username_or_email']);
    $password = $_POST['password'];

    if (empty($username_or_email)) { $errors[] = "Nazwa użytkownika lub email jest wymagana."; }
    if (empty($password)) { $errors[] = "Hasło jest wymagane."; }

    if (empty($errors)) {
        // Spróbuj znaleźć użytkownika po nazwie lub emailu
        $stmt = $conn->prepare("SELECT id, username, password, email FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username_or_email, $username_or_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) { // Weryfikacja hasła
                if (session_status() == PHP_SESSION_NONE) { session_start(); }
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['flash_message'] = "Zalogowano pomyślnie!";
                $_SESSION['flash_type'] = "success";
                header("Location: index.php");
                exit;
            } else {
                $errors[] = "Nieprawidłowe hasło.";
            }
        } else {
            $errors[] = "Nie znaleziono użytkownika o podanej nazwie lub adresie email.";
        }
        $stmt->close();
    }
}
$conn->close();

require_once '../includes/header.php';
?>

<h2>Logowanie</h2>

<?php if (!empty($errors)): ?>
    <div class="errors">
        <?php foreach ($errors as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form action="login.php" method="post">
    <div>
        <label for="username_or_email">Nazwa użytkownika lub Email:</label><br>
        <input type="text" id="username_or_email" name="username_or_email" value="<?php echo htmlspecialchars($username_or_email); ?>" required>
    </div>
    <div>
        <label for="password">Hasło:</label><br>
        <input type="password" id="password" name="password" required>
    </div>
    <div>
        <button type="submit">Zaloguj</button>
    </div>
</form>

<?php require_once '../includes/footer.php'; ?>
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = array(); // Usuń wszystkie zmienne sesyjne
if (ini_get("session.use_cookies")) { // Usuń ciasteczko sesyjne
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy(); // Zniszcz sesję

header("Location: index.php"); // Przekieruj na stronę główną
exit;
?>
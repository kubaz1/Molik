<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOLIK</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); // Zapobiega cache'owaniu CSS ?>">
</head>
<body>
    <header>
        <div class="container header-flex-container">
            <div class="logo-area">
                <a href="index.php" class="site-title-link">
                   
                    <h1>MOLIK</h1>
                </a>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Strona Główna</a></li>
                    <li><a href="books.php" class="<?php echo ($current_page == 'books.php' || $current_page == 'book_details.php') ? 'active' : ''; ?>">Przeglądaj</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="add_book.php" class="<?php echo ($current_page == 'add_book.php') ? 'active' : ''; ?>">Dodaj Książkę</a></li>
                        <li><a href="messages.php" class="<?php echo ($current_page == 'messages.php' || $current_page == 'view_thread.php' || $current_page == 'send_message.php') ? 'active' : ''; ?>">Wiadomości</a></li>
                        <li><a href="profile.php" class="<?php echo ($current_page == 'profile.php' || $current_page == 'edit_profile.php') ? 'active' : ''; ?>">Profil (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                        <li><a href="logout.php">Wyloguj</a></li>
                    <?php else: ?>
                        <li><a href="register.php" class="<?php echo ($current_page == 'register.php') ? 'active' : ''; ?>">Rejestracja</a></li>
                        <li><a href="login.php" class="<?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">Logowanie</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
        <?php
        if (isset($_SESSION['flash_message'])): ?>
            <div class="flash-message <?php echo htmlspecialchars($_SESSION['flash_type'] ?? 'info'); ?>">
                <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
            </div>
            <?php
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
            ?>
        <?php endif; ?>
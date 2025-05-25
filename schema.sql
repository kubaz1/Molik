-- Usuń tabele w odwrotnej kolejności zależności, jeśli istnieją
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS message_participants;
DROP TABLE IF EXISTS message_threads;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;

-- Tabela Użytkowników
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    profile_description TEXT NULL,
    avatar_url VARCHAR(255) NULL -- Domyślnie NULL, można ustawić 'default_avatar.png'
);

-- Tabela Książek
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(20) NULL,
    description TEXT NULL,
    price DECIMAL(10, 2) NULL,
    image_filename VARCHAR(255) NULL DEFAULT NULL, -- Nazwa pliku zdjęcia
    status VARCHAR(50) DEFAULT 'available', -- np. available, sold, exchanged
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela Wątków Wiadomości
CREATE TABLE message_threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    related_book_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_message_at TIMESTAMP NULL,
    subject VARCHAR(255) NULL,
    FOREIGN KEY (related_book_id) REFERENCES books(id) ON DELETE SET NULL,
    INDEX idx_last_message_at (last_message_at)
);

-- Tabela Uczestników Wątków
CREATE TABLE message_participants (
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    PRIMARY KEY (thread_id, user_id),
    FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela Wiadomości
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    sender_id INT NOT NULL,
    content TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);
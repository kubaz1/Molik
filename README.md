MOLIK - Webowa Platforma Wymiany i Sprzedaży
Webowa aplikacja typu giełda książek, która umożliwia użytkownikom rejestrację, logowanie, zarządzanie własnym profilem oraz dodawanie ogłoszeń dotyczących książek na wymianę lub sprzedaż. Użytkownicy mogą przeglądać dostępne oferty, kontaktować się z innymi ogłoszeniodawcami za pomocą wbudowanego systemu wiadomości oraz personalizować swoje profile, dodając m.in. avatar i opis.
Projekt został stworzony przy użyciu PHP po stronie backendu oraz standardowych technologii frontendowych (HTML, CSS, JavaScript). Baza danych MySQL służy do przechowywania informacji o użytkownikach, książkach i wiadomościach.

Kluczowe Funkcjonalności:
  •	Uwierzytelnianie i Autoryzacja:
o	Rejestracja nowych użytkowników.
o	Logowanie i wylogowywanie.
o	System sesji.
  •	Zarządzanie Profilem Użytkownika:
o	Wyświetlanie profilu własnego oraz innych użytkowników.
o	Możliwość edycji własnych danych (nazwa użytkownika, email, opis).
o	Możliwość zmiany hasła.
o	Przesyłanie i wyświetlanie avatara profilowego (z domyślnym obrazkiem placeholder).
•	Ogłoszenia Książkowe:
o	Dodawanie nowych ogłoszeń książkowych (tytuł, autor, ISBN, opis, cena/wymiana).
o	Przesyłanie i wyświetlanie zdjęć okładek książek (z domyślnym obrazkiem placeholder).
o	Przeglądanie listy dostępnych książek.
o	Wyświetlanie szczegółów pojedynczej książki.
o	Możliwość usunięcia własnych ogłoszeń książkowych.
o	Możliwość pobrania danych książki za pomocą api 
  •	Komunikacja:
o	System prywatnych wiadomości między użytkownikami.
o	Inicjowanie rozmowy z poziomu ogłoszenia książkowego.
o	Skrzynka odbiorcza (messages.php) z listą wątków konwersacji.
o	Podgląd pełnej konwersacji w danym wątku (view_thread.php) z możliwością odpowiedzi.
o	Wskaźnik nieprzeczytanych wiadomości.
  • Interfejs Użytkownika:
o	Responsywny design dostosowany do różnych rozmiarów ekranu.
o	Stylizacja wiadomości inspirowana popularnymi komunikatorami.

Użyte Technologie:
  •	Frontend:
o	HTML5
o	CSS3 (Flexbox, Grid, RWD)
o	JavaScript (drobne usprawnienia, walidacja po stronie klienta - jeśli dodano)
  •	Backend:
o	PHP 
  •	Baza Danych:
o	MySQL
  •	Serwer WWW:
o XAMPP
  •	Kontrola Wersji:
o	GitHub
  •	API:
o	GOOGLE BOOKS API

Struktura Projektu:
/giełda_ksiazek_php/
|-- config/ # Pliki konfiguracyjne (np. połączenie z DB)
|-- includes/ # Wspólne elementy strony (header, footer)
|-- public/ # Główny folder dostępny publicznie (document root)
| |-- css/ # Arkusze stylów CSS
| |-- uploads/ # Folder na pliki przesyłane przez użytkowników
| | |-- avatars/
| | |-- book_images/
| |-- *.php # Skrypty PHP i strony HTML generowane przez PHP
|-- schema.sql # Schemat bazy danych
|-- README.md # Ten plik

Instalacja i Uruchomienie (Lokalnie):
1.	Sklonuj repozytorium: git clone [URL_REPOZYTORIUM]
2.	Zaimportuj plik schema.sql do swojej bazy danych MySQL/MariaDB (np. o nazwie gielda_ksiazek_db).
3.	Skonfiguruj połączenie z bazą danych w pliku config/db.php, podając swój host, nazwę użytkownika, hasło i nazwę bazy danych.
4.	Upewnij się, że foldery public/uploads/avatars/ oraz public/uploads/book_images/ istnieją i są zapisywalne dla serwera WWW.
5.	Skieruj swój lokalny serwer WWW (np. Apache w XAMPP) na folder public/ jako document root lub otwórz projekt w przeglądarce przez http://localhost/ścieżka_do_projektu/public/.

Autorzy:
Miłosz Musielak 51304
Jakub Żywiczko 51377
Eliasz Marcinkowski 51593

<h2> MOLIK - Webowa Platforma Wymiany i Sprzedaży </h2>

Webowa aplikacja typu giełda książek, która umożliwia użytkownikom rejestrację, logowanie, zarządzanie własnym profilem oraz dodawanie ogłoszeń dotyczących książek na wymianę lub sprzedaż. Użytkownicy mogą przeglądać dostępne oferty, kontaktować się z innymi ogłoszeniodawcami za pomocą wbudowanego systemu wiadomości oraz personalizować swoje profile, dodając m.in. avatar i opis.
Projekt został stworzony przy użyciu PHP po stronie backendu oraz standardowych technologii frontendowych (HTML, CSS, JavaScript). Baza danych MySQL służy do przechowywania informacji o użytkownikach, książkach i wiadomościach.
<hr />

<h3>Kluczowe Funkcjonalności:  </h3>
<h4>Uwierzytelnianie i Autoryzacja:  </h4>
o	Rejestracja nowych użytkowników.  
o	Logowanie i wylogowywanie.  
o	System sesji.  
  
<h4>Zarządzanie Profilem Użytkownika: </h4> 
 o	Wyświetlanie profilu własnego oraz innych użytkowników.  
 o	Możliwość edycji własnych danych (nazwa użytkownika, email, opis).  
 o	Możliwość zmiany hasła.  
 o	Przesyłanie i wyświetlanie avatara profilowego (z domyślnym obrazkiem placeholder).  
  
<h4>Ogłoszenia Książkowe:  </h4>
o	Dodawanie nowych ogłoszeń książkowych (tytuł, autor, ISBN, opis, cena/wymiana).  
o	Przesyłanie i wyświetlanie zdjęć okładek książek (z domyślnym obrazkiem placeholder).  
o	Przeglądanie listy dostępnych książek.  
o	Wyświetlanie szczegółów pojedynczej książki.  
o	Możliwość usunięcia własnych ogłoszeń książkowych.  
o	Możliwość pobrania danych książki za pomocą api   
  
<h4>Komunikacja:  </h4>
o	System prywatnych wiadomości między użytkownikami.  
o	Inicjowanie rozmowy z poziomu ogłoszenia książkowego.  
o	Skrzynka odbiorcza (messages.php) z listą wątków konwersacji.  
o	Podgląd pełnej konwersacji w danym wątku (view_thread.php) z możliwością odpowiedzi.  
o	Wskaźnik nieprzeczytanych wiadomości.  
  
<h4>Interfejs Użytkownika: </h4>  
o	Responsywny design dostosowany do różnych rozmiarów ekranu.  
o	Stylizacja wiadomości inspirowana popularnymi komunikatorami.  

<hr />

<h3>Użyte Technologie: </h3> 
<h4>Frontend: </h4>  
o	HTML5  
o	CSS3 (Flexbox, Grid, RWD)   
o	JavaScript  
<h4>Backend: </h4>  
o	PHP  
<h4> Baza Danych:  </h4>
o	MySQL  
<h4>Serwer WWW:  </h4>
o XAMPP  
<h4>Kontrola Wersji:</h4>
o	GitHub  
<h4>API: </h4>
o	GOOGLE BOOKS API  

<hr />

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

<hr />

<h3>Instalacja i Uruchomienie (Lokalnie):  </h3>  
1.	Sklonuj repozytorium: git clone [URL_REPOZYTORIUM] <br />
2.	Zaimportuj plik schema.sql do swojej bazy danych MySQL/MariaDB (np. o nazwie gielda_ksiazek_db).<br />
3.	Skonfiguruj połączenie z bazą danych w pliku config/db.php, podając swój host, nazwę użytkownika, hasło i nazwę bazy danych. <br />
4.	Upewnij się, że foldery public/uploads/avatars/ oraz public/uploads/book_images/ istnieją i są zapisywalne dla serwera WWW. <br />
5.	Skieruj swój lokalny serwer WWW (np. Apache w XAMPP) na folder public/ jako document root lub otwórz projekt w przeglądarce przez http://localhost/ścieżka_do_projektu/public/. <br />  

<hr />

<h3>Autorzy: </h3>
Miłosz Musielak 51304   <br />
Jakub Żywiczko 51377   <br />
Eliasz Marcinkowski 51593   <br />

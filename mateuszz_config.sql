-- phpMyAdmin SQL Dump
-- version 4.6.5.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 17, 2018 at 02:45 PM
-- Server version: 10.1.21-MariaDB
-- PHP Version: 7.1.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mateuszz_config`
--

-- --------------------------------------------------------

--
-- Table structure for table `doc_szablony`
--

CREATE TABLE `doc_szablony` (
  `id` int(11) NOT NULL,
  `plik` varchar(100) COLLATE utf8_polish_ci NOT NULL,
  `nazwa` varchar(100) COLLATE utf8_polish_ci NOT NULL,
  `tresc` longtext COLLATE utf8_polish_ci NOT NULL,
  `tagi` varchar(512) COLLATE utf8_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Dumping data for table `doc_szablony`
--

INSERT INTO `doc_szablony` (`id`, `plik`, `nazwa`, `tresc`, `tagi`) VALUES
(1, 'oswiadczenie_ogolne_pracownika.html', 'Oświadczenie ogólne pracownika', '<!DOCTYPE html>\r\n<html lang=\"pl\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <title>Upoważnienie</title>\r\n\r\n    <link rel=\"stylesheet\" href=\"/css/templates/reset.css\">\r\n    <link rel=\"stylesheet\" href=\"/css/templates/style.css\">\r\n    <link rel=\"stylesheet\" href=\"/css/docs.css\">\r\n\r\n    <style type=\"text/css\">\r\n\r\n    </style>\r\n</head>\r\n\r\n<body style=\"margin: 0 5px; padding: 0\">\r\n\r\n<div id=\"page-wrap\" >\r\n    <div>\r\n        <div style=\"float:left\">\r\n            {$nazwa_firmy|strtoupper}<br />\r\n        </div>\r\n        <div style=\"float: right\">\r\n            {$city}, {$data_z_ustawien}\r\n        </div>\r\n    </div>\r\n    <div class=\"clear space\"></div>\r\n\r\n    <h1 class=\"fn\">OŚWIADCZENIE NR {$numer}</h1>\r\n\r\n    <div class=\"clear space\"></div>\r\n    <p class=\"center header\">JA, NIŻEJ PODPISANY(-A) -  {$imie} {$nazwisko}</p>\r\n    <p class=\"center header\">ZATRUDNIONY(-A) NA STANOWISKU {$stanowisko} </p>\r\n    <div class=\"clear space\"></div>\r\n    <table>\r\n        <tr>\r\n            <td width=\"75%\">Potwierdzam, że podane do Umowy dane są prawdziwe:</td>\r\n            <td width=\"25%\"></td>\r\n        </tr>\r\n        <tr>\r\n            <td width=\"75%\">Oświadczam, że zostałem(łam) poinformowany (a) o ciążącym na mnie obowiązku ochrony danych osobowych z mocy ustawy z dnia 29 sierpnia 1997 r. o ochronie danych osobowych (Dz. U. Nr 101, poz. 926 z późń. zm.)</td>\r\n            <td width=\"25%\"></td>\r\n        </tr>\r\n        <tr>\r\n            <td width=\"75%\">Oświadczam, że zapoznałem (łam) się z Polityką Bezpieczeństwa oraz Instrukcją Zarządzania Systemem Informatycznym służącym do przetwarzania danych osobowych oraz przepisami prawa dotyczącymi ochrony danych osobowych (ustawa o ochronie danych osobowych z 29 sierpnia 1997 oraz Rozporządzenie MSWiA z 29 kwietnia 2004 r (Dz. U Nr 100, poz. 1024) i zobowiązuje się do ich przestrzegania.</td>\r\n            <td width=\"25%\"></td>\r\n        </tr>\r\n        <tr>\r\n            <td width=\"75%\">Oświadczam, że zobowiązuje się do zachowania w tajemnicy w okresie zatrudnienia jak i po jego ustaniu, wszelkich informacji pozostających w związku z danymi osobowymi, do, których mam dostęp z racji zajmowanego stanowiska czy wykonywanych prac.</td>\r\n            <td width=\"25%\"></td>\r\n        </tr>\r\n        <tr>\r\n            <td width=\"75%\">Wyrażam zgodę na publikowanie mojego wizerunku przez Pracodawcę</td>\r\n            <td width=\"25%\"></td>\r\n        </tr>\r\n        <tr>\r\n            <td width=\"75%\">Wyrażam zgodę na umieszczenie danych osobowych w bazie danych Pracodawcy, który będzie ich administratorem, na ich przekazywanie osobom trzecim, a także na ich przetwarzanie zgodnie z ustawą z dnia 29.08.1997 r. o ochronie danych osobowych (Dz. U. z 2002 r., Nr 101, poz. 926 ze zm.) w celach marketingowych, reklamowych, informacyjnych oraz przyszłych wynikających z działalności Pracodawcy lub osoby trzecie, działające z jego upoważnienia i na jego rzecz, a także zgoda na przesyłanie materiałów promocyjnych i reklamowych innych podmiotów. Oświadczam, że zostałem poinformowany o prawie wglądu do swoich danych i możliwości żądania uzupełnienia, uaktualnienia, sprostowania oraz czasowego lub stałego wstrzymania ich przetwarzania lub ich usunięcia.</td>\r\n            <td width=\"25%\"></td>\r\n        </tr>\r\n        <tr>\r\n            <td width=\"75%\">Nie wyrażam zgody na umieszczenie danych osobowych w bazie danych Pracodawcy dla celów marketingowych, reklamowych, informacyjnych oraz przyszłych wynikających z działalności Pracodawcy lub osoby trzecie, działające z jego upoważnienia i na jego rzecz, a także zgoda na przesyłanie materiałów promocyjnych i reklamowych innych podmiotów.</td>\r\n            <td width=\"25%\"></td>\r\n        </tr>\r\n    </table>\r\n    <div class=\"clear space\"></div>\r\n    <p style=\"text-transform: uppercase;font-size: 12px\">Administratorem Danych Osobowych jest {$ado}</p>\r\n</div>\r\n</body>\r\n\r\n</html>\r\n', '$data_z_ustawien;$nazwa_firmy;$city;$data;$numer;$imie;$nazwisko;$stanowisko;$ado'),
(2, 'upowaznienie_do_przetwarzania_d_o.html', 'Upoważnienie do przetwarzania danych osobowych', '<!DOCTYPE html>\r\n<html lang=\"pl\">\r\n<head>\r\n     <meta charset=\"UTF-8\">\r\n     <title>Upoważnienie</title>\r\no \r\no <link rel=\"stylesheet\" href=\"/css/templates/reset.css\">\r\no <link rel=\"stylesheet\" href=\"/css/templates/style.css\">\r\n     <link rel=\"stylesheet\" href=\"/css/docs.css\">\r\n\r\n     <style type=\"text/css\">\r\n        \r\n     </style>\r\n</head>\r\n\r\n<body style=\"margin: 0 5px; padding: 0\">\r\n\r\n    <div id=\"page-wrap\" >\r\n        <div>\r\n            <div style=\"float:left\">\r\n                {$nazwa_firmy|strtoupper}<br />\r\n            </div>\r\n            <div style=\"float: right\">\r\n                {$city}, {$data_z_ustawien}\r\n            </div>\r\n        </div>\r\n        <div class=\"clear space\"></div>\r\n\r\no<h1 class=\"fn\">UPOWAŻNIENIE DO PRZETWARZANIA DANYCH OSOBOWYCH <br />NR {$number}</h1>\r\n\r\no<div class=\"clear space\"></div>\r\no\r\no<p class=\"center\">\r\n            Na podstawie art.37 ustawy z dnia 29 sierpnia 1997 r. o ochronie danych osobowych (Dz. U. z 2002 r. Nr 101, poz. 926 z późń. zm.)\r\n        </p>\r\no\r\no<div class=\"clear space\"></div>\r\n\r\n        <p class=\"center header\">UPOWAŻNIAM</p>\r\n        <h2 class=\"center header\">PANIĄ/PANA {$imie} {$nazwisko}</h2>\r\n        <p class=\"center header\">DO PRZETWARZANIA DANYCH OSOBOWYCH</p>\r\n        <div class=\"clear space\"></div>\r\n        <span class=\"header\">SZCZEGÓŁY:</span>\r\n        <table>\r\n            <tr>\r\n                <td width=\"25%\">FORMA ZATRUDNIENIA/WSPÓŁPRACY</td>\r\n                <td width=\"25%\">UMOWA  {if $rodzajUmowy == \'o-prace\'}O PRACĘ{else if $rodzajUmowy==\'cywilnoprawna\'}CYWILNOPRAWNA{else if $rodzajUmowy==\'dzialalnosc-g\'}działalność gospodarcza{/if}\r\n                <td width=\"25%\">STANOWISKO</td>\r\n                <td width=\"25%\">{$stanowisko|strtoupper}</td>\r\n            </tr>\r\n            <tr>\r\n                <td >UPOWAŻNIENIE OD (DATA)</td>\r\n                <td>{$data}</td>\r\n                <td>UPOWAŻNIENIE DO (DATA)</td>\r\n                <td>BEZTERMINOWO</td>\r\n            </tr>\r\n            <tr>\r\n                <td colspan=\"2\">NADANO IDENTYFIKATOR</td>\r\n                <td colspan=\"2\">{$login_do_systemu}</td>\r\n            </tr>\r\n            <tr>\r\n                <td colspan=\"1\">ZAKRES</td>\r\n                <td colspan=\"3\">{$nazwy_zbiorow|substr:1}</td>\r\n            </tr>\r\n        </table>\r\n        <div class=\"clear space\"></div>\r\n        <div class=\"clear space\"></div>\r\no<dl class=\"description right\">\r\n\r\no<dt>\r\n            <p style=\"text-transform: uppercase;font-size: 12px\">Pełnomocnik Administratora Danych osobowy</p>\r\n                (data,podpis)\r\n            </dt>\r\no</dl>\r\n    </div>\r\n</body>\r\n\r\n</html>', '$data_z_ustawien;$nazwa_firmy;$city;$data;$number;$imie;$nazwisko;$rodzajUmowy;$stanowisko;$data;$login_do_systemu;$nazwy_zbiorow;'),
(3, 'upowaznienie_klucze.html', 'Upoważnienie do kluczy', '<!DOCTYPE html>\r\n<html lang=\"pl\">\r\n<head>\r\n     <meta charset=\"UTF-8\">\r\n     <title>Upoważnienie</title>\r\no \r\no <link rel=\"stylesheet\" href=\"/css/templates/reset.css\">\r\no <link rel=\"stylesheet\" href=\"/css/templates/style.css\">\r\n\r\n     <style type=\"text/css\">\r\n        \r\n     </style>\r\n</head>\r\n\r\n<body>\r\n\r\n    <div id=\"page-wrap\">\r\no<div class=\"clear space\"></div>       \r\no<div class=\"clear space\"></div>\r\n        \r\no<h1 class=\"fn\">Upoważnienie nr {$number}</h1>\r\no<h2>do dysponowania kluczami do pomieszczeń gdzie są przetwarzane oraz gromadzone dane osobowe</h2>\r\no\r\n        <div class=\"clear space\"></div>\r\no<div class=\"clear space\"></div>\r\no\r\no<span class=\"align header\">Upoważniam</span>\r\n\r\no<dl>           \r\n            <dt>Pana/Panią</dt>\r\n            <dd>\r\n                <span class=\"underline center\">{$imie} {$nazwisko}</span>\r\n            </dd>\r\n            \r\n            <dd class=\"clear\"></dd>\r\no<dd class=\"clear\"></dd>\r\n            \r\n            <dt>zatrudnioną (ego)</br><span class=\"small\">umowa {if $rodzajUmowy == \'o-prace\'}o pracę{else if $rodzajUmowy==\'cywilnoprawna\'}cywilnoprawna{else if $rodzajUmowy==\'dzialalnosc-g\'}działalność gospodarcza{/if}</span></br>{*odbywa staż/praktykę**}</dt>\r\n            <dd>\r\no<div class=\"left\">W</div>\r\no<div class=\"right\">\r\no<span class=\"underline\">{$company_description|strtoupper}</span>\r\no<span class=\"underline\">{$adres|strtoupper}</span>\r\no<span class=\"underline\">&nbsp;</span>\r\no</div>\r\n            </dd>\r\n            \r\n            <dd class=\"clear\"></dd>\r\no<dd class=\"clear\"></dd>\r\n            \r\n            <dt>na stanowisku</dt>\r\n            <dd>\r\no<span class=\"underline\">{$stanowisko|strtoupper}</span>\r\n            </dd>\r\n            \r\n            <dd class=\"clear\"></dd>\r\n        </dl>\r\no\r\no\r\no<div class=\"clear space\"></div>\r\no\r\no<p>do dysponowania od dnia {$data_z_ustawien} r kluczami do:</p>\r\no\r\no<div class=\"clear space\"></div>\r\no\r\no<p class=\"upline\">\r\nopomieszczeń w których są przechowywane dane osobowe - pomieszczenia:\r\no</p>\r\no<p class=\"center upline bottomline\">\r\no{$klucze}\r\no</p>\r\n\r\no<div class=\"clear space\"></div>\r\no<div class=\"clear space\"></div>\r\no<div class=\"clear space\"></div>\r\no<div class=\"clear space\"></div>\r\no\r\no<dl class=\"description right\">\r\no<dd></dd>\r\no<dt>data i podpis Administratora Danych Osobowych</dt>\r\no</dl>\r\no\r\no<div class=\"clear space\"></div>\r\no<div class=\"clear space\"></div>\r\no\r\no<span class=\"small\">\r\no* nie właściwe skreślić\r\no</span>\r\n    </div>\r\n\r\n</body>\r\n\r\n</html>', '$data_z_ustawien;$number;$imie;$nazwisko;$rodzajUmowy;$company_description;$adres;$stanowisko;$data;$klucze;'),
(4, 'oswiadczenie_o_wyrazeniu_zgody_na_przetwarznie_danych_osobowych.html', 'Zgoda na przetwarzanie danych osobowych poza firmą', '<!DOCTYPE html>\r\n<html lang=\"pl\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <title>Dokument wykonania kopii zapasowych</title>\r\n\r\n    <link rel=\"stylesheet\" href=\"/css/templates/reset.css\">\r\n    <link rel=\"stylesheet\" href=\"/css/templates/style.css\">\r\n    <link rel=\"stylesheet\" href=\"/css/docs.css\">\r\n\r\n    <style type=\"text/css\">\r\n\r\n    </style>\r\n</head>\r\n\r\n<body style=\"margin: 0 5px; padding: 0\">\r\n\r\n<div id=\"page-wrap\" >\r\n    <div>\r\n        <div style=\"float:left\">\r\n            {$data.nazwa_organizacji|strtoupper}<br />\r\n        </div>\r\n        <div style=\"float: right\">\r\n            {$data.city}, {$data_z_ustawien|date_format:\'%Y-%m-%d\'}\r\n        </div>\r\n    </div>\r\n    <div class=\"clear space\"></div>\r\n\r\n    <h1 class=\"fn\">\r\n        ZGODA NA PRZETWARZANIE DANYCH OSOBOWYCH<br />\r\n        POZA WYZNACZONYM OBSZAREM\r\n        <br />NR {$data.number_dokument}\r\n    </h1>\r\n    <div class=\"clear space\"></div>\r\n    <p>\r\n        Na podstawie procedury 1 i) określonej w Polityce Bezpieczeństwa rozdział 8 - System zabezpieczeń danych osobowych\r\n    </p>\r\n    <div class=\"clear space\"></div>\r\n    <h2 class=\"center\">WYRAŻAM ZGODĘ BY<br />\r\n        PANI/PAN {$data.imie} {$data.nazwisko}<br />\r\n        PRZETWARZAŁ DANE OSOBOWE POZA WYZNACZONYM OBSZAREM\r\n    </h2>\r\n    <p>SZCZEGÓŁY:</p>\r\n    <table>\r\n        <tr>\r\n            <td width=\"25%\">FORMA ZATRUDNIENIA/WSPÓŁPRACY:</td>\r\n            <td width=\"25%\">{if $data.rodzajUmowy == \'o-prace\'}O PRACĘ{else if $data.rodzajUmowy==\'cywilnoprawna\'}CYWILNOPRAWNA{else if $rodzajUmowy==\'dzialalnosc-g\'}działalność gospodarcza{/if}</td>\r\n            <td width=\"25%\">STANOWISKO:</td>\r\n            <td width=\"25%\">{$data.stanowisko}</td>\r\n        </tr>\r\n        <tr>\r\n            <td>UPOWAŻNIENIE OD (DATA):</td>\r\n            <td>{$data.data|date_format:\'%Y-%m-%d\'}</td>\r\n            <td>UPOWAŻNIENIE DO (DATA):</td>\r\n            <td>{$data.data_do}</td>\r\n        </tr>\r\n        <tr>\r\n            <td>IDENTYFIKATOR:</td>\r\n            <td colspan=\"3\">{$data.login_do_systemu}</td>\r\n        </tr>\r\n        <tr>\r\n            <td>ZAKRES (ZBIÓR/DOKUMENT):</td>\r\n            <td colspan=\"3\">{$data.zbiory}</td>\r\n        </tr>\r\n    </table>\r\n    <div class=\"clear space\"></div>\r\n    <div class=\"clear space\"></div>\r\n    <dl class=\"description right\">\r\n\r\n        <dt>\r\n        <p style=\"text-transform: uppercase;font-size: 12px\">Pełnomocnik Administratora Danych osobowy</p>\r\n        (data,podpis)\r\n        </dt>\r\n    </dl>\r\n    <div class=\"clear space\"></div>\r\n    <div class=\"clear space\"></div>\r\n    <div class=\"clear space\"></div>\r\n    <p>\r\n        Otrzymałem instruktaż odnośnie zachowania bezpieczeństwa informacji oraz jestem świadomy odpowiedzialności, jaka na mnie ciąży.<br />\r\n    </p>\r\n    <p class=\"center\">\r\n        (Data, podpis osoby otrzymującej zgodę)\r\n    </p>\r\n</div>\r\n</body>\r\n\r\n</html>\r\n', '$data_z_ustawien;$data.nazwa_organizacji;$data.city;$data.data;$data.number_dokument;$data.imie;$data.nazwisko;$data.rodzajUmowy;$data.stanowisko;$data.data;$data.data_do;$data.login_do_systemu;$data.zbiory;');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `content` text COLLATE utf8_polish_ci NOT NULL,
  `nazwa` varchar(200) COLLATE utf8_polish_ci NOT NULL,
  `aktywna` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `content`, `nazwa`, `aktywna`) VALUES
(1, 'Dla firm', 'Dla firm', 1),
(2, '<a href=\"http://www.kryptos.co\">Kryptos.co</a>', 'Pomoc', 1),
(3, '<a href=\"http://www.kryptos.co\">Kryptos.co</a>', 'O nas', 1),
(4, '<html>\r\n<head>\r\n	<title></title>\r\n</head>\r\n<body>\r\n<p>Wsparcie</p>\r\n</body>\r\n</html>\r\n', 'Wsparcie', 1),
(5, 'Warunki korzystania', 'Warunki korzystania', 1);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_levels`
--

CREATE TABLE `subscription_levels` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `currency` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `subscription_levels`
--

INSERT INTO `subscription_levels` (`id`, `name`, `price`, `currency`, `description`) VALUES
(0, 'System', 0, '', ''),
(1, 'Basic', 1000, 'PLN', 'Pakiet Basic'),
(2, 'Standard', 2000, 'PLN', 'Pakiet Standard'),
(3, 'Professional', 3000, 'PLN', 'Pakiet Professional'),
(4, 'Enterprise', 4000, 'PLN', 'Pakiet Enterprise');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_levels_limits`
--

CREATE TABLE `subscription_levels_limits` (
  `name` varchar(255) NOT NULL,
  `type` int(11) NOT NULL,
  `limit` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `subscription_levels_limits`
--

INSERT INTO `subscription_levels_limits` (`name`, `type`, `limit`) VALUES
('Application_Model_KontaBankowe', 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_payment_config`
--

CREATE TABLE `subscription_payment_config` (
  `id` varchar(255) NOT NULL,
  `crc` varchar(255) NOT NULL,
  `url_direct` varchar(255) NOT NULL,
  `url_verify` varchar(255) NOT NULL,
  `merchant_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `subscription_payment_config`
--

INSERT INTO `subscription_payment_config` (`id`, `crc`, `url_direct`, `url_verify`, `merchant_id`) VALUES
('1', '92c1e9f12d85fbe8', 'https://sandbox.przelewy24.pl/trnDirect', 'https://sandbox.przelewy24.pl/trnVerify', 58551),
('2', '400f75dbdffcb34d', 'https://secure.przelewy24.pl/trnDirect', 'https://secure.przelewy24.pl/trnVerify', 58551);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_transactions`
--

CREATE TABLE `subscription_transactions` (
  `id` int(11) NOT NULL,
  `subdomain` varchar(255) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `verify_status` varchar(255) NOT NULL,
  `paid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `subscription_transactions`
--

INSERT INTO `subscription_transactions` (`id`, `subdomain`, `session_id`, `price`, `order_id`, `verify_status`, `paid`) VALUES
(1, '', 'IobBmyUmk6Hzt1aX9NnI', 0, 0, '', 1),
(2, '', 'Tp0losOwL8GMYEgCrfjO', 0, 0, '', 0),
(3, '', 'Zxn4hEQbmu6DuJGrvpWS', 0, 0, '', 0),
(4, '', 'rVLN1Og1i00P6L4HvCGA', 0, 0, '', 0),
(5, '', '7zPiOo2vQXhDZ5T1oU1d', 10000, 0, '', 0),
(6, '', '6KHKYuHHCWlHSHW171D3', 10000, 0, '', 0),
(7, '', 'lLk6Vzm0xfoFNcSXsuqm', 10000, 0, '', 0),
(8, '', 'WE9jkvl7YKBxG1xHL9Hc', 10000, 0, '', 0),
(9, '', 'mBKyh43wqHaAeRG6O3V7', 10000, 0, '', 0),
(10, '', 'hThevC2P3BxIMRVPHJqg', 10000, 0, '', 0),
(11, '', '', 0, 109162394, 'error=0', 1);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_transactions_log`
--

CREATE TABLE `subscription_transactions_log` (
  `id` int(11) NOT NULL,
  `subdomain` int(11) NOT NULL,
  `data` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `subscription_transactions_log`
--

INSERT INTO `subscription_transactions_log` (`id`, `subdomain`, `data`, `created_at`) VALUES
(1, 8331794, 'Received data', '0000-00-00 00:00:00'),
(2, 8331794, 'Received data. OrderId: 0 Sign: 0', '0000-00-00 00:00:00'),
(3, 8331794, 'Received data. OrderId: 0 Sign: test', '0000-00-00 00:00:00'),
(4, 8331794, 'Received data. OrderId: 109162394 Sign: ca5fc34a9ab536fe408944608d548b8f', '2017-06-12 13:45:18'),
(5, 8331794, 'Verification. OrderId: 109162394 Sign: ca5fc34a9ab536fe408944608d548b8f. Result: error=0', '2017-06-12 13:45:19');

-- --------------------------------------------------------

--
-- Table structure for table `systems`
--

CREATE TABLE `systems` (
  `subdomain` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `is_pending` bit(1) NOT NULL,
  `is_waiting` bit(1) NOT NULL,
  `token` varchar(255) NOT NULL,
  `system_ready` bit(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `subscription_end` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `systems`
--

INSERT INTO `systems` (`subdomain`, `type`, `email`, `is_pending`, `is_waiting`, `token`, `system_ready`, `created_at`, `subscription_end`) VALUES
('11196076', '1', 'info@anton-pribora.ru', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'd45ef2006d8af522fd075fbb0e18d791', b'1111111111111111111111111111111', '2017-09-22 14:56:37', NULL),
('12263778', '1', 'shadeborn.iod@gmail.com', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '6934c72390b959079a18f1a27ba10c97', b'1111111111111111111111111111111', '2017-11-03 08:54:00', NULL),
('12756062', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '3d8a29dbc3f512a589c9cdc0d297dac1', b'1111111111111111111111111111111', '2017-07-13 09:25:10', NULL),
('13138128', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'c43c6fce89703ce31b46df4987a375b8', b'1111111111111111111111111111111', '2017-09-22 09:19:34', NULL),
('13341040', '1', 'info@anton-pribora.ru', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '683912ea1d138d36404aba52a8b91acc', b'1111111111111111111111111111111', '2017-09-22 16:08:11', NULL),
('13343821', '1', 'handzlik@gmail.com', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '9d83162deec9a626d7ca4ebc7bc822c4', b'1111111111111111111111111111111', '2017-07-12 10:12:29', NULL),
('15150586', '2', 'info@herbypolski.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'eb05caebe1b7bb4e13b3340c9fd1829c', b'1111111111111111111111111111111', '2017-07-05 14:27:50', NULL),
('16950318', '1', 'sad@sad.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'a3ece03066238facb1bca4b563320df4', b'1111111111111111111111111111111', '2017-07-06 12:20:53', NULL),
('19558853', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'a13a83ea888c196697aa004f1a824a9e', b'1111111111111111111111111111111', '2017-10-31 10:23:55', NULL),
('20524522', '1', 'info@anton-pribora.ru', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '694c32ecd524abcff921ef8c720e7e0e', b'1111111111111111111111111111111', '2017-09-25 09:35:48', NULL),
('21037421', '1', 'handzlik@gmail.com', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'e361f0e0df0faa9a669925d5d1ffe994', b'1111111111111111111111111111111', '2017-07-12 10:23:51', NULL),
('2190977', '3', 'sylwia.strepiak@smjutrzenka.krakow.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '804e7383b6c82bbc990afcbd66d032a5', b'1111111111111111111111111111111', '2017-10-20 11:21:39', NULL),
('2207168', '1', 'info@anton-pribora.ru', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'a205b1d5578b6e8d712eb377963faba8', b'1111111111111111111111111111111', '2017-09-19 09:10:56', NULL),
('2296737', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '75eb35d91c64e4c4968dfd72d42fa5ee', b'1111111111111111111111111111111', '2017-07-25 14:31:36', NULL),
('24533184', '1', 'handzli.k@gmail.com', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'dda326ecfe1f8955abfe2e595783c685', b'1111111111111111111111111111111', '2017-07-12 09:55:52', NULL),
('26382305', '3', 'dyrektor@bibliotekakrynica.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '0cf1df1838d96f52b57f471e424ee509', b'1111111111111111111111111111111', '2017-09-27 09:14:39', NULL),
('26730269', '1', 'bok@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '4641cea66bbbe08114bd29e73a317909', b'1111111111111111111111111111111', '2017-10-18 14:24:27', NULL),
('2721169', '2', 'dsads@dsa.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'a9cf934c32a21f3771c134ac8fa23c43', b'1111111111111111111111111111111', '0000-00-00 00:00:00', NULL),
('29579650', '2', 'it@klett.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'b3bdcf4916515d85705fd74de7c361ea', b'1111111111111111111111111111111', '2017-07-06 17:09:01', NULL),
('32376804', '3', 'dan.wie@gmail.com', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '39765862ba6d46257f74e165f45dd28b', b'1111111111111111111111111111111', '2017-10-28 09:56:59', NULL),
('33953654', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '9a10c487006b7ce780cdc57a5bc92220', b'1111111111111111111111111111111', '2017-08-25 09:27:11', NULL),
('35436900', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'edd73fd0a1b855e3be38c9ab8eaa527e', b'1111111111111111111111111111111', '2017-09-25 16:46:25', NULL),
('36299475', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'f57adbe8cb1ae5b8442b034174237889', b'1111111111111111111111111111111', '2017-07-13 09:27:50', NULL),
('36899972', '2', 'it@klett.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '8a1d8f2b1eba6ee4419f833539bb6902', b'1111111111111111111111111111111', '2017-07-07 21:44:30', NULL),
('37065443', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '27d8174c5bb463bf8194ed06ad351721', b'1111111111111111111111111111111', '2017-09-19 09:10:35', NULL),
('37172897', '2', 'it@klett.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '7e6f17630bb38fd0ef8cdb1471a2a213', b'1111111111111111111111111111111', '2017-07-06 13:01:10', NULL),
('37420871', '1', 'info@anton-pribora.ru', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '62f1b116a778e38b4b340f4c61d55072', b'1111111111111111111111111111111', '2017-09-24 15:58:03', NULL),
('38955998', '3', 'porady.prawnikow@gmail.com', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'b6c45fa3ebf56c5c7d709675efe05729', b'1111111111111111111111111111111', '2017-10-24 13:45:00', NULL),
('40703381', '1', 'michal.olszowski@gmail.com', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '8081b51a31c1f895a3b844cd6cf8a625', b'1111111111111111111111111111111', '2017-09-27 14:14:20', NULL),
('41768357', '3', 'marantek@poczta.onet.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'f96486581f011761aec564cf3ae7fa5e', b'1111111111111111111111111111111', '2017-10-23 12:13:58', NULL),
('45738208', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'ef585e68bfc12530caa59c7f020c8eb1', b'1111111111111111111111111111111', '2017-06-12 22:30:24', NULL),
('48734561', '1', 'monika.gasior@cui24.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '7ac64975842348c5b5a116c18052de62', b'1111111111111111111111111111111', '2017-10-03 09:05:55', NULL),
('49694846', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '1384e6a908e7923d78df6cbd7b755c11', b'1111111111111111111111111111111', '2017-07-14 08:06:57', NULL),
('49857477', '2', 'wojciech.burzynski@info-projekt-it.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '4cd1510ed6a40cdd4fda41e2af712a02', b'1111111111111111111111111111111', '2017-10-27 15:25:26', NULL),
('50460174', '2', 'it@klett.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'b58b0b486ad618c55748e34812434819', b'1111111111111111111111111111111', '2017-07-05 14:55:35', NULL),
('50713550', '3', 'grzegorz.surdykowski@sw.gov.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '355934a6a07d7bb8697f9f53f2499116', b'1111111111111111111111111111111', '2017-10-23 11:18:14', NULL),
('52232289', '1', 'handzlik@gmail.com', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '678de9d7b663f790b296fd0db638ca61', b'1111111111111111111111111111111', '2017-06-12 20:14:26', NULL),
('52387898', '1', 'test@test.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'fdef1d3ed8e942c31a5001411db40d54', b'1111111111111111111111111111111', '0000-00-00 00:00:00', NULL),
('52442168', '3', 'rgrabski@me.com', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '964bc967048941bc9863f0c7f0ac552f', b'1111111111111111111111111111111', '2017-10-23 08:50:32', NULL),
('52554723', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '75305c0ec75cc8345a2357292d252fd6', b'1111111111111111111111111111111', '2017-08-25 09:28:11', NULL),
('53610511', '1', 'tellir@op.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '030019aa1104d4de5716c2cdedef0702', b'1111111111111111111111111111111', '2017-07-06 12:21:10', NULL),
('57269097', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'e999ab9e544593aa2c10d6281bb786bd', b'1111111111111111111111111111111', '2017-09-26 10:28:18', NULL),
('57603093', '1', 'info@anton-pribora.ru', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'c42877e15929fc6aefad87526e5dbbff', b'1111111111111111111111111111111', '2017-09-22 12:48:10', NULL),
('57633394', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '6a6240282b10df1cfe7f07b1422fbfd7', b'1111111111111111111111111111111', '2017-10-06 08:44:03', NULL),
('58836442', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '2a742460b2073f04d74801a3da43f0c4', b'1111111111111111111111111111111', '2017-09-24 20:58:12', NULL),
('60937229', '2', 'it@klett.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'd28aa92ef5a5ed2ccf908602149ce8ff', b'1111111111111111111111111111111', '2017-07-05 10:50:20', NULL),
('61348163', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'dae5cd139f20389b29396aae52ec6545', b'1111111111111111111111111111111', '2017-09-22 12:11:32', NULL),
('62302773', '3', 'karol.krzyszton@krzyszton.legal', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'a2599f35ed7faa7d86b30bc89e9740b8', b'1111111111111111111111111111111', '2017-10-23 15:46:30', NULL),
('62439487', '3', 'inloco.pl@gmail.com', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'ef1d630e213b5398f72caa3a30b1a0b3', b'1111111111111111111111111111111', '2017-09-26 20:47:01', NULL),
('71037002', '3', 'kontakt@katarzynawardzinska.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'c12f81ec15425586c9fd0f36ec29c234', b'1111111111111111111111111111111', '2017-10-26 08:23:37', NULL),
('72043354', '2', 'handzlik@gmail.com', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'a789cf869557f4ae1d8b372a9c9cc7f9', b'1111111111111111111111111111111', '2017-07-12 10:11:35', NULL),
('73468622', '1', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '63e8547fdae0e9a4f071e1b79814a012', b'1111111111111111111111111111111', '0000-00-00 00:00:00', NULL),
('73636848', '1', 'info@anton-pribora.ru', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '7264e864f0fc3e3ac0f2eb8115c8ef39', b'1111111111111111111111111111111', '2017-09-22 15:07:55', NULL),
('74673297', '3', 'wedwewerwer@wqewewr.werwe', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '917bca0aa976d690d62ed63384e38e3d', b'1111111111111111111111111111111', '2017-09-22 09:53:05', NULL),
('75559777', '1', 'info@anton-pribora.ru', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '5ab282ae0aba2fd6364f1db68f4fd479', b'1111111111111111111111111111111', '2017-09-25 07:24:08', NULL),
('79491598', '3', 'lukasz.kolodziejczyk@signumconsulting.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'a451fd58ed6d74d254f6cb8c76665b2f', b'1111111111111111111111111111111', '2017-09-28 21:19:17', NULL),
('82218666', '2', 'it@klett.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '40707dd668b7bef448990ffbdccf208f', b'1111111111111111111111111111111', '2017-07-05 14:27:46', NULL),
('82606288', '1', 'handzlik@gmail.com', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '2f32c97fa6ea66506034e97e93888d82', b'1111111111111111111111111111111', '2017-07-12 10:13:17', NULL),
('8331794', '1', 'test@test.com', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '', b'1111111111111111111111111111111', '0000-00-00 00:00:00', NULL),
('83940224', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '8de09b27d61e6adc4313e7034bba860f', b'1111111111111111111111111111111', '2017-08-25 09:26:37', NULL),
('86682369', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'cacca3e8d8eab996a13c8db4234ab41c', b'1111111111111111111111111111111', '2017-09-24 15:39:25', NULL),
('90496970', '1', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '3815fb678727a2bd34c0e7c572b2db37', b'1111111111111111111111111111111', '2017-07-12 05:26:08', NULL),
('93148083', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '3085c87d5ab0a90f19942a19c5e38147', b'1111111111111111111111111111111', '2017-08-14 08:25:14', NULL),
('94555562', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '497ac5eaae38b85eede1e611c57a146b', b'1111111111111111111111111111111', '2017-09-28 10:56:26', NULL),
('95014184', '2', 'it@klett.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '99b0307b1dcaad642e7059012dcf3eae', b'1111111111111111111111111111111', '2017-07-07 21:44:54', NULL),
('95860462', '2', 'it@klett.pl', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '4e49bc12f02ee7f2d00d8bf96583064b', b'1111111111111111111111111111111', '2017-07-07 21:45:18', NULL),
('95913766', '1', 'inloco.pl@gmail.com', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'ae496070d1c7d0e0f9deb0e07006afbb', b'1111111111111111111111111111111', '2017-08-25 21:52:12', NULL),
('96461443', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', 'f483b3c20d5c64722ab2c966067a4c13', b'1111111111111111111111111111111', '2017-09-01 13:01:57', NULL),
('98354253', '3', 'm.rolka@kryptos.co', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '7aca2e723da73281e0d56e377681fecd', b'1111111111111111111111111111111', '2017-07-11 09:37:14', NULL),
('98453576', '1', 'handzlik@gmail.com', b'1111111111111111111111111111111', b'1111111111111111111111111111111', '7f775edd59872b06af803a9e7019b14c', b'1111111111111111111111111111111', '0000-00-00 00:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `systemy`
--

CREATE TABLE `systemy` (
  `id` int(11) NOT NULL,
  `subdomena` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_polish_ci NOT NULL,
  ` aktywna` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Dumping data for table `systemy`
--

INSERT INTO `systemy` (`id`, `subdomena`, ` aktywna`) VALUES
(1, ' abi1 ', 1),
(2, ' abi2 ', 1),
(3, ' abi3 ', 1),
(4, ' abi4 ', 1),
(5, ' abi5 ', 1),
(9, ' 8145627 ', 1),
(10, ' 7423698 ', 1),
(11, ' 5261794 ', 1),
(12, ' 2943516 ', 1),
(13, ' 1470653 ', 1),
(14, ' 7584631 ', 1),
(15, ' 9167538 ', 1),
(16, ' 1527430 ', 1),
(17, ' 7342186 ', 1),
(18, ' 0635124 ', 1),
(19, ' 3548679 ', 1),
(20, ' 2741630 ', 1),
(21, ' 9857234 ', 1),
(22, ' 4015726 ', 1),
(23, ' 7642851 ', 1),
(24, ' 0145763 ', 1),
(25, ' 4381295 ', 1),
(26, ' 7054612 ', 1),
(27, ' 7298154 ', 1),
(28, ' 5763124 ', 1),
(29, ' 6948173 ', 1),
(30, ' 5743602 ', 1),
(31, ' 3196758 ', 1),
(32, ' 4375620 ', 1),
(33, ' 7621853 ', 1),
(34, ' 7032641 ', 1),
(35, ' 9785423 ', 1),
(36, ' 4750162 ', 1),
(37, ' 4271359 ', 1),
(38, ' 4256317 ', 1),
(39, ' 4796815 ', 1),
(40, ' 1470562 ', 1),
(41, ' 7560324 ', 1),
(42, ' 2681394 ', 1),
(43, ' 2179645 ', 1),
(44, ' kryptos24 ', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `doc_szablony`
--
ALTER TABLE `doc_szablony`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscription_levels`
--
ALTER TABLE `subscription_levels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscription_levels_limits`
--
ALTER TABLE `subscription_levels_limits`
  ADD PRIMARY KEY (`name`,`type`);

--
-- Indexes for table `subscription_payment_config`
--
ALTER TABLE `subscription_payment_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscription_transactions`
--
ALTER TABLE `subscription_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscription_transactions_log`
--
ALTER TABLE `subscription_transactions_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `systems`
--
ALTER TABLE `systems`
  ADD PRIMARY KEY (`subdomain`);

--
-- Indexes for table `systemy`
--
ALTER TABLE `systemy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_systemy_sudomenta` (`subdomena`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `doc_szablony`
--
ALTER TABLE `doc_szablony`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `subscription_transactions`
--
ALTER TABLE `subscription_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `subscription_transactions_log`
--
ALTER TABLE `subscription_transactions_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `systemy`
--
ALTER TABLE `systemy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

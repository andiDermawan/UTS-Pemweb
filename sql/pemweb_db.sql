-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 18 Bulan Mei 2026
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pemweb_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `prodi_tbl`
--

CREATE TABLE `prodi_tbl` (
  `prodi_id` int(30) NOT NULL,
  `nama_prodi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `prodi_tbl`
--

INSERT INTO `prodi_tbl` (`prodi_id`, `nama_prodi`) VALUES
(5, 'Akuntansi'),
(8, 'Biologi'),
(6, 'Farmasi'),
(10, 'Fisika'),
(1, 'Ilmu Komputer'),
(7, 'Kimia'),
(4, 'Manajemen'),
(11, 'Matematika'),
(2, 'Sistem Informasi'),
(9, 'Statistika'),
(3, 'Teknik Elektro');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_tbl`
-- userid menggunakan UUID (CHAR(36))
--

CREATE TABLE `user_tbl` (
  `userid` char(36) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `prodi_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user_tbl`
--

INSERT INTO `user_tbl` (`userid`, `email`, `password`, `foto_profil`, `prodi_id`) VALUES
('961f46e5-f769-47c5-abac-396234b2ebe0', 'admin123@gmail.com', '$2y$12$InAqIVqboPncYG7QXmKtSO4tDyy1HqjiDbb66JPYRA4KbNsKojEyS', NULL, 1);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `prodi_tbl`
--
ALTER TABLE `prodi_tbl`
  ADD PRIMARY KEY (`prodi_id`),
  ADD UNIQUE KEY `nama_prodi` (`nama_prodi`);

--
-- Indeks untuk tabel `user_tbl`
--
ALTER TABLE `user_tbl`
  ADD PRIMARY KEY (`userid`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `prodi_id` (`prodi_id`);

--
-- AUTO_INCREMENT untuk tabel `prodi_tbl`
--
ALTER TABLE `prodi_tbl`
  MODIFY `prodi_id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Ketidakleluasaan untuk tabel `user_tbl`
--
ALTER TABLE `user_tbl`
  ADD CONSTRAINT `user_tbl_ibfk_1` FOREIGN KEY (`prodi_id`) REFERENCES `prodi_tbl` (`prodi_id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

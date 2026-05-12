-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 01 Bulan Mei 2026 pada 10.01
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
--

CREATE TABLE `user_tbl` (
  `userid` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `prodi_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user_tbl`
--

INSERT INTO `user_tbl` (`userid`, `email`, `password`, `prodi_id`) VALUES
(1, 'ayam@gmail.com', '$2y$10$A1nt7/IGXQ7Jpfn0kxvPFexTiB/LNgEBS893o6zQjgdoA.HuV/dZy', 4),
(2, 'kicau@gmail.com', '$2y$10$0W2xeVL7XP8UAF2zIIKnleVzI67G9HJ6pXrivX9p6ZhTlZeeDJ1XK', 2);

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
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `prodi_tbl`
--
ALTER TABLE `prodi_tbl`
  MODIFY `prodi_id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `user_tbl`
--
ALTER TABLE `user_tbl`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `user_tbl`
--
ALTER TABLE `user_tbl`
  ADD CONSTRAINT `user_tbl_ibfk_1` FOREIGN KEY (`prodi_id`) REFERENCES `prodi_tbl` (`prodi_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

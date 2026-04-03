-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2026 at 02:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_juma_tiga`
--

-- --------------------------------------------------------

--
-- Table structure for table `broadcast`
--

CREATE TABLE `broadcast` (
  `id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `pesan` text NOT NULL,
  `target_role` varchar(100) DEFAULT 'semua',
  `no_hp` varchar(20) DEFAULT NULL,
  `dibuat_oleh` varchar(100) DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `broadcast`
--

INSERT INTO `broadcast` (`id`, `judul`, `pesan`, `target_role`, `no_hp`, `dibuat_oleh`, `aktif`, `created_at`) VALUES
(3, 'dgh', 'cvcghjhk', 'semua', '6289670975052', '0', 1, '2026-03-19 18:22:12');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `id` int(11) NOT NULL,
  `nama_toko` varchar(200) NOT NULL,
  `id_mesin` varchar(100) DEFAULT NULL,
  `nomor_hp` varchar(20) DEFAULT NULL,
  `lokasi` text DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `link_maps` text DEFAULT NULL,
  `wilayah` enum('Wilayah 1','Wilayah 2','Wilayah 3','Wilayah 4') DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `catatan` text DEFAULT NULL,
  `dibuat_oleh` varchar(100) DEFAULT NULL,
  `diedit_oleh` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`id`, `nama_toko`, `id_mesin`, `nomor_hp`, `lokasi`, `kota`, `provinsi`, `link_maps`, `wilayah`, `status`, `catatan`, `dibuat_oleh`, `diedit_oleh`, `created_at`, `updated_at`) VALUES
(5, 'TOKO PERANGIN-ANGIN', 'BIDNAICE2020402121', '081370675252', '', '', '', 'https://maps.app.goo.gl/4RGvRy9vaLhAGBRV6', 'Wilayah 1', 'aktif', '', 'admin_assets', 'admin_assets', '2026-03-24 11:52:28', '2026-03-24 11:53:12');

-- --------------------------------------------------------

--
-- Table structure for table `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id` int(11) NOT NULL,
  `transaksi_id` int(11) DEFAULT NULL,
  `produk_id` int(11) DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `subtotal` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `galeri`
--

CREATE TABLE `galeri` (
  `id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) NOT NULL,
  `kategori` enum('produk','kegiatan','kantor','promosi','lainnya') DEFAULT 'lainnya',
  `urutan` int(11) DEFAULT 0,
  `aktif` tinyint(1) DEFAULT 1,
  `dibuat_oleh` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `galeri`
--

INSERT INTO `galeri` (`id`, `judul`, `deskripsi`, `gambar`, `kategori`, `urutan`, `aktif`, `dibuat_oleh`, `created_at`) VALUES
(1, 'foto tika', 'bsa', 'gal_1773892917_69bb7535460a2.jpeg', 'promosi', 0, 1, '0', '2026-03-19 11:01:57');

-- --------------------------------------------------------

--
-- Table structure for table `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `isi` text NOT NULL,
  `tipe` enum('diskon','promo','info','penting') DEFAULT 'info',
  `badge` varchar(50) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengumuman`
--

INSERT INTO `pengumuman` (`id`, `judul`, `isi`, `tipe`, `badge`, `gambar`, `aktif`, `created_at`) VALUES
(1, 'Maniskan Momen Ramadhan', 'Dapatkan potongan harga 20% untuk semua produk pilihan selama bulan Desember. Jangan lewatkan kesempatan emas ini!', 'promo', 'HOT', NULL, 1, '2026-03-18 13:08:23'),
(2, 'Promo Pembelian Pertama', 'Khusus pembeli baru, nikmati gratis ongkir untuk transaksi pertama kamu bersama PT Juma Tiga.', 'promo', 'NEW', NULL, 1, '2026-03-18 13:08:23'),
(3, 'Jam Operasional Terbaru', 'Mulai bulan ini kami melayani pemesanan Senin–Sabtu pukul 08.00–17.00 WIB. Hari Minggu & libur nasional tutup.', 'info', NULL, NULL, 1, '2026-03-18 13:08:23'),
(4, 'Stok Terbatas!', 'Beberapa produk unggulan kami hampir habis. Segera lakukan pemesanan sebelum kehabisan.', 'penting', 'LIMITED', NULL, 1, '2026-03-18 13:08:23');

-- --------------------------------------------------------

--
-- Table structure for table `pesan_chat`
--

CREATE TABLE `pesan_chat` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `pesan` text NOT NULL,
  `tipe` enum('chat','keluhan','masukan') DEFAULT 'chat',
  `dibaca` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `pengirim` varchar(100) DEFAULT NULL,
  `penerima` varchar(100) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesan_chat`
--

INSERT INTO `pesan_chat` (`id`, `username`, `role`, `pesan`, `tipe`, `dibaca`, `created_at`, `pengirim`, `penerima`, `gambar`) VALUES
(1, 'tika', 'pembeli', 'jjbyjj', 'chat', 1, '2026-03-18 14:18:20', NULL, NULL, NULL),
(2, 'tika', 'pembeli', 'hhghtfhn', 'keluhan', 1, '2026-03-18 16:48:28', NULL, NULL, NULL),
(3, 'tika', 'pembeli', 'cvbnmxz', 'masukan', 1, '2026-03-18 17:56:38', NULL, NULL, NULL),
(4, 'tehna', 'publik', 'cara terhubung sebagai  mitra bagaimana', 'chat', 1, '2026-03-23 12:07:08', NULL, NULL, NULL),
(5, 'erna', 'publik', 'fgvhgdshbbhbjs', 'chat', 1, '2026-03-24 09:20:54', NULL, NULL, NULL),
(6, 'admin_assets', 'admin_asset', 'boleh kirim no wa kak ?', 'chat', 1, '2026-03-24 09:31:25', NULL, NULL, NULL),
(7, 'admin_assets', 'admin_asset', 'untuk promo nya ya kak', 'chat', 1, '2026-03-24 09:31:57', NULL, NULL, NULL),
(8, 'tika', 'publik', 'xcsvsjdhgebj,dmnck.dkjbcvbnmk,', 'chat', 1, '2026-03-25 08:12:04', NULL, NULL, NULL),
(9, 'djnkjs', 'publik', 'szdxghjklkjhgertyu', 'chat', 0, '2026-03-25 09:01:27', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pesan_order`
--

CREATE TABLE `pesan_order` (
  `id` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `catatan` text DEFAULT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `status` enum('pending','diproses','selesai','ditolak') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pesan_publik`
--

CREATE TABLE `pesan_publik` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `pesan` text NOT NULL,
  `tipe` enum('chat','keluhan','masukan') DEFAULT 'chat',
  `dibaca` tinyint(1) DEFAULT 0,
  `dibalas` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesan_publik`
--

INSERT INTO `pesan_publik` (`id`, `nama`, `pesan`, `tipe`, `dibaca`, `dibalas`, `created_at`) VALUES
(1, 'atikaaa', 'mau nanyak2', 'chat', 0, NULL, '2026-03-23 12:01:28');

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `nama_produk` varchar(100) DEFAULT NULL,
  `kategori` varchar(100) DEFAULT 'Umum',
  `gambar` varchar(255) DEFAULT NULL,
  `harga` int(11) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id`, `nama_produk`, `kategori`, `gambar`, `harga`, `deskripsi`) VALUES
(1, 'keluarga mochi', 'Es Krim', 'prod_1773822803_69ba63536bd61.png', 90000, NULL),
(2, 'cryspy ball series', 'Es Krim', 'prod_1773822779_69ba633b1bf7a.jpg', 83000, NULL),
(3, 'chocolate Stick', 'Es Krim', 'prod_1773822422_69ba61d6ae305.jpg', 90000, NULL),
(4, 'sweet corn stick', 'Es Krim', 'prod_1773822319_69ba616fb1df8.jpg', 90000, NULL),
(5, 'corn stick', 'Umum', 'prod_1773903475_69bb9e73a89f9.jpg', 4000, '');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `total` int(11) DEFAULT NULL,
  `id_produk` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `user_id`, `tanggal`, `total`, `id_produk`, `username`, `jumlah`) VALUES
(12, NULL, '2026-03-18', 180000, 3, 'tika', 2),
(13, NULL, '2026-03-18', 180000, 4, 'tika', 2),
(14, NULL, '2026-03-18', 332000, 2, 'tika', 4);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `role` enum('penjual','pembeli','sales','admin_asset','admin_program') DEFAULT 'pembeli'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', '123', 'penjual'),
(2, 'tika', '123', 'pembeli'),
(3, 'admin_assets', '123456', 'admin_asset'),
(4, 'sales', '123456', 'sales');

-- --------------------------------------------------------

--
-- Table structure for table `wilayah`
--

CREATE TABLE `wilayah` (
  `id` int(11) NOT NULL,
  `nama_wilayah` varchar(100) NOT NULL,
  `nama_admin` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `area_coverage` text DEFAULT NULL,
  `urutan` int(11) DEFAULT 0,
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wilayah`
--

INSERT INTO `wilayah` (`id`, `nama_wilayah`, `nama_admin`, `no_hp`, `area_coverage`, `urutan`, `aktif`, `created_at`, `updated_at`) VALUES
(1, 'kabanjahe', 'cm,x', '', 'cnnxcncxnm', 1, 1, '2026-03-19 17:34:56', '2026-03-19 17:34:56'),
(2, 'kabanjahe', 'ghhxbjx4', '345678', 'vdcjmkjdc', 2, 1, '2026-03-19 18:23:24', '2026-03-19 18:23:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `broadcast`
--
ALTER TABLE `broadcast`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_id` (`transaksi_id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- Indexes for table `galeri`
--
ALTER TABLE `galeri`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pesan_chat`
--
ALTER TABLE `pesan_chat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pesan_order`
--
ALTER TABLE `pesan_order`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pesan_publik`
--
ALTER TABLE `pesan_publik`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wilayah`
--
ALTER TABLE `wilayah`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `broadcast`
--
ALTER TABLE `broadcast`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `galeri`
--
ALTER TABLE `galeri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pesan_chat`
--
ALTER TABLE `pesan_chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `pesan_order`
--
ALTER TABLE `pesan_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pesan_publik`
--
ALTER TABLE `pesan_publik`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wilayah`
--
ALTER TABLE `wilayah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `detail_transaksi_ibfk_1` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`),
  ADD CONSTRAINT `detail_transaksi_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`);

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

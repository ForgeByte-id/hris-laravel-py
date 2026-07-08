-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2026 at 01:44 AM
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
-- Database: `hris_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id_absensi` bigint(20) UNSIGNED NOT NULL,
  `id_karyawan` bigint(20) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `menit_terlambat` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `face_verified` tinyint(1) NOT NULL DEFAULT 0,
  `face_confidence` decimal(5,2) DEFAULT NULL,
  `photo_hash` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id_absensi`, `id_karyawan`, `tanggal`, `jam_masuk`, `jam_pulang`, `status`, `menit_terlambat`, `recorded_by`, `face_verified`, `face_confidence`, `photo_hash`, `created_at`, `updated_at`) VALUES
(2, 11, '2026-06-20', '01:31:56', NULL, 'tepat_waktu', 0, NULL, 0, NULL, NULL, '2026-06-19 17:31:56', '2026-06-19 17:31:56');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cuti`
--

CREATE TABLE `cuti` (
  `id_cuti` bigint(20) UNSIGNED NOT NULL,
  `id_karyawan` bigint(20) UNSIGNED NOT NULL,
  `jenis_cuti` varchar(255) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `keterangan` text DEFAULT NULL,
  `tanggal_persetujuan` date DEFAULT NULL,
  `status_persetujuan` varchar(255) NOT NULL DEFAULT 'pending',
  `id_atasan` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cuti`
--

INSERT INTO `cuti` (`id_cuti`, `id_karyawan`, `jenis_cuti`, `tanggal_mulai`, `tanggal_selesai`, `keterangan`, `tanggal_persetujuan`, `status_persetujuan`, `id_atasan`, `created_at`, `updated_at`) VALUES
(2, 18, 'Cuti Tahunan', '2026-06-18', '2026-06-19', 'Pulang Kampung', NULL, 'pending', NULL, '2026-06-13 14:33:50', '2026-06-13 14:33:50');

-- --------------------------------------------------------

--
-- Table structure for table `divisis`
--

CREATE TABLE `divisis` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_divisi` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `divisis`
--

INSERT INTO `divisis` (`id`, `nama_divisi`, `created_at`, `updated_at`) VALUES
(1, 'NBCS', '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(2, 'NSC2', '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(3, 'NSC1', '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(4, 'Office', '2026-05-07 22:28:55', '2026-05-07 22:28:55');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jabatans`
--

CREATE TABLE `jabatans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_jabatan` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jabatans`
--

INSERT INTO `jabatans` (`id`, `nama_jabatan`, `created_at`, `updated_at`) VALUES
(1, 'Manager Umum', '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(2, 'Wakil Manager Umum', '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(3, 'Manager Divisi', '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(4, 'Wakil Manager Divisi', '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(5, 'Supervisor', '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(6, 'SDM', '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(7, 'Accounting', '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(8, 'Online Marketing', '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(9, 'Customer Service', '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(10, 'Teknisi', '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(11, 'Kasir', '2026-05-07 22:28:55', '2026-05-07 22:28:55');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_kerja`
--

CREATE TABLE `jadwal_kerja` (
  `id_jadwal` bigint(20) UNSIGNED NOT NULL,
  `id_karyawan` bigint(20) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `id_shift` varchar(2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `karyawan`
--

CREATE TABLE `karyawan` (
  `id_karyawan` bigint(20) UNSIGNED NOT NULL,
  `id_user` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `id_jabatan` bigint(20) UNSIGNED DEFAULT NULL,
  `id_divisi` bigint(20) UNSIGNED DEFAULT NULL,
  `tanggal_masuk` date NOT NULL,
  `status_aktif` varchar(255) NOT NULL DEFAULT 'Aktif',
  `status_karyawan` varchar(255) DEFAULT NULL,
  `face_embedding` text DEFAULT NULL,
  `face_image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `karyawan`
--

INSERT INTO `karyawan` (`id_karyawan`, `id_user`, `nama`, `id_jabatan`, `id_divisi`, `tanggal_masuk`, `status_aktif`, `status_karyawan`, `face_embedding`, `face_image_path`, `created_at`, `updated_at`) VALUES
(11, 12, 'I Nengah Subawa Kardika Putra', 1, 4, '2024-01-02', 'Aktif', 'Tetap', '[-0.036901261657476425,0.008848050609230995,0.0887920930981636,-0.0453108586370945,-0.12061195075511932,0.011972704902291298,-0.015843579545617104,-0.11864729970693588,0.22190500795841217,-0.1389005184173584,0.21569517254829407,-0.052172549068927765,-0.25778451561927795,-0.02462855726480484,0.0054228538647294044,0.14299891889095306,-0.16620652377605438,-0.09685470163822174,0.019237754866480827,-0.03992585837841034,0.027300965040922165,-0.0035175946541130543,0.03877641260623932,0.046015337109565735,-0.11304643750190735,-0.41227415204048157,-0.08580949902534485,-0.0872751995921135,-0.024879712611436844,-0.05216255784034729,-0.033941447734832764,0.12848468124866486,-0.18117058277130127,0.016858836635947227,-0.04131779074668884,0.04658203199505806,-0.048389632254838943,-0.08652028441429138,0.2063882052898407,-0.0033260569907724857,-0.20516818761825562,-0.09613402187824249,-0.014731230214238167,0.14890214800834656,0.1739705204963684,0.03503230959177017,0.018107902258634567,-0.06387075036764145,0.037620045244693756,-0.21927034854888916,0.00825997069478035,0.15269239246845245,0.00976176280528307,-0.021863140165805817,0.04557628184556961,-0.15359024703502655,-0.022423479706048965,0.06921201199293137,-0.17162108421325684,0.0063739679753780365,0.03298889845609665,-0.12862929701805115,-0.037264011800289154,-0.04295320063829422,0.2595779001712799,0.1289857178926468,-0.07882291823625565,-0.06170613318681717,0.16216090321540833,-0.171197772026062,-0.05296168476343155,-0.0013249199837446213,-0.13921257853507996,-0.23235371708869934,-0.2805721163749695,0.00039307400584220886,0.3455822169780731,0.1107194647192955,-0.17825256288051605,0.05203096568584442,-0.004535377956926823,0.013442080467939377,0.18886801600456238,0.1419408619403839,-0.03162120655179024,-0.0011847391724586487,-0.07196314632892609,0.026340056210756302,0.16185824573040009,-0.04398456588387489,-0.031429097056388855,0.23749098181724548,-0.06912166625261307,0.04145762324333191,0.04231475666165352,0.0018724823603406549,0.003060968592762947,0.03198723867535591,-0.13015896081924438,0.024894382804632187,0.10243850946426392,-0.034614428877830505,0.0060655055567622185,0.10696398466825485,-0.1867217719554901,0.03166704624891281,-0.029908902943134308,-0.056973062455654144,0.056658875197172165,-0.06323588639497757,-0.1303887814283371,-0.07012530416250229,0.17191222310066223,-0.27498459815979004,0.15650337934494019,0.15709318220615387,-0.06454473733901978,0.19760000705718994,0.03539968281984329,0.10543906688690186,-0.011947073973715305,-0.07619000971317291,-0.1954137533903122,-0.005849984008818865,0.10842067003250122,-0.006391679868102074,0.062013890594244,-0.0037051262333989143]', 'karyawan/11/face.webp', '2026-06-13 13:54:53', '2026-06-19 17:11:04'),
(12, 13, 'I Putu Raka Darmadi', 2, 4, '2016-11-13', 'Aktif', 'Tetap', NULL, NULL, '2026-06-13 13:56:38', '2026-06-13 13:56:38'),
(13, 14, 'I Nengah Wardika', 9, 1, '2017-11-27', 'Aktif', 'Tetap', NULL, NULL, '2026-06-13 13:57:29', '2026-06-13 13:57:29'),
(14, 15, 'I Gede Juli Suparwata', 3, 1, '2016-08-16', 'Aktif', 'Tetap', NULL, NULL, '2026-06-13 13:58:33', '2026-06-13 13:58:33'),
(15, 16, 'I Made Nesa Antara', 3, 2, '2011-03-18', 'Aktif', 'Tetap', NULL, NULL, '2026-06-13 14:01:02', '2026-06-13 14:01:02'),
(16, 17, 'Andris Styawan Putro', 10, 2, '2011-09-18', 'Aktif', 'Tetap', NULL, NULL, '2026-06-13 14:02:09', '2026-06-13 14:02:28'),
(17, 18, 'I Nengah Mertha Yasa', 3, 3, '2019-08-26', 'Aktif', 'Tetap', NULL, NULL, '2026-06-13 14:03:32', '2026-06-13 14:03:32'),
(18, 19, 'Victor Johan Dwi Ariyanto', 10, 3, '2022-10-26', 'Aktif', 'Tetap', NULL, NULL, '2026-06-13 14:04:47', '2026-06-13 14:04:47'),
(19, 20, 'Ni Nengah Wiratni', 7, 4, '2018-11-24', 'Aktif', 'Tetap', NULL, NULL, '2026-06-13 14:05:35', '2026-06-13 14:05:35'),
(20, 21, 'I Ketut Paduary Karmanta', 6, 4, '2023-11-13', 'Aktif', 'Tetap', NULL, NULL, '2026-06-13 14:06:16', '2026-06-13 14:06:16'),
(22, 25, 'Dewi Alpianti', 9, 1, '2025-09-11', 'Aktif', 'Kontrak', NULL, NULL, '2026-06-19 14:57:40', '2026-06-19 16:42:45');

-- --------------------------------------------------------

--
-- Table structure for table `kuota_cuti_karyawan`
--

CREATE TABLE `kuota_cuti_karyawan` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_karyawan` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `year` smallint(5) UNSIGNED NOT NULL,
  `quota` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `remaining_quota` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kuota_cuti_karyawan`
--

INSERT INTO `kuota_cuti_karyawan` (`id`, `id_karyawan`, `leave_type_id`, `year`, `quota`, `remaining_quota`, `created_at`, `updated_at`) VALUES
(1, 18, 1, 2026, 12, 12, '2026-06-13 14:31:59', '2026-06-13 14:31:59');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `route` varchar(255) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_admin_only` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `route`, `icon`, `order`, `is_admin_only`, `created_at`, `updated_at`) VALUES
(1, 'Dashboard', '/dashboard', 'bi-house-door-fill', 1, 0, '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(2, 'Cuti', '/cuti', 'bi-calendar-event-fill', 2, 0, '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(3, 'Absensi', '/attendance', 'bi-camera-video-fill', 3, 1, '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(4, 'Riwayat Absensi', '/attendance/history', 'bi-clock-history', 4, 1, '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(5, 'Role Management', '/admin/roles', 'bi-shield-lock-fill', 5, 1, '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(6, 'Hak Akses', '/admin/permissions', 'bi-lock-fill', 6, 1, '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(7, 'Jadwal Kerja', '/jadwal', 'bi-clock-fill', 7, 1, '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(8, 'Divisi', '/divisi', 'bi-diagram-3-fill', 8, 1, '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(9, 'Jabatan', '/jabatan', 'bi-briefcase-fill', 9, 1, '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(10, 'Karyawan', '/karyawan', 'bi-people-fill', 10, 1, '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(11, 'Laporan', '/laporan', 'bi-bar-chart-fill', 11, 1, '2026-05-07 22:28:55', '2026-05-07 22:28:55');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_02_18_054713_create_karyawan_table', 1),
(5, '2026_02_18_055056_create_cuti_table', 1),
(6, '2026_02_18_055408_create_absensi_table', 1),
(7, '2026_02_18_060241_create_jadwal_kerja_table', 1),
(8, '2026_05_04_120000_add_email_to_users_table', 1),
(9, '2026_05_04_122759_create_permission_tables', 1),
(10, '2026_05_04_122942_create_jabatans_table', 1),
(11, '2026_05_04_123204_alter_karyawan_add_id_jabatan', 1),
(12, '2026_05_04_125626_create_role_menu_permissions_table', 1),
(13, '2026_05_04_151951_create_personal_access_tokens_table', 1),
(14, '2026_05_04_152439_create_devisis_table', 1),
(15, '2026_05_05_000000_update_karyawan_divisi_to_foreign_key', 1),
(16, '2026_05_07_020211_add_leave_quota_to_karyawan_table', 1),
(17, '2026_06_01_000000_add_audit_columns_to_absensi_table', 1),
(18, '2026_06_02_000001_add_menit_terlambat_to_absensi_table', 2),
(19, '2026_06_02_000001_create_shifts_table', 2),
(20, '2026_06_02_000002_add_kode_shift_to_jadwal_kerja_table', 2),
(21, '2026_06_03_000003_add_kode_shift_to_karyawan_table', 2),
(22, '2026_05_06_140136_create_menu_items_table', 3),
(23, '2026_05_11_112833_create_shifts_table', 3),
(24, '2026_06_13_000001_add_real_employee_fields_to_karyawan_table', 3),
(25, '2026_06_13_000002_create_leave_types_table', 3),
(26, '2026_06_13_000003_create_karyawan_leave_quotas_table', 3),
(27, '2026_06_13_000004_add_face_image_path_to_karyawan_table', 3);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 8),
(2, 'App\\Models\\User', 9),
(3, 'App\\Models\\User', 10),
(3, 'App\\Models\\User', 11),
(4, 'App\\Models\\User', 2),
(4, 'App\\Models\\User', 3),
(4, 'App\\Models\\User', 4),
(4, 'App\\Models\\User', 5),
(4, 'App\\Models\\User', 6),
(4, 'App\\Models\\User', 7),
(4, 'App\\Models\\User', 12),
(4, 'App\\Models\\User', 13),
(4, 'App\\Models\\User', 14),
(4, 'App\\Models\\User', 15),
(4, 'App\\Models\\User', 16),
(4, 'App\\Models\\User', 17),
(4, 'App\\Models\\User', 18),
(4, 'App\\Models\\User', 19),
(4, 'App\\Models\\User', 20),
(4, 'App\\Models\\User', 21);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'view-dashboard', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(2, 'view-karyawan', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(3, 'create-karyawan', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(4, 'edit-karyawan', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(5, 'delete-karyawan', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(6, 'register-face-karyawan', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(7, 'view-attendance', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(8, 'view-attendance-history', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(9, 'record-attendance', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(10, 'view-cuti', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(11, 'create-cuti', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(12, 'view-cuti-history', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(13, 'approve-cuti', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(14, 'reject-cuti', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(15, 'cancel-cuti', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(16, 'view-jadwal', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(17, 'create-jadwal', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(18, 'edit-jadwal', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(19, 'delete-jadwal', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(20, 'bulk-create-jadwal', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(21, 'set-libur-massal', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(22, 'manage-users', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(23, 'manage-roles', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54');

-- --------------------------------------------------------

--
-- Table structure for table `persetujuan_cuti`
--

CREATE TABLE `persetujuan_cuti` (
  `id_persetujuan` bigint(20) UNSIGNED NOT NULL,
  `id_cuti` bigint(20) UNSIGNED NOT NULL,
  `id_penyetuju` bigint(20) UNSIGNED NOT NULL,
  `status_persetujuan` varchar(255) NOT NULL,
  `tanggal_persetujuan` date NOT NULL,
  `catatan` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(2, 'manager', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(3, 'supervisor', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54'),
(4, 'karyawan', 'web', '2026-05-07 22:28:54', '2026-05-07 22:28:54');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(2, 1),
(2, 2),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(7, 2),
(7, 3),
(7, 4),
(8, 1),
(8, 2),
(8, 3),
(9, 1),
(10, 1),
(10, 2),
(10, 3),
(11, 1),
(11, 4),
(12, 1),
(12, 2),
(12, 4),
(13, 1),
(13, 2),
(13, 3),
(14, 1),
(14, 2),
(15, 1),
(16, 1),
(16, 2),
(16, 3),
(16, 4),
(17, 1),
(17, 2),
(18, 1),
(18, 2),
(19, 1),
(20, 1),
(21, 1),
(22, 1),
(23, 1);

-- --------------------------------------------------------

--
-- Table structure for table `role_menu_permissions`
--

CREATE TABLE `role_menu_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `menu_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id_shift` bigint(20) UNSIGNED NOT NULL,
  `kode_shift` varchar(2) NOT NULL,
  `nama_shift` varchar(255) NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`id_shift`, `kode_shift`, `nama_shift`, `jam_masuk`, `jam_pulang`, `created_at`, `updated_at`) VALUES
(1, 'Pa', 'Pagi', '08:00:00', '17:00:00', NULL, NULL),
(2, 'Si', 'Siang', '13:00:00', '22:00:00', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tipe_cuti`
--

CREATE TABLE `tipe_cuti` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_cuti` varchar(255) NOT NULL,
  `kuota_cuti` int(10) UNSIGNED NOT NULL,
  `berlaku_untuk_status` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tipe_cuti`
--

INSERT INTO `tipe_cuti` (`id`, `nama_cuti`, `kuota_cuti`, `berlaku_untuk_status`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Cuti Tahunan', 12, 'Tetap', 1, NULL, NULL),
(2, 'Cuti Hari Raya', 4, 'All', 1, NULL, NULL),
(3, 'Cuti Sakit', 6, 'All', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `username`, `email`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@hris.local', NULL, '$2y$12$vW5JK5WatACvcCzBm1t3wOyHbRWYu8N2JPS9DofDmfizzE3VFfdtW', 'admin', '1wDfcQZ5WUPDbNy9LKCp1MfXRfhDPGXeFoP9vgDwlQNXSFIBhyUtmZNbIyj5', '2026-05-07 22:28:55', '2026-05-07 22:28:55'),
(12, 'nengah_subawa', 'nengahsubawa@gmail.com', NULL, '$2y$12$qxnDlff8yu7RpVhAz1KovuE0yJ9nNJ8FkYOJR4OuovuW5vQTx24Pq', 'karyawan', NULL, '2026-06-13 13:54:53', '2026-06-13 13:54:53'),
(13, 'raka_darmadi', NULL, NULL, '$2y$12$AeE1oSdewR8rYc0sZ0XVf.cbNzhqjgEjr3Z4PZdPAUDVpmwtP2tTi', 'karyawan', NULL, '2026-06-13 13:56:38', '2026-06-13 13:56:38'),
(14, 'nengah_wardika', NULL, NULL, '$2y$12$pRwEkOOdIJn7EOVh0exoRuijRDCaTrcnMVdRqJEJ5iIfCyUhN9JJ6', 'karyawan', NULL, '2026-06-13 13:57:29', '2026-06-13 13:57:29'),
(15, 'gede_juli', NULL, NULL, '$2y$12$irMnPlZ4ZFGhz4TUa2U2relHhq7aPQuVcIoWUt5jD9Psi5xFRes8S', 'karyawan', NULL, '2026-06-13 13:58:33', '2026-06-13 13:58:33'),
(16, 'nesa_antara', NULL, NULL, '$2y$12$utxRcE1xTN.tTPPNQVKY1uVKPwkV1yrVuftEOkZpKcvLZHBnIjZAi', 'karyawan', NULL, '2026-06-13 14:01:02', '2026-06-13 14:01:02'),
(17, 'andris_styawan', NULL, NULL, '$2y$12$AwLaIE/Dk0GOoCX5xaX88OMTJ1jDmtIr0nDpVFdOZIxmY4WGzbNPW', 'karyawan', NULL, '2026-06-13 14:02:09', '2026-06-13 14:02:09'),
(18, 'nengah_mertha', NULL, NULL, '$2y$12$4kP2EI86x7aFb2f6tF5ItOrKQmQYWGq6qfYxQf44yGIzd5SbkbLpC', 'karyawan', NULL, '2026-06-13 14:03:32', '2026-06-13 14:03:32'),
(19, 'victor_johan', NULL, NULL, '$2y$12$EjAyvVFZTqbitN5JfB43D.P8KCnId/tzMhggI2ah4yXCOuPvm/EPa', 'karyawan', NULL, '2026-06-13 14:04:47', '2026-06-13 14:04:47'),
(20, 'nengah_wiratni', NULL, NULL, '$2y$12$9axo8sZvIaXBdQpW8ZNax..93d/QfUNPgdjXLipudKhEf7vY17Zr.', 'karyawan', NULL, '2026-06-13 14:05:35', '2026-06-13 14:05:35'),
(21, 'ketut_paduary', NULL, NULL, '$2y$12$XmNGFdFdj.YaAwmIct4NSeX2spoEWZQ04Q8fsIDxTrAUSxYrUdmRS', 'karyawan', NULL, '2026-06-13 14:06:16', '2026-06-13 14:06:16'),
(25, 'dewi_alpianti', NULL, NULL, '$2y$12$ot6Af6iLIE7i3s4IYISCVeKS8iUoK5E45Or5yORrIYdblFBGk/kvG', 'Karyawan', NULL, '2026-06-19 14:57:40', '2026-06-19 16:42:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id_absensi`),
  ADD KEY `absensi_id_karyawan_foreign` (`id_karyawan`),
  ADD KEY `absensi_recorded_by_foreign` (`recorded_by`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cuti`
--
ALTER TABLE `cuti`
  ADD PRIMARY KEY (`id_cuti`),
  ADD KEY `cuti_id_karyawan_foreign` (`id_karyawan`),
  ADD KEY `cuti_id_atasan_foreign` (`id_atasan`);

--
-- Indexes for table `divisis`
--
ALTER TABLE `divisis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `devisis_nama_devisi_unique` (`nama_divisi`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jabatans`
--
ALTER TABLE `jabatans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `jabatans_nama_jabatan_unique` (`nama_jabatan`);

--
-- Indexes for table `jadwal_kerja`
--
ALTER TABLE `jadwal_kerja`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `jadwal_kerja_id_karyawan_foreign` (`id_karyawan`),
  ADD KEY `jadwal_kerja_kode_shift_foreign` (`id_shift`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD PRIMARY KEY (`id_karyawan`),
  ADD UNIQUE KEY `karyawan_id_user_unique` (`id_user`),
  ADD KEY `karyawan_id_jabatan_foreign` (`id_jabatan`),
  ADD KEY `karyawan_id_devisi_foreign` (`id_divisi`);

--
-- Indexes for table `kuota_cuti_karyawan`
--
ALTER TABLE `kuota_cuti_karyawan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `karyawan_leave_quota_unique` (`id_karyawan`,`leave_type_id`,`year`),
  ADD KEY `karyawan_leave_quotas_leave_type_id_foreign` (`leave_type_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `persetujuan_cuti`
--
ALTER TABLE `persetujuan_cuti`
  ADD PRIMARY KEY (`id_persetujuan`),
  ADD KEY `id_cuti` (`id_cuti`),
  ADD KEY `id_penyetuju` (`id_penyetuju`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `role_menu_permissions`
--
ALTER TABLE `role_menu_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_menu_permissions_role_id_menu_id_unique` (`role_id`,`menu_id`),
  ADD KEY `role_menu_permissions_menu_id_foreign` (`menu_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id_shift`);

--
-- Indexes for table `tipe_cuti`
--
ALTER TABLE `tipe_cuti`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_types_nama_cuti_unique` (`nama_cuti`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id_absensi` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cuti`
--
ALTER TABLE `cuti`
  MODIFY `id_cuti` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `divisis`
--
ALTER TABLE `divisis`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jabatans`
--
ALTER TABLE `jabatans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `jadwal_kerja`
--
ALTER TABLE `jadwal_kerja`
  MODIFY `id_jadwal` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `karyawan`
--
ALTER TABLE `karyawan`
  MODIFY `id_karyawan` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `kuota_cuti_karyawan`
--
ALTER TABLE `kuota_cuti_karyawan`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `persetujuan_cuti`
--
ALTER TABLE `persetujuan_cuti`
  MODIFY `id_persetujuan` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `role_menu_permissions`
--
ALTER TABLE `role_menu_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id_shift` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tipe_cuti`
--
ALTER TABLE `tipe_cuti`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_id_karyawan_foreign` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`) ON DELETE CASCADE,
  ADD CONSTRAINT `absensi_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL;

--
-- Constraints for table `cuti`
--
ALTER TABLE `cuti`
  ADD CONSTRAINT `cuti_id_atasan_foreign` FOREIGN KEY (`id_atasan`) REFERENCES `karyawan` (`id_karyawan`) ON DELETE SET NULL,
  ADD CONSTRAINT `cuti_id_karyawan_foreign` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`) ON DELETE CASCADE;

--
-- Constraints for table `jadwal_kerja`
--
ALTER TABLE `jadwal_kerja`
  ADD CONSTRAINT `jadwal_kerja_id_karyawan_foreign` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`) ON DELETE CASCADE;

--
-- Constraints for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD CONSTRAINT `karyawan_id_devisi_foreign` FOREIGN KEY (`id_divisi`) REFERENCES `divisis` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `karyawan_id_jabatan_foreign` FOREIGN KEY (`id_jabatan`) REFERENCES `jabatans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `karyawan_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `kuota_cuti_karyawan`
--
ALTER TABLE `kuota_cuti_karyawan`
  ADD CONSTRAINT `karyawan_leave_quotas_id_karyawan_foreign` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`) ON DELETE CASCADE,
  ADD CONSTRAINT `karyawan_leave_quotas_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `tipe_cuti` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `persetujuan_cuti`
--
ALTER TABLE `persetujuan_cuti`
  ADD CONSTRAINT `persetujuan_cuti_ibfk_1` FOREIGN KEY (`id_cuti`) REFERENCES `cuti` (`id_cuti`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `persetujuan_cuti_ibfk_2` FOREIGN KEY (`id_penyetuju`) REFERENCES `karyawan` (`id_karyawan`) ON UPDATE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_menu_permissions`
--
ALTER TABLE `role_menu_permissions`
  ADD CONSTRAINT `role_menu_permissions_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_menu_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

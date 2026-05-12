<?php
// Sertakan autentikasi & konfigurasi
require_once 'auth.php';
require_once 'config.php';

// Ambil koneksi database
$pdo = getDBConnection();

// Menangani tambah prodi dengan pola Post-Redirect-Get
// Menangani permintaan form (POST) untuk tindakan: add, delete, update
// Menggunakan Post-Redirect-Get (PRG) pattern untuk mencegah form resubmission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        // Tambah program studi baru
        $nama_prodi = trim($_POST['nama_prodi'] ?? '');

        if (empty($nama_prodi)) {
            $_SESSION['flash_message'] = '<div class="alert alert-warning alert-dismissible mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="ti ti-alert-circle me-2"></i>
                    <div>Nama program studi tidak boleh kosong!</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            header("Location: program_studi.php");
            exit;
        } else {
            try {
                // Cek apakah nama prodi yang sama sudah ada
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM prodi_tbl WHERE LOWER(nama_prodi) = LOWER(?)");
                $checkStmt->execute([$nama_prodi]);
                $exists = (int) $checkStmt->fetchColumn();

                if ($exists > 0) {
                    $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-alert-circle me-2"></i>
                            <div>Gagal menambahkan program studi! (Mungkin duplikat)</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
                    header("Location: program_studi.php");
                    exit;
                }

                $stmt = $pdo->prepare("INSERT INTO prodi_tbl (nama_prodi) VALUES (?)");
                $stmt->execute([$nama_prodi]);
                $_SESSION['flash_message'] = '<div class="alert alert-success alert-dismissible mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-circle-check me-2"></i>
                        <div>Program studi berhasil ditambahkan!</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
                header("Location: program_studi.php");
                exit;
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-alert-circle me-2"></i>
                        <div>Gagal menambahkan program studi! (Mungkin duplikat)</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
                header("Location: program_studi.php");
                exit;
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        // Hapus program studi (jika tidak ada user terkait)
        $prodi_id = (int) ($_POST['prodi_id'] ?? 0);

        if ($prodi_id > 0) {
            try {
                // Cek apakah ada user di prodi ini
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_tbl WHERE prodi_id = ?");
                $stmt->execute([$prodi_id]);
                $count = (int) $stmt->fetchColumn();

                if ($count > 0) {
                    $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-alert-circle me-2"></i>
                            <div>Tidak bisa menghapus! Program studi ini masih memiliki ' . $count . ' user/mahasiswa.</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM prodi_tbl WHERE prodi_id = ?");
                    $stmt->execute([$prodi_id]);

                    // Reset AUTO_INCREMENT ke max ID + 1
                    $maxIdStmt = $pdo->query("SELECT MAX(prodi_id) FROM prodi_tbl");
                    $maxId = (int) $maxIdStmt->fetchColumn();
                    $nextId = max($maxId + 1, 1); // Minimal 1 jika table kosong
                    $pdo->exec("ALTER TABLE prodi_tbl AUTO_INCREMENT = $nextId");

                    $_SESSION['flash_message'] = '<div class="alert alert-success alert-dismissible mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-circle-check me-2"></i>
                            <div>Program studi berhasil dihapus!</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
                }
                header("Location: program_studi.php");
                exit;
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-alert-circle me-2"></i>
                        <div>Gagal menghapus program studi!</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
                header("Location: program_studi.php");
                exit;
            }
        }
    } elseif ($_POST['action'] === 'update') {
        // Update nama program studi
        $prodi_id = (int) ($_POST['prodi_id'] ?? 0);
        $nama_prodi = trim($_POST['nama_prodi'] ?? '');

        if ($prodi_id <= 0 || empty($nama_prodi)) {
            $_SESSION['flash_message'] = '<div class="alert alert-warning alert-dismissible mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="ti ti-alert-circle me-2"></i>
                    <div>Nama program studi tidak boleh kosong!</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            header("Location: program_studi.php");
            exit;
        }
        try {
            // Ambil nama prodi saat ini untuk membandingkan (case-insensitive)
            $currentStmt = $pdo->prepare("SELECT nama_prodi FROM prodi_tbl WHERE prodi_id = ?");
            $currentStmt->execute([$prodi_id]);
            $current = $currentStmt->fetch();

            if (!$current) {
                $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-alert-circle me-2"></i>
                        <div>Program studi tidak ditemukan!</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
                header("Location: program_studi.php");
                exit;
            }
            // Jika nama baru sama dengan nama lama (case-sensitive), tetap update jika ada beda huruf kapital dan tampilkan pesan sukses
            if ($current['nama_prodi'] === $nama_prodi) {
                $_SESSION['flash_message'] = '<div class="alert alert-success alert-dismissible mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-circle-check me-2"></i>
                        <div>Program studi berhasil diperbarui!</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
                header("Location: program_studi.php");
                exit;
            }
            // cek ketersediaan nama prodi baru (case-insensitive) untuk prodi lain
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM prodi_tbl WHERE LOWER(nama_prodi) = LOWER(?) AND prodi_id != ?");
            $checkStmt->execute([$nama_prodi, $prodi_id]);
            $exists = (int) $checkStmt->fetchColumn();

            if ($exists > 0) {
                $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-alert-circle me-2"></i>
                        <div>Gagal mengganti program studi! (Mungkin duplikat)</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
                header("Location: program_studi.php");
                exit;
            }

            // Update prodi
            $updateStmt = $pdo->prepare("UPDATE prodi_tbl SET nama_prodi = ? WHERE prodi_id = ?");
            $updateStmt->execute([$nama_prodi, $prodi_id]);

            $_SESSION['flash_message'] = '<div class="alert alert-success alert-dismissible mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="ti ti-circle-check me-2"></i>
                    <div>Program studi berhasil diperbarui!</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            header("Location: program_studi.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="ti ti-alert-circle me-2"></i>
                    <div>Gagal memperbarui program studi!</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            header("Location: program_studi.php");
            exit;
        }
    }
}
// Ambil flash message (jika ada) lalu kosongkan supaya tampil sekali saja
$flash_message = '';
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
// Ambil daftar program studi beserta jumlah user/mahasiswa per prodi
// LEFT JOIN dipakai supaya prodi tanpa user tetap muncul dengan count 0
$stmt = $pdo->query("
    SELECT p.prodi_id, p.nama_prodi, COUNT(u.userid) as jumlah_user
    FROM prodi_tbl p
    LEFT JOIN user_tbl u ON p.prodi_id = u.prodi_id
    GROUP BY p.prodi_id, p.nama_prodi
    ORDER BY p.nama_prodi ASC
");
$prodis = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Studi — Sistem Akademik</title>

    <!-- CSS Tabler -->
    <link rel="stylesheet" href="css/tabler.min.css">
    <!-- Ikon Tabler -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/tabler-icons.min.css">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
        }

        .wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .page-wrapper {
            flex: 1;
        }

        .navbar-brand-text {
            font-weight: 700;
            font-size: 1rem;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert {
            animation: slideDown .3s ease;
        }

        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.75) !important;
            transition: color .2s;
            padding: 0.5rem 0.75rem;
        }

        .navbar-nav .nav-link:hover {
            color: #fff !important;
        }

        .navbar-nav .nav-link.active {
            color: #fff !important;
            font-weight: 600;
            border-bottom: 2px solid rgba(255, 255, 255, 0.85);
        }

        @media (min-width: 992px) {
            :root {
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
        }
    </style>
</head>

<body class="antialiased">
    <div class="wrapper">
        <nav class="navbar navbar-dark bg-blue sticky-top d-print-none">
            <div class="container-xl">
                <!-- Logo/Merek -->
                <a href="dashboard.php" class="navbar-brand d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-school" width="28"
                        height="28" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M22 9l-10 -4l-10 4l10 4l10 -4v6" />
                        <path d="M6 10.6v5.4a6 3 0 0 0 12 0v-5.4" />
                    </svg>
                    <span class="navbar-brand-text">Sistem Akademik</span>
                </a>

                <!-- Hamburger (seluler) - tombol collapse Bootstrap -->
                <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarToggleExternalContent" aria-controls="navbarToggleExternalContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <i class="ti ti-menu-2"></i>
                </button>

                <!-- Menu desktop -->
                <div class="d-none d-md-flex ms-auto">
                    <ul class="navbar-nav flex-row align-items-center gap-2">
                        <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i
                                    class="ti ti-home me-1"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="profile.php"><i
                                    class="ti ti-user me-1"></i>Profile</a></li>
                        <li class="nav-item"><a class="nav-link text-white active" href="program_studi.php"><i
                                    class="ti ti-building-community me-1"></i>Program Studi</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="user.php"><i
                                    class="ti ti-users me-1"></i>User</a></li>
                        <li class="nav-item"><a href="logout.php" class="btn btn-light ms-2"><i
                                    class="ti ti-logout me-1"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Menu collapse seluler (gaya contoh Bootstrap) -->
        <div class="collapse d-md-none" id="navbarToggleExternalContent">
            <div class="bg-blue p-3">
                <div class="d-grid gap-1">
                    <a class="btn btn-link text-white text-start w-100 border-bottom m-0" href="dashboard.php"><i
                            class="ti ti-home me-1"></i>Dashboard</a>
                    <a class="btn btn-link text-white text-start w-100 border-bottom m-0" href="profile.php"><i
                            class="ti ti-user me-1"></i>Profile</a>
                    <a class="btn btn-link text-white text-start w-100 border-bottom m-0 active"
                        href="program_studi.php"><i class="ti ti-building-community me-1"></i>Program Studi</a>
                    <a class="btn btn-link text-white text-start w-100 border-bottom m-0" href="user.php"><i
                            class="ti ti-users me-1"></i>User</a>
                    <a class="btn btn-light w-100 mt-2" href="logout.php"><i class="ti ti-logout me-1"></i>Logout</a>
                </div>
            </div>
        </div>
        <div class="page-wrapper">
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <div class="page-pretitle">Akademik</div>
                            <h2 class="page-title">Manajemen Program Studi</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page-body">
                <div class="container-xl">
                    <?php if ($flash_message): ?>
                        <div class="mb-3">
                            <?php echo $flash_message; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Card Form Tambah Prodi -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ti ti-plus me-2 text-blue"></i>Tambah Program Studi Baru
                            </h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="action" value="add">
                                <div class="col-md-9">
                                    <label class="form-label required">Nama Program Studi</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ti ti-building-community"></i></span>
                                        <input type="text" name="nama_prodi" class="form-control"
                                            placeholder="Contoh: Ilmu Komputer" required>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="ti ti-plus me-1"></i>Tambah
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Card Daftar Prodi -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ti ti-building-community me-2 text-blue"></i>Daftar Program Studi
                            </h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter table-hover table-striped card-table">
                                <thead>
                                    <tr>
                                        <th class="w-1 text-center">No</th>
                                        <th>Nama Program Studi</th>
                                        <th class="text-center">Jumlah User</th>
                                        <th class="w-1 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($prodis) > 0): ?>
                                        <?php foreach ($prodis as $index => $prodi): ?>
                                            <tr>
                                                <td class="text-center text-muted">
                                                    <small><?php echo str_pad($index + 1, 3, '0', STR_PAD_LEFT); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($prodi['nama_prodi']); ?></strong>
                                                </td>
                                                <td class="text-center">
                                                    <?php
                                                    $count = (int) $prodi['jumlah_user'];
                                                    $color = ($count === 0) ? 'secondary' : 'blue';
                                                    ?>
                                                    <span
                                                        class="badge rounded-pill bg-<?php echo $color; ?> text-white"><?php echo $count; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-list flex-nowrap justify-content-center">
                                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                            data-bs-target="#editModal"
                                                            onclick="setEditData(<?php echo $prodi['prodi_id']; ?>, '<?php echo htmlspecialchars(addslashes($prodi['nama_prodi'])); ?>')">
                                                            <i class="ti ti-edit me-1"></i>Edit
                                                        </button>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="prodi_id"
                                                                value="<?php echo $prodi['prodi_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" <?php echo ($prodi['jumlah_user'] > 0) ? 'disabled' : 'onclick="return confirm(\'Apakah Anda yakin ingin menghapus program studi ini?\')"'; ?>>
                                                                <i class="ti ti-trash me-1"></i>Hapus
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4">
                                                <div class="empty">
                                                    <div class="empty-img">
                                                        <i class="ti ti-inbox" style="font-size:3rem;color:#94a3b8"></i>
                                                    </div>
                                                    <p class="empty-title">Tidak ada program studi</p>
                                                    <p class="empty-subtitle text-muted">Belum ada program studi. Tambahkan
                                                        yang baru menggunakan form di atas.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <p class="m-0 text-muted small">
                                Menampilkan <strong><?= count($prodis) ?></strong> program studi
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal Edit Prodi -->
            <div class="modal modal-blur fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Program Studi</h5>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="prodi_id" id="editProdiId" value="">
                                <div class="mb-3">
                                    <label class="form-label">Nama Program Studi</label>
                                    <input type="text" name="nama_prodi" id="editProdiName" class="form-control"
                                        placeholder="Masukkan nama program studi" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="ti ti-x me-1"></i>Batal
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-check me-1"></i>Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <footer class="footer d-print-none bg-blue text-white border-top border-blue py-2"
                style="min-height: auto; margin-top: auto;">
                <div class="container-xl d-flex align-items-center justify-content-center" style="min-height: 50px;">
                    <div class="text-center small">
                        <span class="text-white fw-semibold">Sistem Akademik &copy; 2026</span>
                        <span class="text-white-50 ms-3 me-3">•</span>
                        <span class="text-white-50">Built with Tabler (https://tabler.io)</span>
                        <span class="text-white-50 ms-3 me-3">•</span>
                        <span class="text-white-50">License: MIT</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="js/bootstrap.bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
    <script>
        function setEditData(prodiId, prodiName) {
            document.getElementById('editProdiId').value = prodiId;
            document.getElementById('editProdiName').value = prodiName;
            document.getElementById('editProdiName').focus();
        }
    </script>
</body>

</html>
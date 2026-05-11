<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = '
        <div class="alert alert-danger alert-important" role="alert">
            <div class="d-flex align-items-center">
                <div class="me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon me-0" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                        <path d="M12 8v4" />
                        <path d="M12 16h.01" />
                    </svg>
                </div>
                <div>
                    <h4 class="alert-title mb-0">Harap login terlebih dahulu!</h4>
                </div>
            </div>
        </div>';
    header("Location: index.php");
    exit;
}

// Database connection
$host    = 'localhost';
$db      = 'pemweb_db';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn     = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die('<div style="padding:2rem;font-family:monospace;color:#e74c3c;">
        <b>Koneksi database gagal:</b><br>' . htmlspecialchars($e->getMessage()) . '
    </div>');
}

// Ambil data user yang sedang login
$stmt = $pdo->prepare("SELECT u.*, p.nama_prodi FROM user_tbl u LEFT JOIN prodi_tbl p ON u.prodi_id = p.prodi_id WHERE u.userid = ?");
$stmt->execute([$_SESSION['user_id']]);
$userLogin = $stmt->fetch();

if (!$userLogin) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Ambil semua prodi untuk dropdown
$prodiList = $pdo->query("SELECT prodi_id, nama_prodi FROM prodi_tbl ORDER BY nama_prodi ASC")->fetchAll();

$success_message = '';
$error_message   = '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_email        = trim($_POST['email'] ?? '');
    $new_prodi_id     = !empty($_POST['prodi_id']) ? (int)$_POST['prodi_id'] : null;
    $new_password     = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi email
    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid!";
    }
    // Cek email sudah dipakai user lain
    elseif ($new_email !== $userLogin['email']) {
        $cek = $pdo->prepare("SELECT userid FROM user_tbl WHERE email = ? AND userid != ?");
        $cek->execute([$new_email, $_SESSION['user_id']]);
        if ($cek->fetch()) {
            $error_message = "Email sudah digunakan oleh user lain!";
        }
    }

    if (empty($error_message)) {
        // Kalau password diisi, validasi dan hash
        if (!empty($new_password)) {
            if (strlen($new_password) < 8) {
                $error_message = "Password minimal 8 karakter!";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "Konfirmasi password tidak cocok!";
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE user_tbl SET email = ?, password = ?, prodi_id = ? WHERE userid = ?");
                $update->execute([$new_email, $hashed, $new_prodi_id, $_SESSION['user_id']]);
                $success_message = "Profil berhasil diperbarui (termasuk password).";
            }
        } else {
            // Update tanpa ubah password
            $update = $pdo->prepare("UPDATE user_tbl SET email = ?, prodi_id = ? WHERE userid = ?");
            $update->execute([$new_email, $new_prodi_id, $_SESSION['user_id']]);
            $success_message = "Profil berhasil diperbarui.";
        }

        if (empty($error_message)) {
            // Refresh data setelah update
            $_SESSION['email'] = $new_email;
            $stmt = $pdo->prepare("SELECT u.*, p.nama_prodi FROM user_tbl u LEFT JOIN prodi_tbl p ON u.prodi_id = p.prodi_id WHERE u.userid = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $userLogin = $stmt->fetch();
        }
    }
}

$initial = strtoupper(substr($userLogin['email'], 0, 1));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — Sistem Akademik</title>

    <!-- Tabler CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/tabler-icons.min.css">

    <style>
        .avatar-circle {
            width: 80px;
            height: 80px;
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #206bc4;
            color: #fff;
            margin: 0 auto 1rem;
        }
    </style>
</head>

<body class="antialiased">
    <div class="wrapper">

        <!-- Navbar -->
        <header class="navbar navbar-expand-md navbar-dark bg-blue sticky-top d-print-none">
            <div class="container-xl">
                <a href="dashboard.php" class="navbar-brand d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-school" width="28" height="28"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M22 9l-10 -4l-10 4l10 4l10 -4v6" />
                        <path d="M6 10.6v5.4a6 3 0 0 0 12 0v-5.4" />
                    </svg>
                    <span class="fw-bold" style="font-size:1rem;">Sistem Akademik</span>
                </a>

                <div class="ms-auto d-flex align-items-center gap-2">
                    <nav class="nav d-none d-md-flex">
                        <a class="nav-link text-white" href="dashboard.php">
                            <i class="ti ti-home me-1"></i>Dashboard
                        </a>
                        <a class="nav-link text-white fw-bold" href="profile.php">
                            <i class="ti ti-user me-1"></i>Profile
                        </a>
                        <a class="nav-link text-white" href="program_studi.php">
                            <i class="ti ti-building-community me-1"></i>Program Studi
                        </a>
                        <a class="nav-link text-white" href="user.php">
                            <i class="ti ti-users me-1"></i>User
                        </a>
                    </nav>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <!-- Page Header -->
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <div class="page-pretitle">Akademik</div>
                            <h2 class="page-title">
                                <i class="ti ti-user-circle me-2"></i>Profile
                            </h2>
                        </div>
                        <div class="col-auto ms-auto d-print-none">
                            <a href="logout.php" class="btn btn-danger">
                                <i class="ti ti-logout me-1"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page-body">
                <div class="container-xl">

                    <!-- Alert sukses/error -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible mb-4" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="ti ti-circle-check me-2"></i>
                                <div><?= htmlspecialchars($success_message) ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="ti ti-alert-circle me-2"></i>
                                <div><?= htmlspecialchars($error_message) ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row justify-content-center">
                        <div class="col-12 col-md-8 col-lg-6">

                            <!-- Card Info Profil -->
                            <div class="card mb-4">
                                <div class="card-body text-center py-4">
                                    <div class="avatar-circle"><?= htmlspecialchars($initial) ?></div>
                                    <h3 class="mb-1"><?= htmlspecialchars($userLogin['email']) ?></h3>
                                    <div class="text-muted">
                                        <i class="ti ti-building-community me-1"></i>
                                        <?= htmlspecialchars($userLogin['nama_prodi'] ?? 'Belum ada program studi') ?>
                                    </div>
                                    <div class="mt-2">
                                        <span class="badge bg-blue-lt">
                                            <i class="ti ti-id me-1"></i>ID: <?= $userLogin['userid'] ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Form Edit Profil -->
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="ti ti-edit me-2 text-blue"></i>Edit Profil
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">

                                        <!-- Email -->
                                        <div class="mb-3">
                                            <label class="form-label" for="email">
                                                Email <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ti ti-mail"></i>
                                                </span>
                                                <input type="email"
                                                    class="form-control"
                                                    id="email"
                                                    name="email"
                                                    value="<?= htmlspecialchars($userLogin['email']) ?>"
                                                    required>
                                            </div>
                                        </div>

                                        <!-- Program Studi -->
                                        <div class="mb-3">
                                            <label class="form-label" for="prodi_id">Program Studi</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ti ti-building-community"></i>
                                                </span>
                                                <select class="form-select" id="prodi_id" name="prodi_id">
                                                    <option value="">— Tidak ada —</option>
                                                    <?php foreach ($prodiList as $prodi): ?>
                                                        <option value="<?= $prodi['prodi_id'] ?>"
                                                            <?= $userLogin['prodi_id'] == $prodi['prodi_id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($prodi['nama_prodi']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <p class="text-muted small mb-3">
                                            <i class="ti ti-info-circle me-1"></i>
                                            Kosongkan field password jika tidak ingin mengubah password.
                                        </p>

                                        <!-- Password Baru -->
                                        <div class="mb-3">
                                            <label class="form-label" for="password">Password Baru</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ti ti-lock"></i>
                                                </span>
                                                <input type="password"
                                                    class="form-control"
                                                    id="password"
                                                    name="password"
                                                    placeholder="Min. 8 karakter"
                                                    minlength="8">
                                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                    <i class="ti ti-eye" id="eyeIcon"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Konfirmasi Password -->
                                        <div class="mb-4">
                                            <label class="form-label" for="confirm_password">Konfirmasi Password Baru</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ti ti-lock-check"></i>
                                                </span>
                                                <input type="password"
                                                    class="form-control"
                                                    id="confirm_password"
                                                    name="confirm_password"
                                                    placeholder="Ulangi password baru">
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="ti ti-device-floppy me-1"></i>Simpan Perubahan
                                        </button>
                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

            <footer class="footer footer-transparent d-print-none">
                <div class="container-xl">
                    <div class="row text-center align-items-center">
                        <div class="col-12 col-lg-auto mt-3 mt-lg-0 text-muted small">
                            Sistem Akademik &copy; <?= date('Y') ?>
                        </div>
                        <div class="col-12 col-lg-auto ms-lg-auto mt-2 mt-lg-0 text-muted small">
                            <div>Built with Tabler (https://tabler.io)</div>
                            <div>License: MIT</div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
    <script>
        // Toggle show/hide password
        document.getElementById('togglePassword').addEventListener('click', function() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('ti-eye', 'ti-eye-off');
            } else {
                input.type = 'password';
                icon.classList.replace('ti-eye-off', 'ti-eye');
            }
        });
    </script>
</body>

</html>
<?php
// Sertakan autentikasi & konfigurasi
require_once 'auth.php';
require_once 'config.php';

// Ambil koneksi database
$pdo = getDBConnection();

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
$error_message = '';

// Menangani submit form (email tidak bisa diubah)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_prodi_id = !empty($_POST['prodi_id']) ? (int) $_POST['prodi_id'] : null;
    $new_password = $_POST['password'] ?? '';
    $old_password = $_POST['old_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $isPhotoChanged = false;

    // Logika Upload Foto Profil
    $foto_profil_name = $userLogin['foto_profil']; // Default ke foto lama
    if (isset($_FILES['foto_profil']) && !empty($_FILES['foto_profil']['name'])) {
        if ($_FILES['foto_profil']['error'] !== UPLOAD_ERR_OK) {
            switch ($_FILES['foto_profil']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message = "Ukuran file melebihi batas upload server, harus kurang dari atau sama dengan 5mb.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message = "File terunggah sebagian. Silakan coba lagi.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error_message = "Folder sementara upload tidak tersedia di server.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error_message = "Server gagal menyimpan file upload.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error_message = "Upload diblokir oleh konfigurasi ekstensi server.";
                    break;
                default:
                    $error_message = "Terjadi kesalahan saat upload foto profil.";
                    break;
            }
        } else {
            $file_tmp = $_FILES['foto_profil']['tmp_name'];
            $file_name = $_FILES['foto_profil']['name'];
            $file_size = $_FILES['foto_profil']['size'];
            $file_type = '';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo !== false) {
                    $detected = finfo_file($finfo, $file_tmp);
                    if ($detected !== false) {
                        $file_type = $detected;
                    }
                    finfo_close($finfo);
                }
            } elseif (function_exists('mime_content_type')) {
                $detected = mime_content_type($file_tmp);
                if ($detected !== false) {
                    $file_type = $detected;
                }
            } else {
                $image_info = @getimagesize($file_tmp);
                if ($image_info !== false && isset($image_info['mime'])) {
                    $file_type = $image_info['mime'];
                }
            }

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // max file size

            if (!in_array($file_type, $allowed_types, true)) {
                $error_message = "Tipe file tidak diizinkan. Hanya JPG, PNG, dan GIF.";
            } elseif ($file_size > $max_size) {
                $error_message = "Ukuran file terlalu besar. Maksimal 2MB.";
            } else {
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                if ($ext === '') {
                    $ext = 'jpg';
                }

                $new_file_name = $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                $upload_path = 'uploads/profiles/' . $new_file_name;

                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Hapus foto lama jika ada dan bukan default
                    if (!empty($userLogin['foto_profil']) && file_exists('uploads/profiles/' . $userLogin['foto_profil'])) {
                        unlink('uploads/profiles/' . $userLogin['foto_profil']);
                    }
                    $foto_profil_name = $new_file_name;
                    $isPhotoChanged = true;
                } else {
                    $error_message = "Gagal mengunggah foto profil.";
                }
            }
        }
    }

    if (empty($error_message)) {
        // Kalau password diisi, validasi dan hash
        if (!empty($new_password)) {
            if (empty($old_password)) {
                $error_message = "Password lama harus diisi jika ingin mengubah password!";
            } elseif (!password_verify($old_password, $userLogin['password'])) {
                $error_message = "Password lama salah!";
            } elseif (strlen($new_password) < 8) {
                $error_message = "Password minimal 8 karakter!";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "Konfirmasi password tidak cocok!";
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE user_tbl SET password = ?, prodi_id = ?, foto_profil = ? WHERE userid = ?");
                $update->execute([$hashed, $new_prodi_id, $foto_profil_name, $_SESSION['user_id']]);

                // Paksa logout dan minta login ulang setelah ganti password
                $_SESSION['flash_message'] = '
                <div class="alert alert-success alert-important" role="alert">
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
                            <h4 class="alert-title mb-0">Silahkan login ulang</h4>
                        </div>
                    </div>
                </div>';

                unset($_SESSION['user_id'], $_SESSION['email']);
                header("Location: index.php");
                exit;
            }
        } else {
            // Update hanya prodi dan foto (tanpa ubah password)
            $isProdiChanged = ((string) ($userLogin['prodi_id'] ?? '') !== (string) ($new_prodi_id ?? ''));

            if ($isProdiChanged || $isPhotoChanged) {
                $update = $pdo->prepare("UPDATE user_tbl SET prodi_id = ?, foto_profil = ? WHERE userid = ?");
                $update->execute([$new_prodi_id, $foto_profil_name, $_SESSION['user_id']]);
                $success_message = "Profil berhasil diperbarui.";
            } else {
                $error_message = "Tidak ada perubahan yang disimpan.";
            }
        }

        if (empty($error_message)) {
            // Refresh data setelah update
            $stmt = $pdo->prepare("SELECT u.*, p.nama_prodi FROM user_tbl u LEFT JOIN prodi_tbl p ON u.prodi_id = p.prodi_id WHERE u.userid = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $userLogin = $stmt->fetch();
        }
    }
}

$initial = strtoupper(substr($userLogin['email'], 0, 1));
// Cek foto profil dari database
$hasProfileImg = false;
$profileImgPath = '';
if (!empty($userLogin['foto_profil']) && file_exists('uploads/profiles/' . $userLogin['foto_profil'])) {
    $hasProfileImg = true;
    $profileImgPath = 'uploads/profiles/' . $userLogin['foto_profil'];
} else if (is_file(__DIR__ . '/images/profile.png')) { // Fallback ke default gambar
    $hasProfileImg = true;
    $profileImgPath = 'images/profile.png';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — Sistem Akademik</title>

    <!-- Ikon Tabler -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/tabler-icons.min.css">
    <!-- CSS Tabler -->
    <link rel="stylesheet" href="css/tabler.min.css">

    <style>
        html,
        body {
            height: 100%;
        }

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

        .avatar-circle-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
            margin: 0 auto 1rem;
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

        /* Gaya navbar (konsisten di semua halaman) */
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

        <!-- Navigasi (Navbar) -->
        <nav class="navbar navbar-dark bg-blue sticky-top d-print-none">
            <div class="container-xl">
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
                        <li class="nav-item"><a class="nav-link text-white active" href="profile.php"><i
                                    class="ti ti-user me-1"></i>Profile</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="program_studi.php"><i
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
                    <a class="btn btn-link text-white text-start w-100 border-bottom m-0 active" href="profile.php"><i
                            class="ti ti-user me-1"></i>Profile</a>
                    <a class="btn btn-link text-white text-start w-100 border-bottom m-0" href="program_studi.php"><i
                            class="ti ti-building-community me-1"></i>Program Studi</a>
                    <a class="btn btn-link text-white text-start w-100 border-bottom m-0" href="user.php"><i
                            class="ti ti-users me-1"></i>User</a>
                    <a class="btn btn-light w-100 mt-2" href="logout.php"><i class="ti ti-logout me-1"></i>Logout</a>
                </div>
            </div>
        </div>

        <div class="page-wrapper">
            <!-- Header Halaman -->
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <div class="page-pretitle">Akademik</div>
                            <h2 class="page-title">
                                <i class="ti ti-user-circle me-2"></i>Profile
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page-body">
                <div class="container-xl">

                    <!-- Notifikasi sukses/error -->
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
                                    <?php if ($hasProfileImg): ?>
                                        <img src="<?= htmlspecialchars($profileImgPath) ?>" alt="Profile"
                                            class="avatar-circle-img">
                                    <?php else: ?>
                                        <div class="avatar-circle"><?= htmlspecialchars($initial) ?></div>
                                    <?php endif; ?>
                                    <h3 class="mb-1"><?= htmlspecialchars($userLogin['email']) ?></h3>
                                    <div class="text-muted">
                                        <i class="ti ti-building-community me-1"></i>
                                        <?= htmlspecialchars($userLogin['nama_prodi'] ?? 'Belum ada program studi') ?>
                                    </div>
                                    <div class="mt-2 d-flex align-items-center justify-content-center">
                                        <span class="badge bg-blue-lt"
                                            title="<?= htmlspecialchars($userLogin['userid']) ?>">
                                            <i class="ti ti-fingerprint me-1"></i>UUID:
                                            <?= htmlspecialchars($userLogin['userid']) ?>
                                        </span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2"
                                            id="copyUuidBtn" data-uuid="<?= htmlspecialchars($userLogin['userid']) ?>"
                                            aria-label="Copy UUID">
                                            <i class="ti ti-copy"></i>
                                        </button>
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
                                    <form method="POST" action="" enctype="multipart/form-data">

                                        <!-- Foto Profil -->
                                        <div class="mb-3">
                                            <label class="form-label" for="foto_profil">Foto Profil</label>
                                            <input type="file" class="form-control" id="foto_profil" name="foto_profil"
                                                accept="image/png, image/jpeg, image/gif">
                                            <small class="form-hint">Format yang diizinkan: JPG, PNG, GIF. Maksimal
                                                2MB.</small>
                                        </div>

                                        <!-- Email (Read-only) -->
                                        <div class="mb-3">
                                            <label class="form-label" for="email">
                                                Email
                                                <span class="badge bg-muted-lt ms-1"><i
                                                        class="ti ti-lock-filled me-1"></i>Tidak dapat diubah</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ti ti-mail"></i>
                                                </span>
                                                <input type="email" class="form-control" id="email"
                                                    value="<?= htmlspecialchars($userLogin['email']) ?>" disabled
                                                    readonly>
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

                                        <!-- Password Lama -->
                                        <div class="mb-3">
                                            <label class="form-label" for="old_password">Password Lama</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ti ti-lock"></i>
                                                </span>
                                                <input type="password" class="form-control" id="old_password"
                                                    name="old_password"
                                                    placeholder="Masukkan password saat ini jika ingin mengubah password">
                                            </div>
                                        </div>

                                        <!-- Password Baru -->
                                        <div class="mb-3">
                                            <label class="form-label" for="password">Password Baru</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ti ti-lock-plus"></i>
                                                </span>
                                                <input type="password" class="form-control" id="password"
                                                    name="password" placeholder="Min. 8 karakter" minlength="8">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    id="togglePassword">
                                                    <i class="ti ti-eye" id="eyeIcon"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Konfirmasi Password -->
                                        <div class="mb-4">
                                            <label class="form-label" for="confirm_password">Konfirmasi Password
                                                Baru</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ti ti-lock-check"></i>
                                                </span>
                                                <input type="password" class="form-control" id="confirm_password"
                                                    name="confirm_password" placeholder="Ulangi password baru">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
    <script>
        // Tombol tampil/sembunyikan password
        document.getElementById('togglePassword').addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('ti-eye-off', 'ti-eye');
            } else {
                input.type = 'password';
                icon.classList.replace('ti-eye', 'ti-eye-off');
            }
        });
    </script>
    <script>
        // Copy UUID to clipboard
        document.getElementById('copyUuidBtn')?.addEventListener('click', function () {
            const uuid = this.getAttribute('data-uuid') || '';
            if (!uuid) return;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(uuid).then(() => {
                    const original = this.innerHTML;
                    this.innerHTML = '<i class="ti ti-check"></i>';
                    setTimeout(() => this.innerHTML = original, 1000);
                }).catch(() => {
                    // fallthrough: silent
                });
            } else {
                // Fallback for older browsers
                const ta = document.createElement('textarea');
                ta.value = uuid;
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); } catch (e) { }
                document.body.removeChild(ta);
            }
        });
    </script>
</body>

</html>
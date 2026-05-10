<?php
session_start();

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

$pesan  = '';
$jenisP = '';
if (!empty($_SESSION['pesan'])) {
  $pesan  = $_SESSION['pesan'];
  $jenisP = $_SESSION['jenisP'];
  unset($_SESSION['pesan'], $_SESSION['jenisP']);
}

$aksi = $_REQUEST['aksi'] ?? '';


// ---------- TAMBAH ----------
if ($aksi === 'tambah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $email    = trim($_POST['email']   ?? '');
  $password = $_POST['password']     ?? '';
  $prodi_id = !empty($_POST['prodi_id']) ? (int)$_POST['prodi_id'] : null;

  if ($email === '' || $password === '') {
    $pesan = 'Email dan Password wajib diisi.';
    $jenisP = 'error';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $pesan = 'Format email tidak valid.';
    $jenisP = 'error';
  } else {
    try {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("INSERT INTO user_tbl (email, password, prodi_id) VALUES (?, ?, ?)");
      $stmt->execute([$email, $hash, $prodi_id]);
      $_SESSION['pesan']  = "User <strong>$email</strong> berhasil ditambahkan.";
      $_SESSION['jenisP'] = 'sukses';
      header("Location: user.php");
      exit;
    } catch (\PDOException $e) {
      $pesan  = ($e->errorInfo[1] == 1062) ? 'Email sudah digunakan, gunakan email lain.' : 'Gagal menambahkan: ' . $e->getMessage();
      $jenisP = 'error';
    }
  }
}

// ---------- EDIT ----------
if ($aksi === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $userid   = (int)($_POST['userid'] ?? 0);
  $email    = trim($_POST['email']   ?? '');
  $password = $_POST['password']     ?? '';
  $prodi_id = !empty($_POST['prodi_id']) ? (int)$_POST['prodi_id'] : null;

  if ($email === '') {
    $pesan = 'Email wajib diisi.';
    $jenisP = 'error';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $pesan = 'Format email tidak valid.';
    $jenisP = 'error';
  } else {
    try {
      if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE user_tbl SET email=?, password=?, prodi_id=? WHERE userid=?");
        $stmt->execute([$email, $hash, $prodi_id, $userid]);
      } else {
        $stmt = $pdo->prepare("UPDATE user_tbl SET email=?, prodi_id=? WHERE userid=?");
        $stmt->execute([$email, $prodi_id, $userid]);
      }
      $_SESSION['pesan']  = "User <strong>$email</strong> berhasil diperbarui.";
      $_SESSION['jenisP'] = 'sukses';
      header("Location: user.php");
      exit;
    } catch (\PDOException $e) {
      $pesan  = ($e->errorInfo[1] == 1062) ? 'Email sudah digunakan oleh user lain.' : 'Gagal memperbarui: ' . $e->getMessage();
      $jenisP = 'error';
    }
  }
  $aksi = '';
}

// ---------- HAPUS ----------
if ($aksi === 'hapus' && isset($_GET['userid'])) {
  $userid = (int)$_GET['userid'];
  $stmt   = $pdo->prepare("DELETE FROM user_tbl WHERE userid=?");
  $stmt->execute([$userid]);
  $_SESSION['pesan']  = 'User berhasil dihapus.';
  $_SESSION['jenisP'] = 'sukses';
  header("Location: user.php");
  exit;
}

// ---------- AMBIL DATA EDIT ----------
$dataEdit = null;
if ($aksi === 'form_edit' && isset($_GET['userid'])) {
  $userid   = (int)$_GET['userid'];
  $stmt     = $pdo->prepare("SELECT * FROM user_tbl WHERE userid=?");
  $stmt->execute([$userid]);
  $dataEdit = $stmt->fetch();
}

$prodiList = $pdo->query("SELECT prodi_id, nama_prodi FROM prodi_tbl ORDER BY nama_prodi")->fetchAll();

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
  $stmt = $pdo->prepare("
        SELECT u.*, p.nama_prodi
        FROM user_tbl u
        LEFT JOIN prodi_tbl p ON u.prodi_id = p.prodi_id
        WHERE u.email LIKE ? OR p.nama_prodi LIKE ?
        ORDER BY u.userid ASC
    ");
  $stmt->execute(["%$search%", "%$search%"]);
} else {
  $stmt = $pdo->query("
        SELECT u.*, p.nama_prodi
        FROM user_tbl u
        LEFT JOIN prodi_tbl p ON u.prodi_id = p.prodi_id
        ORDER BY u.userid ASC
    ");
}
$users = $stmt->fetchAll();

$isEdit    = ($aksi === 'form_edit' && $dataEdit);
$formAksi  = $isEdit ? 'edit' : 'tambah';
$formJudul = $isEdit ? 'Edit User' : 'Tambah User Baru';

$currentPage = 'user';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen User — Sistem Akademik</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/tabler-icons.min.css">

  <style>
    .navbar-brand-text {
      font-weight: 700;
      font-size: 1rem;
    }
    .avatar-initials {
      font-size: .75rem;
      font-weight: 700;
    }
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-8px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .alert { animation: slideDown .3s ease; }

    .navbar-nav .nav-link {
      color: rgba(255,255,255,0.75) !important;
      transition: color .2s;
      padding: 0.5rem 0.75rem;
    }
    .navbar-nav .nav-link:hover {
      color: #fff !important;
    }
    .navbar-nav .nav-link.active {
      color: #fff !important;
      font-weight: 600;
      border-bottom: 2px solid rgba(255,255,255,0.85);
    }
  </style>
</head>

<body class="antialiased">
<div class="wrapper">

  <header class="navbar navbar-expand-md navbar-dark bg-blue sticky-top d-print-none">
    <div class="container-xl">

      <!-- Brand -->
      <a href="lamanUtama.php" class="navbar-brand d-flex align-items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-school" width="28" height="28"
            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
          <path d="M22 9l-10 -4l-10 4l10 4l10 -4v6"/>
          <path d="M6 10.6v5.4a6 3 0 0 0 12 0v-5.4"/>
        </svg>
        <span class="navbar-brand-text">Sistem Akademik</span>
      </a>

      <!-- Hamburger (mobile) -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu"
        aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Nav links -->
      <div class="collapse navbar-collapse" id="navbarMenu">
        <ul class="navbar-nav ms-auto d-flex align-items-md-center">
          <li class="nav-item">
            <a href="dashboard.php" class="nav-link d-flex align-items-center gap-1 <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
              <i class="ti ti-layout-dashboard"></i> Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a href="profile.php" class="nav-link d-flex align-items-center gap-1 <?= $currentPage === 'profile' ? 'active' : '' ?>">
              <i class="ti ti-user-circle"></i> Profile
            </a>
          </li>
          <li class="nav-item">
            <a href="prodi.php" class="nav-link d-flex align-items-center gap-1 <?= $currentPage === 'prodi' ? 'active' : '' ?>">
              <i class="ti ti-building-community"></i> Program Studi
            </a>
          </li>
          <li class="nav-item">
            <a href="user.php" class="nav-link d-flex align-items-center gap-1 <?= $currentPage === 'user' ? 'active' : '' ?>">
              <i class="ti ti-users"></i> User
              <span class="badge bg-white text-blue fw-bold ms-1"><?= count($users) ?></span>
            </a>
          </li>
        </ul>
      </div>

    </div>
  </header>

  <div class="page-wrapper">

    <div class="page-header d-print-none">
      <div class="container-xl">
        <div class="row g-2 align-items-center">
          <div class="col">
            <div class="page-pretitle">Akademik</div>
            <h2 class="page-title">Manajemen User</h2>
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

        <?php if ($pesan): ?>
          <div class="alert alert-<?= $jenisP === 'sukses' ? 'success' : 'danger' ?> alert-dismissible mb-4" role="alert" id="flashAlert">
            <div class="d-flex align-items-center gap-2">
              <i class="ti ti-<?= $jenisP === 'sukses' ? 'circle-check' : 'alert-circle' ?> icon-lg"></i>
              <div><?= $pesan ?></div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
          </div>
        <?php endif; ?>

        <!-- Form Tambah / Edit -->
        <div class="card mb-4">
          <div class="card-header">
            <h3 class="card-title">
              <i class="ti ti-<?= $isEdit ? 'edit' : 'user-plus' ?> me-2 text-blue"></i>
              <?= $formJudul ?>
            </h3>
          </div>
          <div class="card-body">
            <form method="POST" action="user.php?aksi=<?= $formAksi ?>">
              <?php if ($isEdit): ?>
                <input type="hidden" name="userid" value="<?= $dataEdit['userid'] ?>">
              <?php endif; ?>

              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label required">Alamat Email</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="ti ti-mail"></i></span>
                    <input type="email" name="email" class="form-control"
                      placeholder="email@domain.com"
                      value="<?= htmlspecialchars($dataEdit['email'] ?? '') ?>"
                      required
                      oninvalid="this.setCustomValidity('Email tidak boleh kosong!')"
                      oninput="this.setCustomValidity('')">
                  </div>
                </div>

                <div class="col-md-4">
                  <label class="form-label <?= !$isEdit ? 'required' : '' ?>">
                    Password
                    <?php if ($isEdit): ?>
                      <span class="form-hint">Kosongkan jika tidak diubah</span>
                    <?php endif; ?>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="ti ti-lock"></i></span>
                    <input type="password" name="password" class="form-control"
                      placeholder="<?= $isEdit ? '••••••••' : 'Masukkan password' ?>"
                      <?= !$isEdit ? 'required' : '' ?>
                      oninvalid="this.setCustomValidity('Password wajib diisi!')"
                      oninput="this.setCustomValidity('')">
                  </div>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Program Studi</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="ti ti-building-community"></i></span>
                    <select name="prodi_id" class="form-select">
                      <option value="">— Pilih Program Studi —</option>
                      <?php foreach ($prodiList as $prodi): ?>
                        <option value="<?= $prodi['prodi_id'] ?>"
                          <?= (($dataEdit['prodi_id'] ?? '') == $prodi['prodi_id']) ? 'selected' : '' ?>>
                          <?= htmlspecialchars($prodi['nama_prodi']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                  <i class="ti ti-<?= $isEdit ? 'device-floppy' : 'user-plus' ?> me-1"></i>
                  <?= $isEdit ? 'Simpan Perubahan' : 'Tambah User' ?>
                </button>
                <?php if ($isEdit): ?>
                  <a href="user.php" class="btn btn-secondary">
                    <i class="ti ti-x me-1"></i>Batal
                  </a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <!-- Tabel User -->
        <div class="card">
          <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="card-title mb-0">
              <i class="ti ti-users me-2 text-blue"></i>Daftar User
            </h3>
            <form method="GET" action="user.php" class="d-flex align-items-center gap-2 flex-wrap">
              <div class="input-group input-group-sm" style="min-width:220px">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
                <input type="text" name="q" class="form-control"
                  placeholder="Cari email / prodi…"
                  value="<?= htmlspecialchars($search) ?>">
              </div>
              <button type="submit" class="btn btn-sm btn-secondary">Cari</button>
              <?php if ($search): ?>
                <a href="user.php" class="btn btn-sm btn-ghost-secondary">
                  <i class="ti ti-x me-1"></i>Reset
                </a>
              <?php endif; ?>
            </form>
          </div>

          <?php if (empty($users)): ?>
            <div class="card-body">
              <div class="empty">
                <div class="empty-img">
                  <i class="ti ti-user-off" style="font-size:4rem;color:#94a3b8"></i>
                </div>
                <p class="empty-title">Tidak ada user ditemukan</p>
                <p class="empty-subtitle text-muted">
                  <?= $search
                      ? "Tidak ada hasil untuk &ldquo;<strong>" . htmlspecialchars($search) . "</strong>&rdquo;. Coba kata kunci lain."
                      : 'Belum ada user. Tambahkan user pertama menggunakan form di atas.' ?>
                </p>
                <?php if ($search): ?>
                  <div class="empty-action">
                    <a href="user.php" class="btn btn-primary">
                      <i class="ti ti-arrow-left me-1"></i>Kembali
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-vcenter table-hover table-striped card-table">
                <thead>
                  <tr>
                    <th class="w-1 text-center">No</th>
                    <th>Email</th>
                    <th>Program Studi</th>
                    <th class="w-1">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users as $no => $u):
                    $colors = ['bg-blue', 'bg-azure', 'bg-indigo', 'bg-purple', 'bg-pink', 'bg-red', 'bg-orange', 'bg-teal', 'bg-green', 'bg-cyan'];
                    $avatarColor = $colors[$no % count($colors)];
                    $initial = strtoupper(substr($u['email'], 0, 1));
                  ?>
                    <tr>
                      <td class="text-center text-muted">
                        <small><?= str_pad($no + 1, 3, '0', STR_PAD_LEFT) ?></small>
                      </td>
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <span class="avatar avatar-sm <?= $avatarColor ?> text-white avatar-initials rounded-circle">
                            <?= $initial ?>
                          </span>
                          <span class="text-body fw-medium"><?= htmlspecialchars($u['email']) ?></span>
                        </div>
                      </td>
                      <td>
                        <?php if ($u['nama_prodi']): ?>
                          <span class="badge bg-blue-lt text-blue">
                            <i class="ti ti-school me-1"></i><?= htmlspecialchars($u['nama_prodi']) ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted fst-italic small">
                            <i class="ti ti-minus me-1"></i>Belum ada prodi
                          </span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="btn-list flex-nowrap">
                          <a href="user.php?aksi=form_edit&userid=<?= $u['userid'] ?>"
                            class="btn btn-sm btn-warning" title="Edit">
                            <i class="ti ti-edit me-1"></i>Edit
                          </a>
                          <button class="btn btn-sm btn-danger" title="Hapus"
                            onclick="konfirmHapus(<?= $u['userid'] ?>, '<?= htmlspecialchars(addslashes($u['email'])) ?>')">
                            <i class="ti ti-trash me-1"></i>Hapus
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
              <p class="m-0 text-muted small">
                Menampilkan <strong><?= count($users) ?></strong> user
                <?= $search ? "untuk pencarian &ldquo;<strong>" . htmlspecialchars($search) . "</strong>&rdquo;" : '' ?>
              </p>
            </div>
          <?php endif; ?>
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

<!-- Modal Konfirmasi Hapus -->
<div class="modal modal-blur fade" id="confirmModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-body">
        <div class="modal-title text-center mb-2">
          <div class="text-danger mb-3">
            <i class="ti ti-trash" style="font-size:2.5rem"></i>
          </div>
          <h3>Hapus User?</h3>
        </div>
        <p class="text-muted text-center" id="confirmText">Tindakan ini tidak dapat dibatalkan.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <a id="confirmBtn" href="#" class="btn btn-danger w-100">
          <i class="ti ti-trash me-1"></i>Ya, Hapus
        </a>
        <button type="button" class="btn w-100 mt-2" data-bs-dismiss="modal">
          Batal
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const flashAlert = document.getElementById('flashAlert');
    if (flashAlert) {
      setTimeout(function () {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(flashAlert);
        bsAlert.close();
      }, 4000);
    }
  });

  function konfirmHapus(userid, email) {
    document.getElementById('confirmText').innerHTML =
      'Hapus user <strong>' + email + '</strong>?<br>' +
      '<small class="text-muted">Tindakan ini tidak dapat dibatalkan.</small>';
    document.getElementById('confirmBtn').href = 'user.php?aksi=hapus&userid=' + userid;
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
  }
</script>

</body>
</html>

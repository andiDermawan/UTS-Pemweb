<?php
session_start();

require_once __DIR__ . '/config.php';

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

try {
  $pdo = db_connect();
} catch (\PDOException $e) {
  die('<div style="padding:2rem;font-family:monospace;color:#e74c3c;">
        <b>Koneksi database gagal:</b><br>' . htmlspecialchars($e->getMessage()) . '
    </div>');
}

// ============================================================
//  BACA PESAN FLASH DARI SESSION (hanya sekali, lalu hapus)
// ============================================================
$pesan = '';
$jenisP = '';
if (!empty($_SESSION['pesan'])) {
  $pesan = $_SESSION['pesan'];
  $jenisP = $_SESSION['jenisP'];
  unset($_SESSION['pesan'], $_SESSION['jenisP']);
}

$aksi = $_REQUEST['aksi'] ?? '';

// ============================================================
//  PROSES AKSI CRUD
// ============================================================

// ---------- TAMBAH ----------
if ($aksi === 'tambah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $prodi_id = !empty($_POST['prodi_id']) ? (int) $_POST['prodi_id'] : null;

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
      $_SESSION['pesan'] = "User <b>$email</b> berhasil ditambahkan.";
      $_SESSION['jenisP'] = 'sukses';
      header("Location: user.php");
      exit;
    } catch (\PDOException $e) {
      $pesan = ($e->errorInfo[1] == 1062) ? 'Email sudah digunakan, gunakan email lain.' : 'Gagal menambahkan: ' . $e->getMessage();
      $jenisP = 'error';
    }
  }
}

// ---------- EDIT ----------
if ($aksi === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $userid = (int) ($_POST['userid'] ?? 0);
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $prodi_id = !empty($_POST['prodi_id']) ? (int) $_POST['prodi_id'] : null;

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
      $_SESSION['pesan'] = "User <b>$email</b> berhasil diperbarui.";
      $_SESSION['jenisP'] = 'sukses';
      header("Location: user.php");
      exit;
    } catch (\PDOException $e) {
      $pesan = ($e->errorInfo[1] == 1062) ? 'Email sudah digunakan oleh user lain.' : 'Gagal memperbarui: ' . $e->getMessage();
      $jenisP = 'error';
    }
  }
  $aksi = '';
}

// ---------- HAPUS ----------
if ($aksi === 'hapus' && isset($_GET['userid'])) {
  $userid = (int) $_GET['userid'];
  $stmt = $pdo->prepare("DELETE FROM user_tbl WHERE userid=?");
  $stmt->execute([$userid]);
  $_SESSION['pesan'] = 'User berhasil dihapus.';
  $_SESSION['jenisP'] = 'sukses';
  header("Location: user.php");
  exit;
}

// ---------- AMBIL DATA EDIT ----------
$dataEdit = null;
if ($aksi === 'form_edit' && isset($_GET['userid'])) {
  $userid = (int) $_GET['userid'];
  $stmt = $pdo->prepare("SELECT * FROM user_tbl WHERE userid=?");
  $stmt->execute([$userid]);
  $dataEdit = $stmt->fetch();
}

// ---------- DROPDOWN PRODI ----------
$prodiList = $pdo->query("SELECT prodi_id, nama_prodi FROM prodi_tbl ORDER BY nama_prodi")->fetchAll();

// ---------- DAFTAR USER ----------
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
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen User — Sistem Akademik</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap"
    rel="stylesheet">

  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --blue-50: #eff6ff;
      --blue-100: #dbeafe;
      --blue-200: #bfdbfe;
      --blue-500: #3b82f6;
      --blue-600: #2563eb;
      --blue-700: #1d4ed8;
      --blue-800: #1e40af;
      --white: #ffffff;
      --gray-50: #f8fafc;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-300: #cbd5e1;
      --gray-400: #94a3b8;
      --gray-500: #64748b;
      --gray-600: #475569;
      --gray-800: #1e293b;
      --success: #059669;
      --success-bg: #d1fae5;
      --success-bd: #6ee7b7;
      --danger: #dc2626;
      --danger-bg: #fee2e2;
      --danger-bd: #fca5a5;
      --warn: #d97706;
      --warn-bg: #fef3c7;
      --radius-sm: 8px;
      --radius: 12px;
      --radius-lg: 16px;
      --shadow: 0 4px 16px rgba(0, 0, 0, .08), 0 2px 6px rgba(0, 0, 0, .04);
      --shadow-lg: 0 10px 40px rgba(37, 99, 235, .12), 0 4px 16px rgba(0, 0, 0, .06);
    }

    @keyframes slideDownAlert {
      from {
        opacity: 0;
        transform: translateY(-100%);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes slideUpAlert {
      from {
        opacity: 1;
        transform: translateY(0);
        max-height: 100px;
      }

      to {
        opacity: 0;
        transform: translateY(-100%);
        max-height: 0;
        padding: 0;
        margin-bottom: 0;
        visibility: hidden;
      }
    }

    .alert {
      padding: .85rem 1.15rem;
      border-radius: var(--radius);
      margin-bottom: 1.5rem;
      font-size: .88rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .65rem;
      font-weight: 500;
      animation: slideDownAlert 0.4s ease-out;
      overflow: hidden;
      transition: all 0.3s ease;
    }

    .alert.hiding {
      animation: slideUpAlert 0.4s ease-out forwards;
    }

    body {
      background: var(--gray-50);
      color: var(--gray-800);
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      padding-bottom: 4rem;
    }

    /* ===== HEADER ===== */
    header {
      background: linear-gradient(135deg, var(--blue-800) 0%, var(--blue-600) 60%, var(--blue-500) 100%);
      padding: 0 2.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 64px;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 20px rgba(37, 99, 235, .3);
    }

    .brand {
      display: flex;
      align-items: center;
      gap: .75rem;
    }

    .brand-icon {
      width: 36px;
      height: 36px;
      background: rgba(255, 255, 255, .2);
      border: 1.5px solid rgba(255, 255, 255, .35);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
    }

    .brand h1 {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--white);
    }

    .brand h1 span {
      color: var(--blue-200);
    }

    .header-badge {
      background: rgba(255, 255, 255, .15);
      border: 1px solid rgba(255, 255, 255, .25);
      color: var(--white);
      font-family: 'DM Mono', monospace;
      font-size: .72rem;
      padding: .3rem .85rem;
      border-radius: 20px;
    }

    .header-badge b {
      color: var(--blue-100);
    }

    /* ===== MAIN ===== */
    main {
      max-width: 1100px;
      margin: 2rem auto;
      padding: 0 1.5rem;
    }

    /* ===== ALERT ===== */
    .alert {
      padding: .85rem 1.15rem;
      border-radius: var(--radius);
      margin-bottom: 1.5rem;
      font-size: .88rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .65rem;
      font-weight: 500;
      animation: fadeSlide .3s ease;
    }

    .alert-left {
      display: flex;
      align-items: center;
      gap: .65rem;
    }

    .alert.sukses {
      background: var(--success-bg);
      border: 1px solid var(--success-bd);
      color: var(--success);
    }

    .alert.error {
      background: var(--danger-bg);
      border: 1px solid var(--danger-bd);
      color: var(--danger);
    }

    .alert-close {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 1rem;
      opacity: .5;
      color: inherit;
      padding: 0 .2rem;
      transition: opacity .15s;
      line-height: 1;
    }

    .alert-close:hover {
      opacity: 1;
    }

    @keyframes fadeSlide {
      from {
        opacity: 0;
        transform: translateY(-6px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* ===== CARD ===== */
    .card {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: var(--radius-lg);
      padding: 1.75rem;
      margin-bottom: 1.5rem;
      box-shadow: var(--shadow);
    }

    .card-header {
      display: flex;
      align-items: center;
      gap: .65rem;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--gray-100);
    }

    .card-header-icon {
      width: 32px;
      height: 32px;
      background: var(--blue-50);
      border: 1px solid var(--blue-100);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .9rem;
    }

    .card-title {
      font-size: .95rem;
      font-weight: 700;
      color: var(--gray-800);
    }

    /* ===== FORM ===== */
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
      gap: 1.1rem;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: .4rem;
    }

    label {
      font-size: .72rem;
      font-weight: 700;
      color: var(--gray-500);
      text-transform: uppercase;
      letter-spacing: .06em;
    }

    input[type=email],
    input[type=password],
    input[type=text],
    select {
      background: var(--gray-50);
      border: 1.5px solid var(--gray-200);
      border-radius: var(--radius-sm);
      color: var(--gray-800);
      padding: .65rem .9rem;
      font-size: .88rem;
      font-family: inherit;
      transition: border-color .2s, box-shadow .2s, background .2s;
      outline: none;
      width: 100%;
    }

    input:focus,
    select:focus {
      border-color: var(--blue-500);
      background: var(--white);
      box-shadow: 0 0 0 3px rgba(59, 130, 246, .15);
    }

    input::placeholder {
      color: var(--gray-300);
    }

    select option {
      background: var(--white);
    }

    .password-note {
      font-size: .68rem;
      color: var(--gray-400);
      margin-top: .2rem;
    }

    .form-actions {
      display: flex;
      gap: .75rem;
      margin-top: 1.5rem;
    }

    /* ===== BUTTONS ===== */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      padding: .6rem 1.25rem;
      border-radius: var(--radius-sm);
      border: none;
      cursor: pointer;
      font-size: .84rem;
      font-weight: 600;
      font-family: inherit;
      transition: all .18s;
      text-decoration: none;
      white-space: nowrap;
    }

    .btn-primary {
      background: var(--blue-600);
      color: var(--white);
      box-shadow: 0 2px 8px rgba(37, 99, 235, .3);
    }

    .btn-primary:hover {
      background: var(--blue-700);
      transform: translateY(-1px);
      box-shadow: 0 4px 14px rgba(37, 99, 235, .35);
    }

    .btn-secondary {
      background: var(--white);
      color: var(--gray-600);
      border: 1.5px solid var(--gray-200);
    }

    .btn-secondary:hover {
      border-color: var(--gray-300);
      color: var(--gray-800);
    }

    .btn-icon {
      padding: .4rem .65rem;
      font-size: .8rem;
    }

    .btn-edit {
      background: var(--warn-bg);
      color: var(--warn);
      border: 1px solid #fcd34d;
    }

    .btn-edit:hover {
      background: #fde68a;
    }

    .btn-del {
      background: var(--danger-bg);
      color: var(--danger);
      border: 1px solid var(--danger-bd);
    }

    .btn-del:hover {
      background: #fecaca;
    }

    /* ===== TOOLBAR ===== */
    .toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
      margin-bottom: 1.25rem;
    }

    .search-wrap {
      position: relative;
      flex: 1;
      min-width: 180px;
      max-width: 320px;
    }

    .search-wrap svg {
      position: absolute;
      left: .75rem;
      top: 50%;
      transform: translateY(-50%);
      pointer-events: none;
      color: var(--gray-400);
    }

    .search-wrap input {
      padding-left: 2.3rem;
    }

    /* ===== TABLE ===== */
    .table-wrap {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: .875rem;
    }

    thead th {
      background: var(--blue-50);
      padding: .75rem 1rem;
      text-align: left;
      font-size: .7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .07em;
      color: var(--blue-700);
      border-bottom: 2px solid var(--blue-100);
    }

    thead th:first-child {
      border-radius: 8px 0 0 0;
    }

    thead th:last-child {
      border-radius: 0 8px 0 0;
    }

    tbody tr {
      border-bottom: 1px solid var(--gray-100);
      transition: background .12s;
    }

    tbody tr:last-child {
      border-bottom: none;
    }

    tbody tr:hover {
      background: var(--blue-50);
    }

    tbody td {
      padding: .85rem 1rem;
      vertical-align: middle;
    }

    .avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--blue-500), var(--blue-700));
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: .78rem;
      color: var(--white);
      flex-shrink: 0;
      box-shadow: 0 2px 8px rgba(37, 99, 235, .25);
    }

    .user-cell {
      display: flex;
      align-items: center;
      gap: .75rem;
    }

    .user-email {
      font-weight: 500;
      color: var(--gray-800);
    }

    .badge {
      display: inline-block;
      padding: .22rem .7rem;
      border-radius: 20px;
      font-size: .7rem;
      font-weight: 700;
    }

    .badge-prodi {
      background: var(--blue-50);
      color: var(--blue-700);
      border: 1px solid var(--blue-200);
    }

    .no-col {
      font-family: 'DM Mono', monospace;
      font-size: .75rem;
      color: var(--gray-400);
    }

    .no-prodi {
      color: var(--gray-300);
      font-size: .8rem;
      font-style: italic;
    }

    .empty-state {
      text-align: center;
      padding: 3.5rem 1rem;
    }

    .empty-icon {
      width: 56px;
      height: 56px;
      background: var(--gray-100);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin: 0 auto 1rem;
    }

    .empty-state p {
      font-size: .88rem;
      color: var(--gray-500);
    }

    /* ===== MODAL ===== */
    .confirm-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, .5);
      backdrop-filter: blur(5px);
      z-index: 999;
      align-items: center;
      justify-content: center;
    }

    .confirm-overlay.show {
      display: flex;
    }

    .confirm-box {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: var(--radius-lg);
      padding: 2rem;
      max-width: 380px;
      width: 90%;
      box-shadow: var(--shadow-lg);
      animation: fadeSlide .2s ease;
      text-align: center;
    }

    .confirm-icon {
      width: 52px;
      height: 52px;
      background: var(--danger-bg);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      margin: 0 auto 1rem;
    }

    .confirm-box h3 {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--gray-800);
      margin-bottom: .5rem;
    }

    .confirm-box p {
      color: var(--gray-500);
      font-size: .85rem;
      margin-bottom: 1.5rem;
      line-height: 1.5;
    }

    .confirm-actions {
      display: flex;
      gap: .75rem;
      justify-content: center;
    }
  </style>
</head>

<body>

  <header>
    <div class="brand">
      <div class="brand-icon">🎓</div>
      <h1>Sistem <span>Akademik</span></h1>
    </div>
    <div class="header-badge">Total user: <b><?= count($users) ?></b></div>
  </header>

  <main>

    <?php if ($pesan): ?>
      <div class="alert <?= $jenisP ?>" id="alertBox">
        <div class="alert-left">
          <?= $jenisP === 'sukses' ? '✅' : '❌' ?>
          <span><?= $pesan ?></span>
        </div>
        <button class="alert-close" onclick="document.getElementById('alertBox').remove()" title="Tutup">✕</button>
      </div>
    <?php endif; ?>

    <!-- FORM TAMBAH / EDIT -->
    <?php
    $isEdit = ($aksi === 'form_edit' && $dataEdit);
    $formAksi = $isEdit ? 'edit' : 'tambah';
    $formJudul = $isEdit ? 'Edit User' : 'Tambah User Baru';
    $formIcon = $isEdit ? '✏️' : ' ';
    ?>
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= $formJudul ?></div>
      </div>
      <form method="POST" action="user.php?aksi=<?= $formAksi ?>">
        <?php if ($isEdit): ?>
          <input type="hidden" name="userid" value="<?= $dataEdit['userid'] ?>">
        <?php endif; ?>
        <div class="form-grid">
          <div class="form-group">
            <label>Alamat Email *</label>
            <input type="email" name="email" placeholder="email@domain.com"
              value="<?= htmlspecialchars($dataEdit['email'] ?? '') ?>" required
              oninvalid="this.setCustomValidity('Email tidak boleh kosong!')" oninput="this.setCustomValidity('')">
          </div>
          <div class="form-group">
            <label>Password <?= $isEdit ? '(kosongkan jika tidak diubah)' : '*' ?></label>
            <input type="password" name="password" placeholder="<?= $isEdit ? '••••••••' : 'Masukkan password' ?>"
              <?= !$isEdit ? 'required' : '' ?> oninvalid="this.setCustomValidity('Password wajib diisi!')"
              oninput="this.setCustomValidity('')">
            <?php if ($isEdit): ?>
              <div class="password-note">Biarkan kosong jika tidak ingin mengubah password</div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label>Program Studi</label>
            <select name="prodi_id">
              <option value="">— Pilih Program Studi —</option>
              <?php foreach ($prodiList as $prodi): ?>
                <option value="<?= $prodi['prodi_id'] ?>" <?= (($dataEdit['prodi_id'] ?? '') == $prodi['prodi_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($prodi['nama_prodi']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            <?= $isEdit ? ' Simpan Perubahan' : ' Tambah User' ?>
          </button>
          <?php if ($isEdit): ?>
            <a href="user.php" class="btn btn-secondary">✖ Batal</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- TABEL DAFTAR USER -->
    <div class="card">
      <div class="toolbar">
        <div class="card-header" style="margin-bottom:0;border:none;padding:0;">
          <div class="card-header-icon">👥</div>
          <div class="card-title">Daftar User</div>
        </div>
        <form method="GET" action="user.php" style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;">
          <div class="search-wrap">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
              stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.35-4.35" />
            </svg>
            <input type="text" name="q" placeholder="Cari email / prodi…" value="<?= htmlspecialchars($search) ?>">
          </div>
          <button type="submit" class="btn btn-secondary">Cari</button>
          <?php if ($search): ?>
            <a href="user.php" class="btn btn-secondary">Reset</a>
          <?php endif; ?>
        </form>
      </div>

      <div class="table-wrap">
        <?php if (empty($users)): ?>
          <div class="empty-state">
            <div class="empty-icon">👤</div>
            <p><?= $search
              ? "Tidak ada user yang cocok dengan &ldquo;<b>$search</b>&rdquo;."
              : 'Belum ada user. Tambahkan user pertama di atas.' ?></p>
          </div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th style="width:60px">No</th>
                <th>Email</th>
                <th>Program Studi</th>
                <th style="width:110px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $no => $u): ?>
                <tr>
                  <td class="no-col"><?= str_pad($no + 1, 3, '0', STR_PAD_LEFT) ?></td>
                  <td>
                    <div class="user-cell">
                      <div class="avatar"><?= $no + 1 ?></div>
                      <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
                    </div>
                  </td>
                  <td>
                    <?php if ($u['nama_prodi']): ?>
                      <span class="badge badge-prodi">🎓 <?= htmlspecialchars($u['nama_prodi']) ?></span>
                    <?php else: ?>
                      <span class="no-prodi">Tidak ada prodi</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="display:flex;gap:.4rem;">
                      <a href="user.php?aksi=form_edit&userid=<?= $u['userid'] ?>" class="btn btn-icon btn-edit"
                        title="Edit">✏️</a>
                      <button class="btn btn-icon btn-del" title="Hapus"
                        onclick="konfirmHapus(<?= $u['userid'] ?>, '<?= htmlspecialchars(addslashes($u['email'])) ?>')">
                        🗑️
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

  </main>

  <!-- MODAL KONFIRMASI HAPUS -->
  <div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
      <div class="confirm-icon">🗑️</div>
      <h3>Hapus User?</h3>
      <p id="confirmText">Apakah Anda yakin ingin menghapus user ini?</p>
      <div class="confirm-actions">
        <a id="confirmBtn" href="#" class="btn btn-del">Ya, Hapus</a>
        <button class="btn btn-secondary" onclick="tutupKonfirmasi()">Batal</button>
      </div>
    </div>
  </div>

  <script>
    // === AUTO HIDE NOTIFICATION (muncul dari atas, hilang ke atas) ===
    document.addEventListener('DOMContentLoaded', function () {
      const alertBox = document.getElementById('alertBox');
      if (alertBox) {
        // Auto hide after 3 seconds
        setTimeout(function () {

          alertBox.classList.add('hiding');

          setTimeout(function () {
            if (alertBox && alertBox.remove) {
              alertBox.remove();
            }
          }, 400);
        }, 3000);
      }
    });

    // Fungsi tutup manual dengan animasi
    function tutupAlertManually(button) {
      const alertBox = button.closest('.alert');
      if (alertBox) {
        alertBox.classList.add('hiding');
        setTimeout(function () {
          if (alertBox && alertBox.remove) {
            alertBox.remove();
          }
        }, 400);
      }
    }

    function konfirmHapus(userid, email) {
      document.getElementById('confirmText').innerHTML =
        'Hapus user dengan email <b>' + email + '</b>?<br>' +
        '<small style="color:#94a3b8">Tindakan ini tidak dapat dibatalkan.</small>';
      document.getElementById('confirmBtn').href = 'user.php?aksi=hapus&userid=' + userid;
      document.getElementById('confirmOverlay').classList.add('show');
    }

    function tutupKonfirmasi() {
      document.getElementById('confirmOverlay').classList.remove('show');
    }

    const overlay = document.getElementById('confirmOverlay');
    if (overlay) {
      overlay.addEventListener('click', function (e) {
        if (e.target === this) tutupKonfirmasi();
      });
    }

    document.addEventListener('DOMContentLoaded', function () {
      const closeButtons = document.querySelectorAll('.alert-close');
      closeButtons.forEach(btn => {
        btn.onclick = function () {
          tutupAlertManually(this);
        };
      });
    });
  </script>
</body>

</html>
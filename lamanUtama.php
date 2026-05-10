<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}

$host    = 'localhost';
$db      = 'pemweb_db';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (\PDOException $e) {
  die('Koneksi database gagal: ' . $e->getMessage());
}

if (isset($_GET['logout'])) {
  session_destroy();
  header("Location: index.php");
  exit;
}

$userLogin = null;
try {
  $s = $pdo->prepare("SELECT u.*, p.nama_prodi FROM user_tbl u LEFT JOIN prodi_tbl p ON u.prodi_id=p.prodi_id WHERE u.userid=?");
  $s->execute([$_SESSION['user_id']]);
  $userLogin = $s->fetch();
} catch (\Exception $e) {}

$emailLogin = $userLogin['email']      ?? ($_SESSION['email'] ?? 'User');
$prodiLogin = $userLogin['nama_prodi'] ?? null;
$initial    = strtoupper(substr($emailLogin, 0, 1));
$halaman    = basename($_SERVER['PHP_SELF']);

$menus = [
  ['file' => 'dashboard.php',     'label' => 'Dashboard',     'icon' => 'ti-layout-dashboard'],
  ['file' => 'program_studi.php', 'label' => 'Program Studi', 'icon' => 'ti-building-community'],
  ['file' => 'user.php',          'label' => 'User',          'icon' => 'ti-users'],
  ['file' => 'profile.php',       'label' => 'Profile',       'icon' => 'ti-user-circle'],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistem Akademik</title>
  <!-- Tabler CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
  <!-- Tabler Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/tabler-icons.min.css">
  <style>
    /* Biar footer selalu di bawah */
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
    }
    .wrapper {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    .page-wrapper {
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    .page-body {
      flex: 1;
    }
    footer {
      margin-top: auto;
    }
  </style>
</head>

<body class="antialiased">
  <div class="wrapper">

    <!-- Navbar — sama persis dengan dashboard.php -->
    <header class="navbar navbar-expand-md navbar-dark bg-blue sticky-top d-print-none">
      <div class="container-xl">

        <a href="lamanUtama.php" class="navbar-brand d-flex align-items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-school" width="28" height="28"
            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
            stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M22 9l-10 -4l-10 4l10 4l10 -4v6" />
            <path d="M6 10.6v5.4a6 3 0 0 0 12 0v-5.4" />
          </svg>
          <span class="fw-bold" style="font-size:1rem;">Sistem Akademik</span>
        </a>

        <!-- Toggle mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">
          <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
            <nav class="nav">
              <?php foreach ($menus as $m): ?>
                <a class="nav-link text-white <?= $halaman === $m['file'] ? 'fw-bold' : '' ?>"
                   href="<?= $m['file'] ?>">
                  <i class="ti <?= $m['icon'] ?> me-1"></i><?= $m['label'] ?>
                </a>
              <?php endforeach; ?>
            </nav>

            <!-- User dropdown -->
            <div class="dropdown ms-2">
              <a href="#" class="d-flex align-items-center gap-2 text-white text-decoration-none"
                 data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar avatar-sm rounded-circle bg-white text-blue fw-bold">
                  <?= $initial ?>
                </span>
                <div class="d-none d-md-block lh-sm">
                  <div style="font-size:.8rem;font-weight:600;"><?= htmlspecialchars($emailLogin) ?></div>
                  <div style="font-size:.68rem;opacity:.75;"><?= htmlspecialchars($prodiLogin ?? 'Tidak ada prodi') ?></div>
                </div>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <a href="profile.php" class="dropdown-item d-flex align-items-center gap-2">
                    <i class="ti ti-user-circle"></i> Profile
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <a href="lamanUtama.php?logout=1" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                    <i class="ti ti-logout"></i> Logout
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>

      </div>
    </header>

    <!-- Page content -->
    <div class="page-wrapper">
      <div class="page-body">
        <div class="container-xl">
        </div>
      </div>

      <!-- Footer dengan background biru, POSISI PALING BAWAH -->
      <footer class="d-print-none text-white text-center py-3" style="background-color: #0054a6 !important;">
        <div class="container-xl">
          <div>Sistem Akademik &copy; <?= date('Y') ?></div>
          <div>Built with Tabler (https://tabler.io)</div>
          <div>License: MIT</div>
        </div>
      </footer>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
</body>

</html>

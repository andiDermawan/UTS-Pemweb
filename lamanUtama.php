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
} catch (\Exception $e) {
}

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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/tabler-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
  <style>
    :root {
      --blue: #7BB8D4;
      --blue-dark: #5A9CB8;
      --blue-deeper: #3D7F9E;
      --blue-light: #C8E4F0;
      --blue-xl: #EBF5FA;
      --cream: #FAF6EE;
      --cream-d: #F0E9DA;
      --cream-bd: #E2D5BC;
      --white: #FFFFFF;
      --txt: #2C3A46;
      --txt-mid: #5A6A7A;
      --txt-lt: #94A8B8;
      --radius: 14px;
      --radius-sm: 9px;
      --shadow: 0 4px 24px rgba(91, 156, 184, .14);
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html,
    body {
      height: 100%;
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--cream);
      color: var(--txt);
    }

    .ak-nav {
      background: var(--white);
      border-bottom: 2px solid var(--blue-light);
      box-shadow: 0 2px 18px rgba(123, 184, 212, .13);
      position: sticky;
      top: 0;
      z-index: 300;
      height: 66px;
      display: flex;
      align-items: center;
      padding: 0 2rem;
    }

    .ak-nav-inner {
      max-width: 1280px;
      width: 100%;
      margin: 0 auto;
      display: flex;
      align-items: center;
      gap: .5rem;
    }

    .ak-brand {
      display: flex;
      align-items: center;
      gap: .65rem;
      text-decoration: none !important;
      margin-right: 1.75rem;
      flex-shrink: 0;
    }

    .ak-brand:hover,
    .ak-brand:focus {
      text-decoration: none !important;
    }

    .ak-brand-icon {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, var(--blue), var(--blue-deeper));
      border-radius: 11px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 3px 12px rgba(91, 156, 184, .32);
      flex-shrink: 0;
    }

    .ak-brand-icon i {
      color: #fff;
      font-size: 1.15rem;
    }

    .ak-brand-text {
      font-family: 'DM Serif Display', serif;
      font-size: 1.05rem;
      color: var(--blue-deeper);
      line-height: 1.15;
    }

    .ak-brand-text small {
      display: block;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .6rem;
      font-weight: 600;
      color: var(--txt-lt);
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .ak-menu {
      display: flex;
      align-items: center;
      gap: .15rem;
      flex: 1;
    }

    .ak-link {
      display: flex;
      align-items: center;
      gap: .4rem;
      padding: .48rem 1rem;
      border-radius: var(--radius-sm);
      text-decoration: none !important;
      font-size: .82rem;
      font-weight: 500;
      color: var(--txt-mid);
      transition: all .18s ease;
      white-space: nowrap;
      position: relative;
    }

    .ak-link i {
      font-size: 1rem;
    }

    .ak-link:hover {
      background: var(--blue-xl);
      color: var(--blue-dark);
      text-decoration: none !important;
    }

    .ak-link.active {
      background: linear-gradient(135deg, var(--blue-xl), var(--cream));
      color: var(--blue-deeper);
      font-weight: 700;
      box-shadow: inset 0 0 0 1.5px var(--blue-light);
    }

    .ak-link.active::after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 50%;
      transform: translateX(-50%);
      width: 22px;
      height: 3px;
      background: var(--blue);
      border-radius: 99px;
    }

    .ak-user {
      margin-left: auto;
      flex-shrink: 0;
    }

    .ak-user-btn {
      display: flex;
      align-items: center;
      gap: .55rem;
      padding: .3rem .8rem .3rem .35rem;
      border-radius: 99px;
      background: var(--blue-xl);
      border: 1.5px solid var(--blue-light);
      cursor: pointer;
      text-decoration: none !important;
      transition: none;
    }

    .ak-user-btn:hover,
    .ak-user-btn:focus {
      text-decoration: none !important;
      background: var(--blue-xl);
      border-color: var(--blue-light);
    }

    .ak-avatar {
      width: 33px;
      height: 33px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--blue), var(--blue-deeper));
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: .78rem;
      color: #fff;
      box-shadow: 0 2px 8px rgba(91, 156, 184, .3);
      flex-shrink: 0;
    }

    .ak-uname {
      font-size: .78rem;
      font-weight: 600;
      color: var(--txt);
      max-width: 150px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      line-height: 1.3;
    }

    .ak-uprodi {
      font-size: .64rem;
      color: var(--txt-lt);
      max-width: 150px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .dropdown-menu {
      border: 1.5px solid var(--blue-light) !important;
      border-radius: var(--radius) !important;
      box-shadow: var(--shadow) !important;
      padding: .45rem !important;
      min-width: 175px;
    }

    .dropdown-item {
      border-radius: var(--radius-sm) !important;
      font-size: .82rem !important;
      font-weight: 500 !important;
      color: var(--txt-mid) !important;
      padding: .5rem .85rem !important;
      transition: background .14s !important;
      text-decoration: none !important;
    }

    .dropdown-item:hover {
      background: var(--blue-xl) !important;
      color: var(--blue-deeper) !important;
    }

    .dropdown-item.logout {
      color: #d94f4f !important;
    }

    .dropdown-item.logout:hover {
      background: #fef2f2 !important;
      color: #b91c1c !important;
    }

    .dropdown-divider {
      border-color: var(--cream-bd) !important;
      margin: .3rem 0 !important;
    }

    .ak-toggle {
      display: none;
      align-items: center;
      justify-content: center;
      width: 38px;
      height: 38px;
      border: 1.5px solid var(--blue-light);
      border-radius: var(--radius-sm);
      background: var(--blue-xl);
      cursor: pointer;
      color: var(--blue-dark);
      font-size: 1.1rem;
      margin-left: auto;
      transition: background .15s;
    }

    .ak-toggle:hover {
      background: var(--blue-light);
    }

    .ak-mobile {
      display: none;
      background: var(--white);
      border-bottom: 2px solid var(--blue-light);
      box-shadow: 0 6px 20px rgba(91, 156, 184, .12);
      padding: .6rem 1.25rem .85rem;
      position: sticky;
      top: 66px;
      z-index: 299;
    }

    .ak-mobile.open {
      display: block;
    }

    .ak-mobile .ak-link {
      padding: .6rem 1rem;
      display: flex;
      width: 100%;
    }

    .ak-mobile .ak-link.active::after {
      display: none;
    }

    .ak-mobile-divider {
      height: 1px;
      background: var(--cream-bd);
      margin: .4rem 0;
    }

    .ak-mobile .logout-link {
      color: #d94f4f !important;
    }

    .ak-mobile .logout-link:hover {
      background: #fef2f2 !important;
      color: #b91c1c !important;
    }

    .ak-mobile-user {
      display: flex;
      align-items: center;
      gap: .65rem;
      padding: .6rem .5rem .75rem;
      border-bottom: 1px solid var(--cream-bd);
      margin-bottom: .4rem;
    }

    .ak-mobile-user .ak-avatar {
      width: 36px;
      height: 36px;
      font-size: .82rem;
    }

    .ak-mobile-uname {
      font-size: .83rem;
      font-weight: 600;
      color: var(--txt);
    }

    .ak-mobile-uprodi {
      font-size: .68rem;
      color: var(--txt-lt);
    }

    @media (max-width: 820px) {

      .ak-menu,
      .ak-user {
        display: none;
      }

      .ak-toggle {
        display: flex;
      }
    }

    .ak-page {
      max-width: 1280px;
      margin: 0 auto;
      padding: 2rem 1.75rem;
    }

    @media (max-width: 600px) {
      .ak-page {
        padding: 1.25rem 1rem;
      }
    }
    footer {
    font-family: 'Times New Roman', Times, serif !important;
    font-size: 25px;
    background-color: rgb(96, 128, 255);
    color: white;
    text-align: center;
    padding: 10px;
    position: fixed;
    left: 0;
    bottom: 0;
    width: 100%;
}

footer * {
    font-family: 'Times New Roman', Times, serif !important;
    font-size: inherit;
}
  </style>
</head>

<body>

  <nav class="ak-nav">
    <div class="ak-nav-inner">

      <a href="lamanUtama.php" class="ak-brand">
        <div class="ak-brand-icon"><i class="ti ti-school"></i></div>
        <div class="ak-brand-text">
          Sistem Akademik
          <small>Management System</small>
        </div>
      </a>

      <div class="ak-menu">
        <?php foreach ($menus as $m): ?>
          <a href="<?= $m['file'] ?>" class="ak-link <?= $halaman === $m['file'] ? 'active' : '' ?>">
            <i class="ti <?= $m['icon'] ?>"></i><?= $m['label'] ?>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="ak-user dropdown">
        <a href="#" class="ak-user-btn" data-bs-toggle="dropdown" aria-expanded="false">
          <div class="ak-avatar"><?= $initial ?></div>
          <div class="d-none d-md-block">
            <div class="ak-uname"><?= htmlspecialchars($emailLogin) ?></div>
            <div class="ak-uprodi"><?= htmlspecialchars($prodiLogin ?? 'Tidak ada prodi') ?></div>
          </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
           <li>
            <a href="lamanUtama.php?logout=1" class="dropdown-item logout d-flex align-items-center gap-2">
              <i class="ti ti-logout"></i> Logout
            </a>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>
          <li>
            <a href="lamanUtama.php?logout=1" class="dropdown-item logout d-flex align-items-center gap-2">
              <i class="ti ti-logout"></i> Logout
            </a>
          </li>
        </ul>
      </div>

      <button class="ak-toggle" onclick="akToggle()" aria-label="Toggle menu">
        <i class="ti ti-menu-2" id="akToggleIcon"></i>
      </button>

    </div>
  </nav>

  <div class="ak-mobile" id="akMobileMenu">
    <div class="ak-mobile-user">
      <div class="ak-avatar"><?= $initial ?></div>
      <div>
        <div class="ak-mobile-uname"><?= htmlspecialchars($emailLogin) ?></div>
        <div class="ak-mobile-uprodi"><?= htmlspecialchars($prodiLogin ?? 'Tidak ada prodi') ?></div>
      </div>
    </div>
    <?php foreach ($menus as $m): ?>
      <a href="<?= $m['file'] ?>" class="ak-link <?= $halaman === $m['file'] ? 'active' : '' ?>">
        <i class="ti <?= $m['icon'] ?>"></i><?= $m['label'] ?>
      </a>
    <?php endforeach; ?>
    <div class="ak-mobile-divider"></div>
    <a href="lamanUtama.php?logout=1" class="ak-link logout-link">
      <i class="ti ti-logout"></i> Logout
    </a>
  </div>

  <div class="ak-page">

  </div>

  <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
  <script>
    function akToggle() {
      const menu = document.getElementById('akMobileMenu');
      const icon = document.getElementById('akToggleIcon');
      const isOpen = menu.classList.toggle('open');
      icon.className = isOpen ? 'ti ti-x' : 'ti ti-menu-2';
    }
  </script>

<footer>
        <a>&copy; Sistem Akademik 2026</a>
        <br>
        <a>cr: tabler</a>
</footer>

</body>

</html>
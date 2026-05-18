<?php
// Sertakan autentikasi & konfigurasi
require_once 'auth.php';
require_once 'config.php';

// Ambil koneksi database
$pdo = getDBConnection();

$pesan = '';
$jenisP = '';
if (!empty($_SESSION['pesan'])) {
  $pesan = $_SESSION['pesan'];
  $jenisP = $_SESSION['jenisP'];
  unset($_SESSION['pesan'], $_SESSION['jenisP']);
}

$aksi = $_REQUEST['aksi'] ?? '';

// Helper: Generate UUID v4
function generateUUID() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// ---------- TAMBAH ----------
if ($aksi === 'tambah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $prodi_id = !empty($_POST['prodi_id']) ? (int) $_POST['prodi_id'] : null;

  if ($email === '' || $password === '') {
    $pesan = 'Email dan Password wajib diisi.';
    $jenisP = 'error';
  } elseif ($prodi_id === null) {
    $pesan = 'Program Studi wajib dipilih.';
    $jenisP = 'error';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $pesan = 'Format email tidak valid.';
    $jenisP = 'error';
  } elseif (strlen($password) < 8) {
    $pesan = 'Password minimal 8 karakter.';
    $jenisP = 'error';
  } else {
    try {
      $uuid = generateUUID();
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("INSERT INTO user_tbl (userid, email, password, prodi_id) VALUES (?, ?, ?, ?)");
      $stmt->execute([$uuid, $email, $hash, $prodi_id]);
      $_SESSION['pesan'] = "User <strong>$email</strong> berhasil ditambahkan.";
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
  $userid = trim($_POST['userid'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $prodi_id = !empty($_POST['prodi_id']) ? (int) $_POST['prodi_id'] : null;

  if ($email === '') {
    $pesan = 'Email wajib diisi.';
    $jenisP = 'error';
  } elseif ($prodi_id === null) {
    $pesan = 'Program Studi wajib dipilih.';
    $jenisP = 'error';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $pesan = 'Format email tidak valid.';
    $jenisP = 'error';
  } else {
    try {
      // Disallow password changes from the user management page. Password must be changed in Profile.
      if (isset($_POST['password']) && $_POST['password'] !== '') {
        $pesan = 'Password tidak dapat diubah dari halaman User. Gunakan halaman Profile.';
        $jenisP = 'error';
      } else {
        $stmt = $pdo->prepare("UPDATE user_tbl SET email=?, prodi_id=? WHERE userid=?");
        $stmt->execute([$email, $prodi_id, $userid]);

        $_SESSION['pesan'] = "User <strong>$email</strong> berhasil diperbarui.";
        $_SESSION['jenisP'] = 'sukses';
        header("Location: user.php");
        exit;
      }
    } catch (\PDOException $e) {
      $pesan = ($e->errorInfo[1] == 1062) ? 'Email sudah digunakan oleh user lain.' : 'Gagal memperbarui: ' . $e->getMessage();
      $jenisP = 'error';
    }
  }
}

// ---------- HAPUS ----------
if ($aksi === 'hapus' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['userid'])) {
  $userid = trim($_POST['userid']);
  if (isset($_SESSION['user_id']) && $userid === $_SESSION['user_id']) {
    $_SESSION['pesan'] = 'Akun yang sedang login tidak dapat dihapus dari halaman User.';
    $_SESSION['jenisP'] = 'error';
    header("Location: user.php");
    exit;
  }

  $stmt = $pdo->prepare("DELETE FROM user_tbl WHERE userid=?");
  $stmt->execute([$userid]);
  $_SESSION['pesan'] = 'User berhasil dihapus.';
  $_SESSION['jenisP'] = 'sukses';
  header("Location: user.php");
  exit;
}

$prodiList = $pdo->query("SELECT prodi_id, nama_prodi FROM prodi_tbl ORDER BY nama_prodi")->fetchAll();

// Search & Sort
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'email_asc';
$orderClause = match ($sort) {
  'email_desc' => 'u.email DESC',
  'prodi_asc' => 'p.nama_prodi ASC, u.email ASC',
  'prodi_desc' => 'p.nama_prodi DESC, u.email ASC',
  default => 'u.email ASC',
};

if ($search !== '') {
  $stmt = $pdo->prepare("
    SELECT u.*, p.nama_prodi
    FROM user_tbl u
    LEFT JOIN prodi_tbl p ON u.prodi_id = p.prodi_id
    WHERE u.email LIKE ? OR p.nama_prodi LIKE ?
    ORDER BY $orderClause
  ");
  $stmt->execute(["%$search%", "%$search%"]);
} else {
  $stmt = $pdo->query("
    SELECT u.*, p.nama_prodi
    FROM user_tbl u
    LEFT JOIN prodi_tbl p ON u.prodi_id = p.prodi_id
    ORDER BY $orderClause
  ");
}
$users = $stmt->fetchAll();

$currentPage = 'user';
$defaultProfileImg = 'images/profile.png';
$hasDefaultProfileImg = is_file(__DIR__ . '/images/profile.png');

// Helper for sort URL
function sortUrl($field, $currentSort, $search)
{
  $dir = ($currentSort === $field . '_asc') ? $field . '_desc' : $field . '_asc';
  $params = ['sort' => $dir];
  if ($search !== '')
    $params['q'] = $search;
  return 'user.php?' . http_build_query($params);
}
function sortIcon($field, $currentSort)
{
  if ($currentSort === $field . '_asc')
    return '<i class="ti ti-sort-ascending ms-1"></i>';
  if ($currentSort === $field . '_desc')
    return '<i class="ti ti-sort-descending ms-1"></i>';
  return '<i class="ti ti-arrows-sort ms-1 text-muted"></i>';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen User — Sistem Akademik</title>
  <link rel="stylesheet" href="css/tabler.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/tabler-icons.min.css">
  <style>
    html,
    body {
      height: 100%;
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

    .avatar-initials {
      font-size: .75rem;
      font-weight: 700;
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

    th a {
      color: inherit;
      text-decoration: none;
      white-space: nowrap;
    }

    th a:hover {
      color: #206bc4;
    }
  </style>
</head>

<body class="antialiased">
  <div class="wrapper">
    <nav class="navbar navbar-dark bg-blue sticky-top d-print-none">
      <div class="container-xl">
        <a href="dashboard.php" class="navbar-brand d-flex align-items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-school" width="28" height="28"
            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
            stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M22 9l-10 -4l-10 4l10 4l10 -4v6" />
            <path d="M6 10.6v5.4a6 3 0 0 0 12 0v-5.4" />
          </svg>
          <span class="navbar-brand-text">Sistem Akademik</span>
        </a>
        <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse"
          data-bs-target="#navbarToggleExternalContent"><i class="ti ti-menu-2"></i></button>
        <div class="d-none d-md-flex ms-auto">
          <ul class="navbar-nav flex-row align-items-center gap-2">
            <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i
                  class="ti ti-home me-1"></i>Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="profile.php"><i
                  class="ti ti-user me-1"></i>Profile</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="program_studi.php"><i
                  class="ti ti-building-community me-1"></i>Program Studi</a></li>
            <li class="nav-item"><a class="nav-link text-white active" href="user.php"><i
                  class="ti ti-users me-1"></i>User</a></li>
            <li class="nav-item"><a href="logout.php" class="btn btn-light ms-2"><i
                  class="ti ti-logout me-1"></i>Logout</a></li>
          </ul>
        </div>
      </div>
    </nav>
    <div class="collapse d-md-none" id="navbarToggleExternalContent">
      <div class="bg-blue p-3">
        <div class="d-grid gap-1">
          <a class="btn btn-link text-white text-start w-100 border-bottom m-0" href="dashboard.php"><i
              class="ti ti-home me-1"></i>Dashboard</a>
          <a class="btn btn-link text-white text-start w-100 border-bottom m-0" href="profile.php"><i
              class="ti ti-user me-1"></i>Profile</a>
          <a class="btn btn-link text-white text-start w-100 border-bottom m-0" href="program_studi.php"><i
              class="ti ti-building-community me-1"></i>Program Studi</a>
          <a class="btn btn-link text-white text-start w-100 border-bottom m-0 active" href="user.php"><i
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
              <h2 class="page-title">Manajemen User</h2>
            </div>
            <div class="col-auto">
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="ti ti-user-plus me-1"></i>Tambah User
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="page-body">
        <div class="container-xl">
          <?php if ($pesan): ?>
            <div class="alert alert-<?= $jenisP === 'sukses' ? 'success' : 'danger' ?> alert-dismissible mb-4"
              role="alert" id="flashAlert">
              <div class="d-flex align-items-center gap-2">
                <i class="ti ti-<?= $jenisP === 'sukses' ? 'circle-check' : 'alert-circle' ?> icon-lg"></i>
                <div><?= $pesan ?></div>
              </div>
              <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
            </div>
          <?php endif; ?>

          <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
              <h3 class="card-title mb-0"><i class="ti ti-users me-2 text-blue"></i>Daftar User</h3>
              <form method="GET" action="user.php" class="d-flex align-items-center gap-2 flex-wrap">
                <?php if ($sort !== 'email_asc'): ?><input type="hidden" name="sort"
                    value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
                <div class="input-group input-group-sm" style="min-width:220px">
                  <input type="text" name="q" class="form-control" placeholder="Cari email / prodi…"
                    value="<?= htmlspecialchars($search) ?>">
                  <button type="submit" class="btn btn-sm btn-secondary px-2"><i
                      class="ti ti-search me-1"></i>Cari</button>
                </div>
                <?php if ($search): ?>
                  <a href="user.php" class="btn btn-sm btn-ghost-secondary"><i class="ti ti-x me-1"></i>Reset</a>
                <?php endif; ?>
              </form>
            </div>

            <?php if (empty($users)): ?>
              <div class="card-body">
                <div class="empty">
                  <div class="empty-img"><i class="ti ti-user-off" style="font-size:4rem;color:#94a3b8"></i></div>
                  <p class="empty-title">Tidak ada user ditemukan</p>
                  <p class="empty-subtitle text-muted">
                    <?= $search
                      ? "Tidak ada hasil untuk &ldquo;<strong>" . htmlspecialchars($search) . "</strong>&rdquo;. Coba kata kunci lain."
                      : 'Belum ada user. Tambahkan user pertama.' ?>
                  </p>
                  <?php if ($search): ?>
                    <div class="empty-action"><a href="user.php" class="btn btn-primary"><i
                          class="ti ti-arrow-left me-1"></i>Kembali</a></div>
                  <?php endif; ?>
                </div>
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-vcenter table-hover table-striped card-table">
                  <thead>
                    <tr>
                      <th class="w-1 text-center">No</th>
                      <th><a href="<?= sortUrl('email', $sort, $search) ?>">Email<?= sortIcon('email', $sort) ?></a></th>
                      <th><a href="<?= sortUrl('prodi', $sort, $search) ?>">Program
                          Studi<?= sortIcon('prodi', $sort) ?></a></th>
                      <th class="w-1">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($users as $no => $u):
                      $colors = ['bg-blue', 'bg-azure', 'bg-indigo', 'bg-purple', 'bg-pink', 'bg-red', 'bg-orange', 'bg-teal', 'bg-green', 'bg-cyan'];
                      $avatarColor = $colors[$no % count($colors)];
                      $initial = strtoupper(substr($u['email'], 0, 1));
                      $isCurrentUser = isset($_SESSION['user_id']) && $u['userid'] === $_SESSION['user_id'];
                      $profileImgPath = '';
                      $hasProfileImg = !empty($u['foto_profil']) && is_file(__DIR__ . '/uploads/profiles/' . $u['foto_profil']);
                      if ($hasProfileImg) {
                        $profileImgPath = 'uploads/profiles/' . $u['foto_profil'];
                      }
                      ?>
                      <tr>
                        <td class="text-center text-muted"><small><?= str_pad($no + 1, 3, '0', STR_PAD_LEFT) ?></small></td>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <?php if ($hasProfileImg): ?>
                              <img src="<?= htmlspecialchars($profileImgPath) ?>" alt="Profile"
                                class="avatar avatar-sm rounded-circle" style="width:32px;height:32px;object-fit:cover;">
                            <?php elseif ($hasDefaultProfileImg): ?>
                              <img src="<?= htmlspecialchars($defaultProfileImg) ?>" alt="Profile"
                                class="avatar avatar-sm rounded-circle" style="width:32px;height:32px;object-fit:cover;">
                            <?php else: ?>
                              <span
                                class="avatar avatar-sm <?= $avatarColor ?> text-white avatar-initials rounded-circle"><?= $initial ?></span>
                            <?php endif; ?>
                            <span class="text-body fw-medium"><?= htmlspecialchars($u['email']) ?></span>
                          </div>
                        </td>
                        <td>
                          <?php if ($u['nama_prodi']): ?>
                            <span class="badge bg-blue-lt text-blue"><i
                                class="ti ti-school me-1"></i><?= htmlspecialchars($u['nama_prodi']) ?></span>
                          <?php else: ?>
                            <span class="text-muted fst-italic small"><i class="ti ti-minus me-1"></i>Belum ada prodi</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div class="btn-list flex-nowrap">
                            <button class="btn btn-sm btn-warning" title="Edit"
                              onclick='setEditUser(<?= json_encode($u['userid']) ?>, <?= json_encode($u['email']) ?>, <?= json_encode($u['prodi_id'] ?? '') ?>)'>
                              <i class="ti ti-edit me-1"></i>Edit
                            </button>
                            <button class="btn btn-sm btn-danger" title="Hapus"
                              <?= $isCurrentUser ? 'disabled aria-disabled="true" title="Akun yang sedang login tidak dapat dihapus"' : '' ?>
                              onclick='konfirmHapus(<?= json_encode($u['userid']) ?>, <?= json_encode($u['email']) ?>)'>
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

      <footer class="footer d-print-none bg-blue text-white border-top border-blue py-2"
        style="min-height:auto;margin-top:auto;">
        <div class="container-xl d-flex align-items-center justify-content-center" style="min-height:50px;">
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

  <!-- Modal Tambah User -->
  <div class="modal modal-blur fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="ti ti-user-plus me-2 text-blue"></i>Tambah User Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="user.php?aksi=tambah">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label required">Alamat Email</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ti ti-mail"></i></span>
                <input type="email" name="email" class="form-control" placeholder="email@domain.com" required>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label required">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ti ti-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter" required
                  minlength="8">
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label required">Program Studi</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ti ti-building-community"></i></span>
                <select name="prodi_id" class="form-select" required>
                  <option value="">— Pilih Program Studi —</option>
                  <?php foreach ($prodiList as $prodi): ?>
                    <option value="<?= $prodi['prodi_id'] ?>"><?= htmlspecialchars($prodi['nama_prodi']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i
                class="ti ti-x me-1"></i>Batal</button>
            <button type="submit" class="btn btn-primary"><i class="ti ti-user-plus me-1"></i>Tambah User</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Edit User -->
  <div class="modal modal-blur fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="ti ti-edit me-2 text-warning"></i>Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="user.php?aksi=edit">
          <div class="modal-body">
            <input type="hidden" name="userid" id="editUserId" value="">
            <div class="mb-3">
              <label class="form-label required">Alamat Email</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ti ti-mail"></i></span>
                <input type="email" name="email" id="editUserEmail" class="form-control" placeholder="email@domain.com"
                  required>
              </div>
            </div>
            <div class="mb-3">
              <div class="form-text text-muted small">Password hanya dapat diubah melalui halaman Profile oleh pemilik akun.</div>
            </div>
            <div class="mb-3">
              <label class="form-label required">Program Studi</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ti ti-building-community"></i></span>
                <select name="prodi_id" id="editUserProdi" class="form-select" required>
                  <option value="">— Pilih Program Studi —</option>
                  <?php foreach ($prodiList as $prodi): ?>
                    <option value="<?= $prodi['prodi_id'] ?>"><?= htmlspecialchars($prodi['nama_prodi']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i
                class="ti ti-x me-1"></i>Batal</button>
            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan
              Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Konfirmasi Hapus -->
  <div class="modal modal-blur fade" id="confirmModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-body">
          <div class="modal-title text-center mb-2">
            <div class="text-danger mb-3"><i class="ti ti-trash" style="font-size:2.5rem"></i></div>
            <h3>Hapus User?</h3>
          </div>
          <p class="text-muted text-center" id="confirmText">Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="modal-footer justify-content-center">
          <form method="POST" action="user.php?aksi=hapus" id="deleteForm" class="w-100">
            <input type="hidden" name="userid" id="deleteUserId" value="">
            <button type="submit" class="btn btn-danger w-100"><i class="ti ti-trash me-1"></i>Ya, Hapus</button>
          </form>
          <button type="button" class="btn w-100 mt-2" data-bs-dismiss="modal">Batal</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var flashAlert = document.getElementById('flashAlert');
      if (flashAlert) {
        setTimeout(function () {
          var bsAlert = bootstrap.Alert.getOrCreateInstance(flashAlert);
          bsAlert.close();
        }, 4000);
      }
    });

    function setEditUser(userid, email, prodiId) {
      document.getElementById('editUserId').value = userid;
      document.getElementById('editUserEmail').value = email;
      document.getElementById('editUserProdi').value = prodiId || '';
      var modal = new bootstrap.Modal(document.getElementById('editModal'));
      modal.show();
    }

    function konfirmHapus(userid, email) {
      document.getElementById('confirmText').innerHTML =
        'Hapus user <strong>' + email + '</strong>?<br>' +
        '<small class="text-muted">Tindakan ini tidak dapat dibatalkan.</small>';
      document.getElementById('deleteUserId').value = userid;
      var modal = new bootstrap.Modal(document.getElementById('confirmModal'));
      modal.show();
    }
  </script>
</body>

</html>
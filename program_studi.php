<?php
// Sertakan autentikasi & konfigurasi
require_once 'auth.php';
require_once 'config.php';

// Ambil koneksi database
$pdo = getDBConnection();

// Menangani permintaan form (POST) untuk tindakan: add, delete, update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  if ($_POST['action'] === 'add') {
    $nama_prodi = trim($_POST['nama_prodi'] ?? '');
    if (empty($nama_prodi)) {
      $_SESSION['flash_message'] = '<div class="alert alert-warning alert-dismissible mb-4" role="alert">
                <div class="d-flex align-items-center"><i class="ti ti-alert-circle me-2"></i><div>Nama program studi tidak boleh kosong!</div></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
      header("Location: program_studi.php");
      exit;
    } else {
      try {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM prodi_tbl WHERE LOWER(nama_prodi) = LOWER(?)");
        $checkStmt->execute([$nama_prodi]);
        if ((int) $checkStmt->fetchColumn() > 0) {
          $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible mb-4" role="alert">
                        <div class="d-flex align-items-center"><i class="ti ti-alert-circle me-2"></i><div>Gagal menambahkan program studi! (Mungkin duplikat)</div></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
          header("Location: program_studi.php");
          exit;
        }
        $stmt = $pdo->prepare("INSERT INTO prodi_tbl (nama_prodi) VALUES (?)");
        $stmt->execute([$nama_prodi]);
        $_SESSION['flash_message'] = '<div class="alert alert-success alert-dismissible mb-4" role="alert">
                    <div class="d-flex align-items-center"><i class="ti ti-circle-check me-2"></i><div>Program studi berhasil ditambahkan!</div></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        header("Location: program_studi.php");
        exit;
      } catch (PDOException $e) {
        $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible mb-4" role="alert">
                    <div class="d-flex align-items-center"><i class="ti ti-alert-circle me-2"></i><div>Gagal menambahkan program studi!</div></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        header("Location: program_studi.php");
        exit;
      }
    }
  } elseif ($_POST['action'] === 'delete') {
    $prodi_id = (int) ($_POST['prodi_id'] ?? 0);
    if ($prodi_id > 0) {
      try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_tbl WHERE prodi_id = ?");
        $stmt->execute([$prodi_id]);
        $count = (int) $stmt->fetchColumn();
        if ($count > 0) {
          $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible mb-4" role="alert">
                        <div class="d-flex align-items-center"><i class="ti ti-alert-circle me-2"></i><div>Tidak bisa menghapus! Program studi ini masih memiliki ' . $count . ' user/mahasiswa.</div></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } else {
          $stmt = $pdo->prepare("DELETE FROM prodi_tbl WHERE prodi_id = ?");
          $stmt->execute([$prodi_id]);
          $maxIdStmt = $pdo->query("SELECT MAX(prodi_id) FROM prodi_tbl");
          $maxId = (int) $maxIdStmt->fetchColumn();
          $nextId = max($maxId + 1, 1);
          $pdo->exec("ALTER TABLE prodi_tbl AUTO_INCREMENT = $nextId");
          $_SESSION['flash_message'] = '<div class="alert alert-success alert-dismissible mb-4" role="alert">
                        <div class="d-flex align-items-center"><i class="ti ti-circle-check me-2"></i><div>Program studi berhasil dihapus!</div></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
        header("Location: program_studi.php");
        exit;
      } catch (PDOException $e) {
        $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible mb-4" role="alert">
                    <div class="d-flex align-items-center"><i class="ti ti-alert-circle me-2"></i><div>Gagal menghapus program studi!</div></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        header("Location: program_studi.php");
        exit;
      }
    }
  } elseif ($_POST['action'] === 'update') {
    $prodi_id = (int) ($_POST['prodi_id'] ?? 0);
    $nama_prodi = trim($_POST['nama_prodi'] ?? '');
    if ($prodi_id <= 0 || empty($nama_prodi)) {
      $_SESSION['flash_message'] = '<div class="alert alert-warning alert-dismissible mb-4" role="alert">
                <div class="d-flex align-items-center"><i class="ti ti-alert-circle me-2"></i><div>Nama program studi tidak boleh kosong!</div></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
      header("Location: program_studi.php");
      exit;
    }
    try {
      $currentStmt = $pdo->prepare("SELECT nama_prodi FROM prodi_tbl WHERE prodi_id = ?");
      $currentStmt->execute([$prodi_id]);
      $current = $currentStmt->fetch();
      if (!$current) {
        $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible mb-4" role="alert">
                    <div class="d-flex align-items-center"><i class="ti ti-alert-circle me-2"></i><div>Program studi tidak ditemukan!</div></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        header("Location: program_studi.php");
        exit;
      }
      if ($current['nama_prodi'] !== $nama_prodi) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM prodi_tbl WHERE LOWER(nama_prodi) = LOWER(?) AND prodi_id != ?");
        $checkStmt->execute([$nama_prodi, $prodi_id]);
        if ((int) $checkStmt->fetchColumn() > 0) {
          $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible mb-4" role="alert">
                        <div class="d-flex align-items-center"><i class="ti ti-alert-circle me-2"></i><div>Gagal mengganti program studi! (Mungkin duplikat)</div></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
          header("Location: program_studi.php");
          exit;
        }
      }
      $updateStmt = $pdo->prepare("UPDATE prodi_tbl SET nama_prodi = ? WHERE prodi_id = ?");
      $updateStmt->execute([$nama_prodi, $prodi_id]);
      $_SESSION['flash_message'] = '<div class="alert alert-success alert-dismissible mb-4" role="alert">
                <div class="d-flex align-items-center"><i class="ti ti-circle-check me-2"></i><div>Program studi berhasil diperbarui!</div></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
      header("Location: program_studi.php");
      exit;
    } catch (PDOException $e) {
      $_SESSION['flash_message'] = '<div class="alert alert-danger alert-dismissible mb-4" role="alert">
                <div class="d-flex align-items-center"><i class="ti ti-alert-circle me-2"></i><div>Gagal memperbarui program studi!</div></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
      header("Location: program_studi.php");
      exit;
    }
  }
}

$flash_message = '';
if (isset($_SESSION['flash_message'])) {
  $flash_message = $_SESSION['flash_message'];
  unset($_SESSION['flash_message']);
}

// Search & Sort
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'nama_asc';
$orderClause = match ($sort) {
  'nama_desc' => 'p.nama_prodi DESC',
  'jumlah_asc' => 'jumlah_user ASC, p.nama_prodi ASC',
  'jumlah_desc' => 'jumlah_user DESC, p.nama_prodi ASC',
  default => 'p.nama_prodi ASC',
};

if ($search !== '') {
  $stmt = $pdo->prepare("
        SELECT p.prodi_id, p.nama_prodi, COUNT(u.userid) as jumlah_user
        FROM prodi_tbl p
        LEFT JOIN user_tbl u ON p.prodi_id = u.prodi_id
        WHERE p.nama_prodi LIKE ?
        GROUP BY p.prodi_id, p.nama_prodi
        ORDER BY $orderClause
    ");
  $stmt->execute(["%$search%"]);
} else {
  $stmt = $pdo->query("
        SELECT p.prodi_id, p.nama_prodi, COUNT(u.userid) as jumlah_user
        FROM prodi_tbl p
        LEFT JOIN user_tbl u ON p.prodi_id = u.prodi_id
        GROUP BY p.prodi_id, p.nama_prodi
        ORDER BY $orderClause
    ");
}
$prodis = $stmt->fetchAll();

// Helper for sort URL
function sortUrl($field, $currentSort, $search)
{
  $dir = ($currentSort === $field . '_asc') ? $field . '_desc' : $field . '_asc';
  $params = ['sort' => $dir];
  if ($search !== '')
    $params['q'] = $search;
  return 'program_studi.php?' . http_build_query($params);
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
  <title>Program Studi — Sistem Akademik</title>
  <link rel="stylesheet" href="css/tabler.min.css">
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
            <li class="nav-item"><a class="nav-link text-white active" href="program_studi.php"><i
                  class="ti ti-building-community me-1"></i>Program Studi</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="user.php"><i class="ti ti-users me-1"></i>User</a>
            </li>
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
          <a class="btn btn-link text-white text-start w-100 border-bottom m-0 active" href="program_studi.php"><i
              class="ti ti-building-community me-1"></i>Program Studi</a>
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
            <div class="col-auto">
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="ti ti-plus me-1"></i>Tambah Program Studi
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="page-body">
        <div class="container-xl">
          <?php if ($flash_message): ?>
            <div class="mb-3" id="flashAlert"><?php echo $flash_message; ?></div>
          <?php endif; ?>

          <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
              <h3 class="card-title mb-0"><i class="ti ti-building-community me-2 text-blue"></i>Daftar Program Studi
              </h3>
              <form method="GET" action="program_studi.php" class="d-flex align-items-center gap-2 flex-wrap">
                <?php if ($sort !== 'nama_asc'): ?><input type="hidden" name="sort"
                    value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
                <div class="input-group input-group-sm" style="min-width:220px">
                  <input type="text" name="q" class="form-control" placeholder="Cari program studi…"
                    value="<?= htmlspecialchars($search) ?>">
                  <button type="submit" class="btn btn-sm btn-secondary px-2"><i
                      class="ti ti-search me-1"></i>Cari</button>
                </div>
                <?php if ($search): ?>
                  <a href="program_studi.php" class="btn btn-sm btn-ghost-secondary"><i class="ti ti-x me-1"></i>Reset</a>
                <?php endif; ?>
              </form>
            </div>

            <?php if (empty($prodis)): ?>
              <div class="card-body">
                <div class="empty">
                  <div class="empty-img"><i class="ti ti-inbox" style="font-size:4rem;color:#94a3b8"></i></div>
                  <p class="empty-title">Tidak ada program studi ditemukan</p>
                  <p class="empty-subtitle text-muted">
                    <?= $search ? 'Tidak ada hasil untuk &ldquo;<strong>' . htmlspecialchars($search) . '</strong>&rdquo;. Coba kata kunci lain.' : 'Belum ada program studi. Tambahkan yang baru.' ?>
                  </p>
                  <?php if ($search): ?>
                    <div class="empty-action"><a href="program_studi.php" class="btn btn-primary"><i
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
                      <th><a href="<?= sortUrl('nama', $sort, $search) ?>">Nama Program
                          Studi<?= sortIcon('nama', $sort) ?></a></th>
                      <th class="text-center"><a href="<?= sortUrl('jumlah', $sort, $search) ?>">Jumlah
                          User<?= sortIcon('jumlah', $sort) ?></a></th>
                      <th class="w-1 text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($prodis as $index => $prodi):
                      $count = (int) $prodi['jumlah_user'];
                      $color = ($count === 0) ? 'secondary' : 'blue';
                      ?>
                      <tr>
                        <td class="text-center text-muted"><small><?= str_pad($index + 1, 3, '0', STR_PAD_LEFT) ?></small>
                        </td>
                        <td><strong><?= htmlspecialchars($prodi['nama_prodi']) ?></strong></td>
                        <td class="text-center"><span
                            class="badge rounded-pill bg-<?= $color ?> text-white"><?= $count ?></span></td>
                        <td class="text-center">
                          <div class="btn-list flex-nowrap justify-content-center">
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal"
                              onclick="setEditData(<?= $prodi['prodi_id'] ?>, '<?= htmlspecialchars(addslashes($prodi['nama_prodi'])) ?>')">
                              <i class="ti ti-edit me-1"></i>Edit
                            </button>
                            <button class="btn btn-sm btn-danger" <?= ($count > 0) ? 'disabled' : '' ?>
                              onclick="konfirmHapus(<?= $prodi['prodi_id'] ?>, '<?= htmlspecialchars(addslashes($prodi['nama_prodi'])) ?>')">
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
                  Menampilkan <strong><?= count($prodis) ?></strong> program studi
                  <?= $search ? 'untuk pencarian &ldquo;<strong>' . htmlspecialchars($search) . '</strong>&rdquo;' : '' ?>
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

  <!-- Modal Tambah Prodi -->
  <div class="modal modal-blur fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="ti ti-plus me-2 text-blue"></i>Tambah Program Studi Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
              <label class="form-label required">Nama Program Studi</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ti ti-building-community"></i></span>
                <input type="text" name="nama_prodi" class="form-control" placeholder="Contoh: Ilmu Komputer" required>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i
                class="ti ti-x me-1"></i>Batal</button>
            <button type="submit" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Edit Prodi -->
  <div class="modal modal-blur fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="ti ti-edit me-2 text-warning"></i>Edit Program Studi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="prodi_id" id="editProdiId" value="">
            <div class="mb-3">
              <label class="form-label required">Nama Program Studi</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ti ti-building-community"></i></span>
                <input type="text" name="nama_prodi" id="editProdiName" class="form-control"
                  placeholder="Masukkan nama program studi" required>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i
                class="ti ti-x me-1"></i>Batal</button>
            <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Konfirmasi Hapus -->
  <div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-body">
          <div class="modal-title text-center mb-2">
            <div class="text-danger mb-3"><i class="ti ti-trash" style="font-size:2.5rem"></i></div>
            <h3>Hapus Program Studi?</h3>
          </div>
          <p class="text-muted text-center" id="deleteConfirmText">Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="modal-footer justify-content-center">
          <form method="POST" id="deleteForm" class="w-100">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="prodi_id" id="deleteProdiId" value="">
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
    function setEditData(prodiId, prodiName) {
      document.getElementById('editProdiId').value = prodiId;
      document.getElementById('editProdiName').value = prodiName;
    }
    function konfirmHapus(prodiId, prodiName) {
      document.getElementById('deleteConfirmText').innerHTML =
        'Hapus program studi <strong>' + prodiName + '</strong>?<br><small class="text-muted">Tindakan ini tidak dapat dibatalkan.</small>';
      document.getElementById('deleteProdiId').value = prodiId;
      var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
      modal.show();
    }
    // Auto-dismiss flash alert
    document.addEventListener('DOMContentLoaded', function () {
      var flash = document.getElementById('flashAlert');
      if (flash) {
        setTimeout(function () {
          flash.style.transition = 'opacity 0.5s';
          flash.style.opacity = '0';
          setTimeout(function () { flash.remove(); }, 500);
        }, 4000);
      }
    });
  </script>
</body>

</html>
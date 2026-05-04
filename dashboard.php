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

$flash = '';
if (isset($_SESSION['flash_message'])) {
  $flash = $_SESSION['flash_message'];
  unset($_SESSION['flash_message']);
}

$totalProdi = 0;
$totalUser = 0;
$chartLabels = [];
$chartValues = [];
$dbError = '';

try {
  $pdo = db_connect();

  $totalProdi = (int) $pdo->query("SELECT COUNT(*) FROM prodi_tbl")->fetchColumn();
  $totalUser = (int) $pdo->query("SELECT COUNT(*) FROM user_tbl")->fetchColumn();

  $rows = $pdo->query("\
    SELECT p.nama_prodi, COUNT(u.userid) AS total_user\
    FROM prodi_tbl p\
    LEFT JOIN user_tbl u ON u.prodi_id = p.prodi_id\
    GROUP BY p.prodi_id, p.nama_prodi\
    ORDER BY p.nama_prodi\
  ")->fetchAll();

  foreach ($rows as $row) {
    $chartLabels[] = (string) $row['nama_prodi'];
    $chartValues[] = (int) $row['total_user'];
  }
} catch (PDOException $e) {
  $dbError = $e->getMessage();
}

?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard</title>

  <!-- Tabler CSS (CDN) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
</head>
<body>
  <div class="page">
    <header class="navbar navbar-expand-md">
      <div class="container-xl">
        <a class="navbar-brand" href="dashboard.php">Dashboard</a>
      </div>
    </header>

    <div class="page-wrapper">
      <div class="container-xl">
        <div class="page-header d-print-none">
          <h2 class="page-title">Dashboard</h2>
        </div>

        <?php if (!empty($flash)) echo $flash; ?>

        <?php if (!empty($dbError)): ?>
          <div class="alert alert-danger" role="alert">
            Koneksi / query database gagal: <b><?php echo htmlspecialchars($dbError); ?></b>
          </div>
        <?php endif; ?>

        <div class="row row-cards">
          <div class="col-12 col-md-6">
            <div class="card card-sm">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col">
                    <div class="font-weight-medium">Total Prodi</div>
                    <div class="h1 mb-0"><?php echo (int) $totalProdi; ?></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="card card-sm">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col">
                    <div class="font-weight-medium">Total User</div>
                    <div class="h1 mb-0"><?php echo (int) $totalUser; ?></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Jumlah User per Prodi</h3>
              </div>
              <div class="card-body">
                <div style="height: 340px;">
                  <canvas id="usersPerProdiChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabler JS (CDN) -->
  <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>

  <!-- Chart.js (CDN) untuk grafik -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <script>
    const labels = <?php echo json_encode($chartLabels, JSON_UNESCAPED_UNICODE); ?>;
    const values = <?php echo json_encode($chartValues); ?>;

    const ctx = document.getElementById('usersPerProdiChart');
    if (ctx) {
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Jumlah user',
            data: values,
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: true }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { precision: 0 }
            }
          }
        }
      });
    }
  </script>
</body>
</html>
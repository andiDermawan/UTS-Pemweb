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

// Database connection (PDO)
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

// Fetch statistics
$totalProdi = (int)$pdo->query("SELECT COUNT(*) FROM prodi_tbl")->fetchColumn();
$totalUser  = (int)$pdo->query("SELECT COUNT(*) FROM user_tbl")->fetchColumn();

$stmt = $pdo->query("SELECT p.nama_prodi, COUNT(u.userid) AS jumlah
                      FROM prodi_tbl p
                      LEFT JOIN user_tbl u ON p.prodi_id = u.prodi_id
                      GROUP BY p.prodi_id
                      ORDER BY p.nama_prodi ASC");
$perProdi = $stmt->fetchAll();

$labels = array_column($perProdi, 'nama_prodi');
$counts = array_map(function ($r) {
    return (int)$r['jumlah'];
}, $perProdi);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard — Sistem Akademik</title>

    <!-- Tabler CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/tabler-icons.min.css">

    <style>
        /* Sedikit penyesuaian di atas Tabler */
        .navbar-brand-text {
            font-weight: 700;
            font-size: 1rem;
        }

        /* Animasi smooth untuk cards */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: slideUp 0.4s ease-out;
        }

        .card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .card:nth-child(3) {
            animation-delay: 0.3s;
        }

        /* Hover effect untuk stat cards */
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Styling untuk chart card */
        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Badge styling */
        .badge {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="antialiased">
    <div class="wrapper">

        <header class="navbar navbar-expand-md navbar-dark bg-blue sticky-top d-print-none">
            <div class="container-xl">
                <!-- Brand -->
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

                <!-- Right side -->
                <div class="ms-auto d-flex align-items-center gap-2">
                    <nav class="nav d-none d-md-flex">
                        <a class="nav-link text-white" href="dashboard.php" title="Dashboard">
                            <i class="ti ti-home me-1"></i>Dashboard
                        </a>
                        <a class="nav-link text-white" href="profile.php" title="Profile">
                            <i class="ti ti-user me-1"></i>Profile
                        </a>
                        <a class="nav-link text-white" href="program_studi.php" title="Program Studi">
                            <i class="ti ti-building-community me-1"></i>Program Studi
                        </a>
                        <a class="nav-link text-white" href="user.php" title="User">
                            <i class="ti ti-users me-1"></i>User
                        </a>
                    </nav>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <div class="page-pretitle">Akademik</div>
                            <h2 class="page-title">
                                <i class="ti ti-chart-bar me-2"></i>Dashboard
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page-body">
                <div class="container-xl">

                    <div class="row row-deck row-cards">
                        <!-- Card Total Program Studi -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <span class="bg-blue text-white avatar avatar-lg rounded">
                                                <i class="ti ti-school"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="text-muted small">Total Program Studi</div>
                                            <div class="fw-bold display-6"><?= $totalProdi ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Total User -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <span class="bg-azure text-white avatar avatar-lg rounded">
                                                <i class="ti ti-users"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="text-muted small">Total User</div>
                                            <div class="fw-bold display-6"><?= $totalUser ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chart Card -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="ti ti-chart-bar me-2 text-blue"></i>User per Program Studi
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="chartUsersPerProdi"></canvas>
                                    </div>
                                </div>
                                <div class="card-footer text-muted text-center small">
                                    <i class="ti ti-info-circle me-1"></i>Data jumlah user yang terdaftar di setiap program studi
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer -->
            <footer class="footer footer-transparent d-print-none">
                <div class="container-xl">
                    <div class="row text-center align-items-center">
                        <div class="col-12 col-lg-auto mt-3 mt-lg-0 text-muted small">
                            Sistem Akademik &copy; <?= date('Y') ?>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Tabler JS -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const labels = <?= json_encode($labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const data = <?= json_encode($counts) ?>;

            const ctx = document.getElementById('chartUsersPerProdi').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah User',
                        data: data,
                        backgroundColor: 'rgba(13, 110, 253, 0.8)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                        hoverBackgroundColor: 'rgba(13, 110, 253, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            borderRadius: 4,
                            titleFont: {
                                size: 13
                            },
                            bodyFont: {
                                size: 12
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            },
                            grid: {
                                drawBorder: false,
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        }
                    }
                }
            });
        });
    </script>

</body>

</html>
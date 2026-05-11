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
        html, body {
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
    </style>
</head>

<body class="antialiased">
    <div class="wrapper">
        <header class="navbar navbar-expand-md navbar-dark bg-blue sticky-top d-print-none">
            <div class="container-xl">
                <!-- Brand -->
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
                        <a href="logout.php" class="btn btn-outline ms-4">
                            <i class="ti ti-logout me-1"></i>Logout
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
                                <i class="ti ti-user me-2"></i>Profile
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="card-body">
                            HALAMAN PROFILE
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer d-print-none bg-blue text-white border-top border-blue py-2" style="min-height: auto; margin-top: auto;">
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

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
</body>

</html>
<?php
session_start();

$db_host = 'localhost';
$db_name = 'pemweb_db';
$db_user = 'root';
$db_pass = 'Qwerty12!';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = "Email dan password wajib diisi!";
    } else {
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $statement = $pdo->prepare("SELECT * FROM user_tbl WHERE email = :email");
            $statement->bindParam(':email', $email);
            $statement->execute();
            $user = $statement->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['userid'];
                $_SESSION['email'] = $user['email'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error_message = "Email atau password salah!";
            }
        } catch (PDOException $e) {
            $error_message = "Koneksi database gagal: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tabler CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Custom background untuk panel kanan agar persis seperti referensi */
        .bg-gradient-custom {
            background: linear-gradient(135deg, #7c5cff, #5a3ce8) !important;
        }

        /* Kustomisasi warna primary button agar senada dengan tema */
        .btn-primary {
            background-color: #684fff;
            border-color: #684fff;
        }

        .btn-primary:hover {
            background-color: #563be8;
            border-color: #563be8;
        }
    </style>
</head>

<body class="d-flex flex-column bg-white">
    <div class="row g-0 flex-fill vh-100">

        <!-- --- LEFT PANEL (LOGIN FORM) --- -->
        <div class="col-12 col-lg-6 col-xl-5 border-top-wide border-primary d-flex flex-column justify-content-center">
            <div class="container container-tight my-5 px-lg-5">
                <div class="mb-4">
                    <h2 class="h1 fw-bold text-dark">Login</h2>
                    <p class="text-muted mb-4">Silakan masuk ke akun Anda untuk melanjutkan.</p>
                </div>

                <?php
                if (isset($_SESSION['flash_message'])) {
                    echo $_SESSION['flash_message'];

                    unset($_SESSION['flash_message']);
                }
                ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-important" role="alert">
                        <div class="d-flex">
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                    <path d="M12 8v4" />
                                    <path d="M12 16h.01" />
                                </svg>
                            </div>
                            <div>
                                <?php echo $error_message; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="mail@website.com"
                            required autocomplete="off">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="password">
                            Password
                            <span class="form-label-description">
                                <a href="#" style="color: #684fff;">Lupa sandi?</a>
                            </span>
                        </label>
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="Min. 8 character" required minlength="8">
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Login</button>
                    </div>
                </form>

                <div class="text-center text-muted mt-4">
                    Belum memiliki akun? <a href="#" tabindex="-1" style="color: #684fff; font-weight: 600;">Buat
                        akun</a>
                </div>
            </div>
        </div>

        <div
            class="col-12 col-lg-6 col-xl-7 d-none d-lg-flex bg-gradient-custom text-white justify-content-center align-items-center">
            <div class="text-center px-5 max-w-sm">
                <h2 class="display-5 fw-bold mb-3">Welcome</h2>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
</body>

</html>
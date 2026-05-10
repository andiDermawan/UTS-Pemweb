<?php
session_start();

$db_host = 'localhost';
$db_name = 'pemweb_db';
$db_user = 'root';
$db_pass = '';

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
    <title>Login - Sistem Akademik</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tabler CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css" />
    <style>
        :root {
            --login-accent: #206bc4;
            --login-bg: #f6f8fc;
            --login-glow: rgba(32, 107, 196, 0.16);
            --login-glow-2: rgba(45, 206, 137, 0.12);
        }

        body {
            font-family: 'Manrope', sans-serif;
            background-color: var(--login-bg);
            background-image:
                radial-gradient(600px 420px at 12% -10%, var(--login-glow), transparent 60%),
                radial-gradient(720px 480px at 110% 0%, var(--login-glow-2), transparent 55%);
        }
    </style>
</head>

<body class="d-flex flex-column">
    <div class="page page-center">
        <div class="container container-tight py-4">
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

            <form class="card card-md" method="POST" action="">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h1 class="h2 fw-bold mb-1">Sistem Akademik</h1>
                        <p class="text-muted mb-0">Silakan login untuk melanjutkan</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="mail@website.com"
                            required autocomplete="off">
                    </div>

                    <div class="mb-2">
                        <label class="form-label" for="password">
                            Password
                            <span class="form-label-description">
                                <a href="#" class="link-secondary">Lupa sandi?</a>
                            </span>
                        </label>
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="Min. 8 character" required minlength="8">
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
</body>

</html>
<?php
// Middleware Autentikasi & Otorisasi
session_start();

// Cek apakah user sudah login
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

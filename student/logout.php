<?php
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Clear "remember me" cookie
if (!empty($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
    // Also remove the token from the database
    require '../includes/db.php';
    if (!empty($_SESSION['student_id'])) {
        $pdo->prepare("UPDATE student_users SET remember_token = NULL WHERE id = ?")
            ->execute([$_SESSION['student_id']]);
    }
}

// Destroy the session
session_destroy();

// Redirect to login
header('Location: login.php');
exit;
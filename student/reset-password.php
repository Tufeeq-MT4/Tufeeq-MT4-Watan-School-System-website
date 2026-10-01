<?php
require '../includes/db.php';
session_start();

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = ''; $success = '';

if (!$token) {
  die('❌ Missing reset token. Please request a new password reset link.');
}

// Verify token and check expiry
$stmt = $pdo->prepare("SELECT * FROM student_users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
  die('❌ Invalid or expired reset link. Please <a href="forgot-password.php">request a new one</a>.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $p1 = $_POST['password'] ?? '';
  $p2 = $_POST['confirm'] ?? '';

  if (strlen($p1) < 6) {
    $error = 'Password must be at least 6 characters.';
  } elseif ($p1 !== $p2) {
    $error = 'Passwords do not match.';
  } else {
    $hash = password_hash($p1, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE student_users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?")
        ->execute([$hash, $user['id']]);
    $success = '✅ Password reset successfully! You can now <a href="login.php">login</a> with your new password.';
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Reset Password | Watan School System</title>
  <link rel="stylesheet" href="/watan-school/css/style.css">
  <style>
    body { background: linear-gradient(135deg, #0a3d62 0%, #062a44 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
    .auth-card { background: #fff; padding: 50px 45px; border-radius: 20px; box-shadow: 0 25px 60px rgba(0,0,0,.25); max-width: 440px; width: 100%; }
    .auth-logo { text-align: center; margin-bottom: 8px; font-size: 48px; }
    .auth-title { text-align: center; font-size: 26px; color: #0a3d62; margin-bottom: 6px; font-family: 'Playfair Display', serif; }
    .auth-sub { text-align: center; color: #6b7c93; font-size: 14px; margin-bottom: 30px; }
  </style>
</head>
<body>
<div class="auth-card">
  <div class="auth-logo">🔒</div>
  <h2 class="auth-title">Reset Password</h2>
  <p class="auth-sub">Welcome back, <?= htmlspecialchars($user['fullname']) ?>!</p>

  <?php if ($error): ?><p class="alert error"><?= $error ?></p><?php endif; ?>
  <?php if ($success): ?><p class="alert success"><?= $success ?></p><?php endif; ?>

  <?php if (!$success): ?>
  <form method="POST" class="form-box" style="box-shadow:none; padding:0; max-width:100%;">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <input name="password" type="password" placeholder="New Password" required minlength="6">
    <input name="confirm" type="password" placeholder="Confirm New Password" required minlength="6">
    <button type="submit">Reset Password</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
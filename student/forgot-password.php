<?php
require '../includes/db.php';
require '../includes/mailer.php';
session_start();

$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = '❌ Please enter a valid email address.';
  } else {
    $stmt = $pdo->prepare("SELECT * FROM student_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
      // ❌ Email NOT registered
      $error = '❌ This email is not registered. Please check the spelling or <a href="register.php" style="color:#0a3d62; font-weight:600;">create an account</a>.';
    } else {
      // ✅ Email IS registered — send reset link
      $token   = bin2hex(random_bytes(32));
      $expires = date('Y-m-d H:i:s', time() + 3600);

      $upd = $pdo->prepare("UPDATE student_users SET reset_token=?, reset_expires=? WHERE id=?");
      $upd->execute([$token, $expires, $user['id']]);

      $link = "http://localhost/watan-school/student/reset-password.php?token=$token";

      $body = "
        <div style='font-family:Arial,sans-serif; max-width:600px; margin:auto; padding:30px; background:#f7f9fc;'>
          <div style='background:#0a3d62; color:#fff; padding:25px; border-radius:10px 10px 0 0; text-align:center;'>
            <h1 style='margin:0; font-size:22px;'>🎓 Watan School System</h1>
            <p style='margin:6px 0 0; color:#f6b93b; font-size:13px; letter-spacing:1px;'>PASSWORD RESET REQUEST</p>
          </div>

          <div style='background:#fff; padding:35px; border-radius:0 0 10px 10px;'>
            <h2 style='color:#0a3d62; margin-top:0;'>Hi " . htmlspecialchars($user['fullname']) . ",</h2>
            <p style='color:#2c3e50; line-height:1.7;'>
              We received a request to reset your password for your Watan School System student account.
            </p>
            <p style='color:#2c3e50; line-height:1.7;'>
              Click the button below to set a new password. <strong>This link is valid for 1 hour.</strong>
            </p>

            <div style='text-align:center; margin:35px 0;'>
              <a href='$link' style='background:#f6b93b; color:#0a3d62; padding:15px 40px; border-radius:8px; text-decoration:none; font-weight:700; display:inline-block;'>
                Reset My Password →
              </a>
            </div>

            <p style='color:#6b7c93; font-size:13px; line-height:1.7;'>
              Or copy this link into your browser:<br>
              <a href='$link' style='color:#0a3d62; word-break:break-all; font-size:12px;'>$link</a>
            </p>

            <hr style='border:none; border-top:1px solid #e5eaf0; margin:30px 0;'>

            <p style='color:#6b7c93; font-size:13px; line-height:1.7;'>
              <strong>Didn't request this?</strong><br>
              You can safely ignore this email. Your password won't change unless you click the link above.
            </p>

            <p style='color:#6b7c93; font-size:12px; margin-top:30px;'>
              — Watan School System<br>
              Darazinda · 📞 0334 9976001
            </p>
          </div>
        </div>
      ";

      send_mail($user['email'], $user['fullname'], 'Reset Your Watan School System Password', $body);

      $msg = '✅ A password reset link has been sent to <strong>' . htmlspecialchars($email) . '</strong>. Check your inbox (and spam folder).';
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Forgot Password | Watan School System</title>
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
  <div class="auth-logo">🔑</div>
  <h2 class="auth-title">Forgot Password?</h2>
  <p class="auth-sub">Enter your email — we'll send a reset link.</p>

  <?php if ($error): ?><p class="alert error"><?= $error ?></p><?php endif; ?>
  <?php if ($msg): ?><p class="alert success"><?= $msg ?></p><?php endif; ?>

  <form method="POST" class="form-box" style="box-shadow:none; padding:0; max-width:100%;">
    <input name="email" type="email" placeholder="Your Email" required>
    <button type="submit">Send Reset Link</button>
    <p style="text-align:center; font-size:14px; margin-top:10px;">
      <a href="login.php" style="color:#0a3d62; font-weight:600;">← Back to Login</a>
    </p>
  </form>
</div>
</body>
</html>
<?php
session_start();
require '../includes/db.php';
require '../includes/captcha.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    captcha_generate();
}

// Auto-login via "remember me" cookie
if (empty($_SESSION['student_id']) && !empty($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $pdo->prepare("SELECT * FROM student_users WHERE remember_token = ?");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if ($u) {
        $_SESSION['student_id']   = $u['id'];
        $_SESSION['student_name'] = $u['fullname'];
        header('Location: dashboard.php');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email    = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $remember = !empty($_POST['remember']);

  if (!captcha_check($_POST['captcha'] ?? 0)) {
    $error = '❌ Wrong CAPTCHA answer. Try again.';
  } else {
    $stmt = $pdo->prepare("SELECT * FROM student_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['student_id']   = $user['id'];
      $_SESSION['student_name'] = $user['fullname'];

      // Remember me: create a token valid for 30 days
      if ($remember) {
        $token = bin2hex(random_bytes(32));
        $upd = $pdo->prepare("UPDATE student_users SET remember_token = ? WHERE id = ?");
        $upd->execute([$token, $user['id']]);
        setcookie('remember_token', $token, time() + (86400 * 30), '/');
      }

      header('Location: dashboard.php');
      exit;
    }
    $error = 'Invalid email or password.';
  }
  captcha_generate();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Student Login | Watan School</title>
  <link rel="stylesheet" href="/watan-school/css/style.css">
  <style>
    body {
      background: linear-gradient(135deg, #0a3d62 0%, #062a44 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
    }
    .auth-card {
      background: #fff;
      padding: 50px 45px;
      border-radius: 20px;
      box-shadow: 0 25px 60px rgba(0,0,0,.25);
      max-width: 440px;
      width: 100%;
    }
    .auth-logo {
      text-align: center;
      margin-bottom: 8px;
      font-size: 40px;
    }
    .auth-title {
      text-align: center;
      font-size: 28px;
      color: #0a3d62;
      margin-bottom: 6px;
    }
    .auth-sub {
      text-align: center;
      color: #6b7c93;
      font-size: 14px;
      margin-bottom: 30px;
    }
    .remember-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 14px;
    }
    .remember-row label {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      color: #2c3e50;
    }
    .remember-row a {
      color: #0a3d62;
      font-weight: 600;
    }
  </style>
</head>
<body>
<div class="auth-card">
  <div class="auth-logo">🎓</div>
  <h2 class="auth-title">Student Login</h2>
  <p class="auth-sub">Welcome back to Watan School</p>

  <?php if ($error): ?><p class="alert error"><?= $error ?></p><?php endif; ?>

  <form method="POST" class="form-box" style="box-shadow:none; padding:0; max-width:100%;">
    <input name="email" type="email" placeholder="Email" required>

    <div class="password-wrap">
      <input id="pwd" name="password" type="password" placeholder="Password" required>
      <span class="eye-toggle" onclick="togglePass('pwd')">👁</span>
    </div>

    <div class="remember-row">
      <label><input type="checkbox" name="remember" style="width:auto;"> Remember me</label>
      <a href="forgot-password.php">Forgot Password?</a>
    </div>

    <?= captcha_field() ?>

    <button type="submit">Login</button>
    <p style="text-align:center; font-size:14px; margin-top:10px;">
      New here? <a href="register.php" style="color:#0a3d62; font-weight:600;">Create an account</a>
    </p>
    <p style="text-align:center; font-size:13px; margin-top:5px;">
      <a href="../index.php" style="color:#6b7c93;">← Back to Home</a>
    </p>
  </form>
</div>

<script>
function togglePass(id) {
  const inp = document.getElementById(id);
  inp.type = (inp.type === 'password') ? 'text' : 'password';
}
</script>
</body>
</html>
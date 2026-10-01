<?php
session_start();
require '../includes/db.php';
require '../includes/captcha.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    captcha_generate();
}

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullname = trim($_POST['fullname'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $phone    = trim($_POST['phone'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm  = $_POST['confirm'] ?? '';

  if (!$fullname || !$email || !$password) {
    $error = 'Please fill all required fields.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email address.';
  } elseif (strlen($password) < 6) {
    $error = 'Password must be at least 6 characters.';
  } elseif ($password !== $confirm) {
    $error = 'Passwords do not match.';
  } elseif (!captcha_check($_POST['captcha'] ?? 0)) {
    $error = '❌ Wrong CAPTCHA answer. Try again.';
  } else {
    $check = $pdo->prepare("SELECT id FROM student_users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
      $error = 'Email already registered.';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("INSERT INTO student_users (fullname, email, phone, password) VALUES (?,?,?,?)");
      $stmt->execute([$fullname, $email, $phone, $hash]);
      $success = 'Account created! You can now <a href="login.php">login</a>.';
    }
  }
  captcha_generate();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Create Account | Watan School</title>
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
      max-width: 480px;
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
    .strength-bar {
      height: 6px;
      background: #e5eaf0;
      border-radius: 3px;
      overflow: hidden;
      margin: -6px 0 4px;
    }
    .strength-fill {
      height: 100%;
      width: 0%;
      transition: all .3s;
      border-radius: 3px;
    }
    .strength-text {
      font-size: 12px;
      text-align: right;
      margin-bottom: 8px;
      color: #6b7c93;
    }
  </style>
</head>
<body>
<div class="auth-card">
  <div class="auth-logo">🎓</div>
  <h2 class="auth-title">Create Your Account</h2>
  <p class="auth-sub">Join Watan School admissions portal</p>

  <?php if ($error): ?><p class="alert error"><?= $error ?></p><?php endif; ?>
  <?php if ($success): ?><p class="alert success"><?= $success ?></p><?php endif; ?>

  <form method="POST" class="form-box" style="box-shadow:none; padding:0; max-width:100%;">
    <input name="fullname" placeholder="Full Name" required>
    <input name="email" type="email" placeholder="Email" required>
    <input name="phone" placeholder="Phone (optional)">

    <div class="password-wrap">
      <input id="pwd1" name="password" type="password" placeholder="Password (min 6 chars)" required oninput="checkStrength(this.value)">
      <span class="eye-toggle" onclick="togglePass('pwd1')">👁</span>
    </div>

    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
    <div class="strength-text" id="strengthText"></div>

    <div class="password-wrap">
      <input id="pwd2" name="confirm" type="password" placeholder="Confirm Password" required>
      <span class="eye-toggle" onclick="togglePass('pwd2')">👁</span>
    </div>

    <?= captcha_field() ?>

    <button type="submit">Create Account</button>
    <p style="text-align:center; font-size:14px; margin-top:10px;">
      Already have an account? <a href="login.php" style="color:#0a3d62; font-weight:600;">Login here</a>
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

function checkStrength(pwd) {
  const fill = document.getElementById('strengthFill');
  const text = document.getElementById('strengthText');
  let score = 0;
  if (pwd.length >= 6) score++;
  if (pwd.length >= 10) score++;
  if (/[A-Z]/.test(pwd)) score++;
  if (/[0-9]/.test(pwd)) score++;
  if (/[^A-Za-z0-9]/.test(pwd)) score++;

  const levels = [
    { w: '0%',   c: 'transparent', t: '' },
    { w: '25%',  c: '#ef4444',     t: 'Weak' },
    { w: '50%',  c: '#f59e0b',     t: 'Fair' },
    { w: '75%',  c: '#22c55e',     t: 'Good' },
    { w: '100%', c: '#16a34a',     t: 'Strong 💪' },
    { w: '100%', c: '#16a34a',     t: 'Very Strong 🔒' }
  ];
  const lv = levels[score];
  fill.style.width = lv.w;
  fill.style.background = lv.c;
  text.textContent = lv.t;
}
</script>
</body>
</html>
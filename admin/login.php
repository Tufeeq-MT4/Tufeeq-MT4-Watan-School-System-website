<?php
session_start();
require '../includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'] ?? '';
  $password = $_POST['password'] ?? '';
  $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
  $stmt->execute([$username]);
  $admin = $stmt->fetch();
  if ($admin && password_verify($password, $admin['password'])) {
    $_SESSION['admin'] = $admin['username'];
    header('Location: dashboard.php');
    exit;
  }
  $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Login | Watan School</title>
  <link rel="stylesheet" href="/watan-school/css/style.css">
</head>
<body>
<section style="max-width:400px;margin-top:100px;">
  <h2>Admin Login</h2>
  <?php if ($error): ?><p class="alert error"><?= $error ?></p><?php endif; ?>
  <form method="POST" class="form-box">
    <input name="username" placeholder="Username" required>
    <input name="password" type="password" placeholder="Password" required>
    <button type="submit">Login</button>
  </form>
</section>
</body>
</html>
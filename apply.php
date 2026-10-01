<?php
session_start();

// If already logged in → go to application form
if (!empty($_SESSION['student_id'])) {
    header('Location: student/apply.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Apply to Watan School System</title>
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
    .apply-card {
      background: #fff;
      padding: 55px 45px;
      border-radius: 20px;
      box-shadow: 0 25px 60px rgba(0,0,0,.25);
      max-width: 520px;
      width: 100%;
      text-align: center;
    }
    .apply-logo { font-size: 52px; margin-bottom: 15px; }
    .apply-title { font-size: 30px; color: #0a3d62; margin-bottom: 10px; font-family: 'Playfair Display', serif; }
    .apply-sub { color: #6b7c93; font-size: 15px; margin-bottom: 35px; }
    .choice-btn {
      display: flex;
      align-items: center;
      gap: 18px;
      padding: 22px 25px;
      border-radius: 14px;
      text-align: left;
      text-decoration: none;
      margin-bottom: 15px;
      transition: all .25s ease;
    }
    .choice-btn .icon { font-size: 32px; flex-shrink: 0; }
    .choice-btn .title { font-size: 17px; font-weight: 700; margin-bottom: 3px; display: block; }
    .choice-btn .desc { font-size: 13px; opacity: .8; display: block; }
    .choice-btn.primary { background: #f6b93b; color: #0a3d62; }
    .choice-btn.primary:hover { background: #d99a1a; transform: translateY(-3px); box-shadow: 0 10px 25px rgba(246,185,59,.4); }
    .choice-btn.secondary { background: #0a3d62; color: #fff; }
    .choice-btn.secondary:hover { background: #062a44; transform: translateY(-3px); box-shadow: 0 10px 25px rgba(10,61,98,.4); }
    .back-link { margin-top: 20px; font-size: 14px; color: #6b7c93; }
    .back-link a { color: #0a3d62; font-weight: 600; }
  </style>
</head>
<body>
<div class="apply-card">
  <div class="apply-logo">🎓</div>
  <h2 class="apply-title">Join Watan School System</h2>
  <p class="apply-sub">Choose an option to continue your admission</p>

  <a href="/watan-school/student/register.php" class="choice-btn primary">
    <span class="icon">✨</span>
    <span>
      <span class="title">Create New Account</span>
      <span class="desc">New student? Start your application here.</span>
    </span>
  </a>

  <a href="/watan-school/student/login.php" class="choice-btn secondary">
    <span class="icon">🔑</span>
    <span>
      <span class="title">I Already Have an Account</span>
      <span class="desc">Login to continue your application.</span>
    </span>
  </a>

  <p class="back-link">
    <a href="/watan-school/index.php">← Back to Home</a>
  </p>
</div>
</body>
</html>
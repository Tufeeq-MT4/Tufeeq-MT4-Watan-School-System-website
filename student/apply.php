<?php
require 'auth.php';
require '../includes/db.php';

$sid = $_SESSION['student_id'];

$u = $pdo->prepare("SELECT * FROM student_users WHERE id = ?");
$u->execute([$sid]);
$user = $u->fetch();

$chk = $pdo->prepare("SELECT id FROM applications WHERE student_id = ?");
$chk->execute([$sid]);
$already = $chk->fetch();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already) {
  $fullname        = trim($_POST['fullname'] ?? '');
  $email           = trim($_POST['email'] ?? '');
  $phone           = trim($_POST['phone'] ?? '');
  $dob             = $_POST['dob'] ?? null;
  $gender          = trim($_POST['gender'] ?? '');
  $blood_group     = trim($_POST['blood_group'] ?? '');
  $religion        = trim($_POST['religion'] ?? '');
  $nationality     = trim($_POST['nationality'] ?? '');
  $father_name     = trim($_POST['father_name'] ?? '');
  $father_cnic     = trim($_POST['father_cnic'] ?? '');
  $grade           = trim($_POST['grade'] ?? '');
  $previous_school = trim($_POST['previous_school'] ?? '');
  $address         = trim($_POST['address'] ?? '');
  $city            = trim($_POST['city'] ?? '');
  $hear_about      = trim($_POST['hear_about'] ?? '');
  $message         = trim($_POST['message'] ?? '');

  if (!$fullname || !$email || !$phone || !$grade) {
    $error = 'Please fill all required fields.';
  } else {
    $stmt = $pdo->prepare("INSERT INTO applications
      (student_id, fullname, email, phone, dob, gender, blood_group, religion, nationality,
       father_name, father_cnic, grade, previous_school,
       address, city, hear_about, message)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
      $sid, $fullname, $email, $phone, $dob, $gender, $blood_group, $religion, $nationality,
      $father_name, $father_cnic, $grade, $previous_school,
      $address, $city, $hear_about, $message
    ]);
    header('Location: dashboard.php');
    exit;
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Application Form | Watan School System</title>
  <link rel="stylesheet" href="/watan-school/css/style.css">
</head>
<body>
<header>
  <h1><a href="dashboard.php">Watan School System</a></h1>
  <nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="monthly-fees.php">Monthly Fees</a>
    <a href="upload.php">Documents</a>
    <a href="logout.php" class="apply-btn">Logout</a>
  </nav>
</header>

<section style="max-width:780px;">
  <h2 style="text-align:center;">Admission Application Form</h2>

  <?php if ($already): ?>
    <div class="alert success">✅ You have already submitted your application. Track it on your <a href="dashboard.php">dashboard</a>.</div>
  <?php else: ?>
    <?php if ($error): ?><p class="alert error"><?= $error ?></p><?php endif; ?>

    <form method="POST" class="form-box">

      <h3 style="margin:0; color:#0a3d62;">👤 Student Information</h3>
      <input name="fullname" placeholder="Student Full Name" value="<?= htmlspecialchars($user['fullname']) ?>" required>
      <input name="email" type="email" placeholder="Email" value="<?= htmlspecialchars($user['email']) ?>" required>
      <input name="phone" placeholder="Phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
      <input type="date" name="dob" placeholder="Date of Birth">

      <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <select name="gender" style="flex:1;">
          <option value="">Gender</option>
          <option>Male</option>
          <option>Female</option>
          <option>Other</option>
        </select>
        <select name="blood_group" style="flex:1;">
          <option value="">Blood Group</option>
          <option>A+</option><option>A-</option>
          <option>B+</option><option>B-</option>
          <option>AB+</option><option>AB-</option>
          <option>O+</option><option>O-</option>
        </select>
      </div>

      <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <input name="religion" placeholder="Religion" style="flex:1;">
        <input name="nationality" placeholder="Nationality" value="Pakistani" style="flex:1;">
      </div>

      <h3 style="margin-top:20px; margin-bottom:0; color:#0a3d62;">👨‍👩‍👦 Father Information</h3>
      <input name="father_name" placeholder="Father's Full Name">
      <input name="father_cnic" placeholder="Father's CNIC (e.g., 12345-1234567-1)">

      <h3 style="margin-top:20px; margin-bottom:0; color:#0a3d62;">📚 Academic Information</h3>
      <select name="grade" required>
        <option value="">Select Grade Applying For</option>
        <?php for ($i=1; $i<=10; $i++): ?>
          <option>Grade <?= $i ?></option>
        <?php endfor; ?>
      </select>
      <input name="previous_school" placeholder="Previous School (if any)">

      <h3 style="margin-top:20px; margin-bottom:0; color:#0a3d62;">🏠 Address</h3>
      <textarea name="address" placeholder="Home Address" rows="2"></textarea>
      <input name="city" placeholder="City">

      <h3 style="margin-top:20px; margin-bottom:0; color:#0a3d62;">📣 Other</h3>
      <select name="hear_about">
        <option value="">How did you hear about us?</option>
        <option>Friend / Family</option>
        <option>Facebook</option>
        <option>Instagram</option>
        <option>Google Search</option>
        <option>Website</option>
        <option>Banner / Poster</option>
        <option>Other</option>
      </select>
      <textarea name="message" placeholder="Any additional note (optional)" rows="3"></textarea>

      <button type="submit" style="margin-top:20px;">Submit Application</button>
    </form>
  <?php endif; ?>
</section>
</body>
</html>
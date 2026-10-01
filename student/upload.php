<?php
require 'auth.php';
require '../includes/db.php';

$sid = $_SESSION['student_id'];
$success = ''; $error = '';

// Get current profile photo
$u = $pdo->prepare("SELECT profile_photo FROM student_users WHERE id = ?");
$u->execute([$sid]);
$curUser = $u->fetch();

$allowed = ['application/pdf','image/jpeg','image/png','image/jpg'];
$maxSize = 5 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // === PROFILE PHOTO UPLOAD ===
  if (!empty($_POST['upload_type']) && $_POST['upload_type'] === 'profile') {
    if (empty($_FILES['photo']['name'])) {
      $error = 'Please choose a photo to upload.';
    } else {
      $f = $_FILES['photo'];
      $allowedImg = ['image/jpeg','image/png','image/jpg'];

      if (!in_array(mime_content_type($f['tmp_name']), $allowedImg)) {
        $error = 'Profile photo must be JPG or PNG.';
      } elseif ($f['size'] > 2 * 1024 * 1024) {
        $error = 'Profile photo must be under 2 MB.';
      } else {
        $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
        $newName = 'profile_' . $sid . '_' . time() . '.' . $ext;
        $dest = __DIR__ . '/uploads/' . $newName;

        if (move_uploaded_file($f['tmp_name'], $dest)) {
          // Delete old photo
          if (!empty($curUser['profile_photo']) && file_exists(__DIR__ . '/uploads/' . $curUser['profile_photo'])) {
            unlink(__DIR__ . '/uploads/' . $curUser['profile_photo']);
          }
          $pdo->prepare("UPDATE student_users SET profile_photo = ? WHERE id = ?")->execute([$newName, $sid]);
          $success = '✅ Profile photo updated!';
          $curUser['profile_photo'] = $newName;
        } else {
          $error = 'Upload failed. Try again.';
        }
      }
    }
  }

  // === DOCUMENT UPLOAD ===
  if (!empty($_POST['doc_type'])) {
    $docType = trim($_POST['doc_type']);

    if (empty($_FILES['file']['name'])) {
      $error = 'Please choose a file to upload.';
    } else {
      $file    = $_FILES['file'];
      $size    = $file['size'];
      $tmpName = $file['tmp_name'];
      $origName = $file['name'];
      $mime    = mime_content_type($tmpName);

      if (!in_array($mime, $allowed)) {
        $error = 'Only PDF, JPG, or PNG files are allowed.';
      } elseif ($size > $maxSize) {
        $error = 'File too large. Max 5 MB.';
      } else {
        $ext      = pathinfo($origName, PATHINFO_EXTENSION);
        $safe     = preg_replace('/[^A-Za-z0-9_-]/', '_', $docType);
        $newName  = 'student' . $sid . '_' . $safe . '_' . time() . '.' . $ext;
        $dest     = __DIR__ . '/uploads/' . $newName;

        if (move_uploaded_file($tmpName, $dest)) {
          $stmt = $pdo->prepare("INSERT INTO documents (student_id, doc_type, file_name) VALUES (?,?,?)");
          $stmt->execute([$sid, $docType, $newName]);
          $success = '✅ Document uploaded successfully!';
        } else {
          $error = 'Upload failed. Check folder permissions.';
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Upload Documents | Watan School System</title>
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

<section style="max-width:640px;">
  <h2 style="text-align:center;">Upload Files</h2>
  <p class="section-sub">Manage your profile photo and admission documents.</p>

  <?php if ($error): ?><p class="alert error"><?= $error ?></p><?php endif; ?>
  <?php if ($success): ?><p class="alert success"><?= $success ?></p><?php endif; ?>

  <!-- ========== PROFILE PHOTO ========== -->
  <div class="card" style="text-align:center; padding:35px; margin-bottom:25px;">
    <h3 style="color:#0a3d62; margin-bottom:15px;">📸 Profile Photo</h3>

    <?php if (!empty($curUser['profile_photo'])): ?>
      <img src="uploads/<?= htmlspecialchars($curUser['profile_photo']) ?>" alt="Profile" 
           style="width:130px; height:130px; object-fit:cover; border-radius:50%; margin:0 auto 20px; display:block; border:5px solid #0a3d62; box-shadow:0 8px 20px rgba(10,61,98,.25);">
    <?php else: ?>
      <div style="width:130px; height:130px; border-radius:50%; background:#e5eaf0; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; font-size:50px; border:5px dashed #cbd5e1;">
        👤
      </div>
      <p style="color:#6b7c93; margin-bottom:15px;">No photo yet</p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="form-box" style="box-shadow:none; padding:0;">
      <input type="hidden" name="upload_type" value="profile">
      <input type="file" name="photo" accept=".jpg,.jpeg,.png" required>
      <small style="color:#6b7c93;">JPG or PNG · Max 2 MB</small>
      <button type="submit"><?= !empty($curUser['profile_photo']) ? 'Update Photo' : 'Upload Photo' ?></button>
    </form>
  </div>

  <!-- ========== DOCUMENTS ========== -->
  <div class="card" style="padding:35px;">
    <h3 style="color:#0a3d62; margin-bottom:15px; text-align:center;">📄 Upload Document</h3>

    <form method="POST" enctype="multipart/form-data" class="form-box" style="box-shadow:none; padding:0;">
      <select name="doc_type" required>
        <option value="">Select Document Type</option>
        <option>Birth Certificate</option>
        <option>Previous Marksheet</option>
        <option>Father's CNIC</option>
        <option>Transfer Certificate</option>
        <option>Other</option>
      </select>
      <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
      <small style="color:#6b7c93;">PDF, JPG, or PNG · Max 5 MB</small>
      <button type="submit">Upload Document</button>
    </form>
  </div>

  <p style="text-align:center; margin-top:20px; font-size:14px;">
    <a href="dashboard.php" style="color:#0a3d62; font-weight:600;">← Back to Dashboard</a>
  </p>
</section>
</body>
</html>
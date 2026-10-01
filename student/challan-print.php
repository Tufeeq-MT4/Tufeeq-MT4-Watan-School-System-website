<?php
require 'auth.php';
require '../includes/db.php';

$sid = $_SESSION['student_id'];
$challanId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
  SELECT c.*, su.fullname, su.email, su.phone,
         a.guardian_name, a.father_name, a.city, a.address
  FROM monthly_challans c
  JOIN student_users su ON su.id = c.student_id
  LEFT JOIN applications a ON a.student_id = su.id
  WHERE c.id = ? AND c.student_id = ?
");
$stmt->execute([$challanId, $sid]);
$c = $stmt->fetch();

if (!$c) die('Challan not found.');

$school = [
  'name'    => 'Watan School System',
  'tagline' => 'Education is Power',
  'branch'  => 'Darazinda',
  'address' => 'Watan School System, Darazinda',
  'phone'   => '0334 9976001',
  'email'   => 'watanschoolsystem956@gmail.com',
  'logo'    => '/watan-school/images/logo.png'
];

$bank = [
  'name'    => 'MCB Bank',
  'title'   => 'Watan School System',
  'iban'    => 'PK52 MUCB 0828 0330 6100 1747',
  'branch'  => 'Darazinda Branch'
];

$easypaisa = '0346 7857152';

$challanNo = 'WSS-' . str_pad($c['id'], 6, '0', STR_PAD_LEFT) . '-' . strtoupper(substr($c['month'],0,3)) . $c['year'];
$statusColor = $c['status'] === 'paid' ? '#22c55e' : ($c['status'] === 'pending' ? '#f59e0b' : '#ef4444');
?>
<!DOCTYPE html>
<html>
<head>
  <title>Challan #<?= $challanNo ?> | Watan School System</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
    body { background:#e5e7eb; padding: 30px 20px; color:#111; }
    .no-print {
      max-width:800px; margin:0 auto 20px; display:flex; justify-content:space-between; align-items:center;
      background:#fff; padding:15px 25px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,.08);
    }
    .no-print h3 { color:#0a3d62; font-size:18px; }
    .no-print button, .no-print a {
      padding:10px 22px; background:#0a3d62; color:#fff; border:none; border-radius:8px;
      font-weight:600; cursor:pointer; text-decoration:none; font-size:14px;
    }
    .no-print a { background:#64748b; margin-left:8px; }
    .challan {
      max-width:800px; margin:auto; background:#fff; padding:0;
      border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,.1); overflow:hidden;
    }
    .header {
      background: linear-gradient(135deg, #0a3d62 0%, #1e5a8a 40%, #0a3d62 80%);
      background-size: 200% 200%;
      animation: holoShift 8s ease infinite;
      padding: 22px 28px; color:#fff;
      display:flex; align-items:center; gap:20px;
      position:relative; overflow:hidden;
    }
    @keyframes holoShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    .header::after {
      content:"";
      position:absolute; top:0; left:-100%; width:100%; height:100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,.15), transparent);
      animation: shine 3s infinite;
    }
    @keyframes shine {
      0% { left:-100%; }
      100% { left: 100%; }
    }
    .header .logo-wrap {
      width:80px; height:80px; background:#fff; border-radius:50%; padding:6px;
      flex-shrink:0; box-shadow:0 4px 12px rgba(0,0,0,.2);
      position:relative; z-index:2;
    }
    .header .logo-wrap img { width:100%; height:100%; object-fit:contain; }
    .header .info { position:relative; z-index:2; }
    .header h1 { font-size:26px; margin-bottom:2px; letter-spacing:.5px; }
    .header .tagline { font-size:13px; color:#f6b93b; font-weight:600; letter-spacing:1px; }
    .header .contact { font-size:12px; margin-top:6px; opacity:.9; }
    .header .challan-no {
      margin-left:auto; text-align:right; position:relative; z-index:2;
    }
    .header .challan-no span { display:block; font-size:11px; opacity:.85; letter-spacing:1px; }
    .header .challan-no strong { font-size:15px; color:#f6b93b; }
    .body-content { padding: 30px 35px; }
    .title-bar {
      background:#f6b93b; color:#0a3d62; padding:12px 20px; border-radius:6px;
      text-align:center; font-weight:700; letter-spacing:1.5px; font-size:14px;
      margin-bottom:24px; text-transform:uppercase;
    }
    .info-grid {
      display:grid; grid-template-columns:1fr 1fr; gap:12px 30px; margin-bottom:24px;
    }
    .info-grid div { border-bottom:1px dashed #cbd5e1; padding:6px 0; display:flex; justify-content:space-between; }
    .info-grid span:first-child { color:#6b7c93; font-size:13px; }
    .info-grid span:last-child  { font-weight:600; color:#0a3d62; font-size:14px; }
    .amount-box {
      background:#0a3d62; color:#fff; padding:22px 26px; border-radius:10px;
      display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;
      position:relative; overflow:hidden;
    }
    .amount-box::before {
      content:""; position:absolute; top:0; right:0; width:120px; height:100%;
      background: linear-gradient(135deg, rgba(246,185,59,.3), transparent);
    }
    .amount-box .label { font-size:12px; letter-spacing:2px; opacity:.85; }
    .amount-box .sub { font-size:13px; margin-top:4px; color:#cfd8e3; }
    .amount-box .value { font-size:32px; font-weight:800; color:#f6b93b; position:relative; z-index:1; }
    .payment-grid {
      display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;
    }
    .payment-box {
      background:#f7f9fc; padding:16px 18px; border-radius:10px; border-left:5px solid #0a3d62;
    }
    .payment-box h4 { color:#0a3d62; font-size:13px; margin-bottom:8px; text-transform:uppercase; letter-spacing:1px; }
    .payment-box p { font-size:13px; color:#2c3e50; line-height:1.7; }
    .payment-box code {
      background:#e2e8f0; padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600;
      display:inline-block; margin-top:2px; word-break:break-all;
    }
    .status-badge {
      display:inline-block; padding:6px 16px; color:#fff; border-radius:20px;
      font-size:11px; font-weight:700; letter-spacing:1px; background:<?= $statusColor ?>;
    }
    .notes {
      font-size:12px; color:#6b7c93; line-height:1.8; margin-top:20px;
      padding-top:18px; border-top:1px dashed #cbd5e1;
    }
    .notes strong { color:#0a3d62; }
    .footer-signatures {
      display:flex; justify-content:space-between; margin-top:35px; padding-top:20px;
      border-top:2px solid #0a3d62;
    }
    .footer-signatures div { text-align:center; }
    .footer-signatures p { font-size:11px; color:#6b7c93; margin-top:8px; }
    .footer-signatures .line { width:140px; border-top:1px solid #111; margin-bottom:5px; }
    .school-footer {
      background:#0a3d62; color:#fff; padding:14px 28px; text-align:center;
      font-size:11px; letter-spacing:.5px;
    }
    .school-footer strong { color:#f6b93b; }
    @media print {
      body { background:#fff; padding:0; }
      .no-print { display:none; }
      .challan { box-shadow:none; max-width:100%; border-radius:0; }
      .header { animation:none; }
    }
  </style>
</head>
<body>

<div class="no-print">
  <h3>Challan: <?= $challanNo ?></h3>
  <div>
    <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    <a href="monthly-fees.php">← Back</a>
  </div>
</div>

<div class="challan">

  <div class="header">
    <div class="logo-wrap">
      <img src="<?= $school['logo'] ?>" alt="Logo">
    </div>
    <div class="info">
      <h1><?= htmlspecialchars($school['name']) ?></h1>
      <div class="tagline">★ <?= htmlspecialchars($school['tagline']) ?> ★</div>
      <div class="contact">📍 <?= htmlspecialchars($school['branch']) ?> · 📞 <?= htmlspecialchars($school['phone']) ?> · ✉ <?= htmlspecialchars($school['email']) ?></div>
    </div>
    <div class="challan-no">
      <span>CHALLAN NO.</span>
      <strong><?= $challanNo ?></strong>
    </div>
  </div>

  <div class="body-content">

    <div class="title-bar">Monthly Fee Challan — <?= strtoupper($c['month']) ?> <?= $c['year'] ?></div>

    <div class="info-grid">
      <div><span>Student Name:</span> <span><?= htmlspecialchars($c['fullname']) ?></span></div>
      <div><span>Roll / ID:</span> <span>#<?= $c['student_id'] ?></span></div>
      <div><span>Grade:</span> <span><?= htmlspecialchars($c['grade']) ?></span></div>
      <div><span>Email:</span> <span><?= htmlspecialchars($c['email']) ?></span></div>
      <div><span>Father/Guardian:</span> <span><?= htmlspecialchars($c['father_name'] ?: ($c['guardian_name'] ?: '—')) ?></span></div>
      <div><span>Phone:</span> <span><?= htmlspecialchars($c['phone'] ?: '—') ?></span></div>
      <div><span>Due Date:</span> <span><?= date('d M Y', strtotime($c['due_date'])) ?></span></div>
      <div><span>Status:</span> <span><span class="status-badge"><?= strtoupper($c['status']) ?></span></span></div>
    </div>

    <div class="amount-box">
      <div>
        <div class="label">TOTAL PAYABLE</div>
        <div class="sub">Monthly Fee — <?= htmlspecialchars($c['month']) ?> <?= $c['year'] ?></div>
      </div>
      <div class="value">Rs. <?= number_format($c['amount']) ?></div>
    </div>

    <div class="payment-grid">
      <div class="payment-box">
        <h4>📱 EasyPaisa</h4>
        <p>
          <strong>Account Number:</strong><br>
          <code><?= htmlspecialchars($easypaisa) ?></code><br>
          <strong>Account Title:</strong><br>
          <?= htmlspecialchars($bank['title']) ?>
        </p>
      </div>

      <div class="payment-box">
        <h4>🏦 Bank Transfer</h4>
        <p>
          <strong><?= htmlspecialchars($bank['name']) ?></strong><br>
          <strong>IBAN:</strong><br>
          <code><?= htmlspecialchars($bank['iban']) ?></code><br>
          <strong>Branch:</strong> <?= htmlspecialchars($bank['branch']) ?>
        </p>
      </div>
    </div>

    <div class="notes">
      <strong>📌 Instructions:</strong><br>
      1. Pay this challan via EasyPaisa or MCB Bank before the due date.<br>
      2. You may also pay in cash at the school office (Mon–Sat, 9 AM – 5 PM).<br>
      3. After payment, upload the receipt in your Student Portal → Monthly Fees section.<br>
      4. Keep this challan safe as proof of payment.<br>
      5. Late payments may incur a fine as per school policy.
    </div>

    <div class="footer-signatures">
      <div><div class="line"></div><p>Depositor's Signature</p></div>
      <div><div class="line"></div><p>Bank Stamp</p></div>
      <div><div class="line"></div><p>Principal's Signature</p></div>
    </div>

  </div>

  <div class="school-footer">
    <strong><?= htmlspecialchars($school['name']) ?></strong> · <?= htmlspecialchars($school['address']) ?> · <?= htmlspecialchars($school['phone']) ?>
  </div>

</div>

</body>
</html>
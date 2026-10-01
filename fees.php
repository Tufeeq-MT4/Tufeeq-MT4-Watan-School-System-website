<?php
require 'includes/db.php';
include 'includes/header.php';

$fees = $pdo->query("SELECT * FROM fees ORDER BY id")->fetchAll();

$payment = [
  'easypaisa' => '0346 7857152',
  'bank_name' => 'MCB Bank',
  'title'     => 'Watan School System',
  'iban'      => 'PK52 MUCB 0828 0330 6100 1747',
  'branch'    => 'Darazinda Branch'
];
?>

<style>
  /* ============ FULL-WIDTH BIG BOX ============ */
  .fees-container {
    width: 100%;
    max-width: 1600px;           /* much wider */
    margin: 40px auto;
    padding: 0 15px;
  }

  .fees-box {
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 25px 70px rgba(10,61,98,.15);
    padding: 55px 60px;          /* more inner space */
    position: relative;
    overflow: hidden;
    border: 1px solid #eef2f6;
  }
  .fees-box::before {
    content: "";
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 6px;
    background: linear-gradient(90deg, #0a3d62 0%, #f6b93b 50%, #0a3d62 100%);
  }
  .fees-box::after {
    content: "";
    position: absolute;
    bottom: -150px; right: -150px;
    width: 400px; height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(246,185,59,.08) 0%, transparent 70%);
    pointer-events: none;
  }

  .fees-header {
    text-align: center;
    margin-bottom: 45px;
    position: relative;
    z-index: 2;
  }
  .fees-header h2 {
    font-size: 48px;
    color: #0a3d62;
    margin-bottom: 14px;
    font-family: 'Playfair Display', serif;
  }
  .fees-header p {
    color: #6b7c93;
    font-size: 18px;
    max-width: 700px;
    margin: auto;
  }
  .fees-header::after {
    content: "";
    display: block;
    width: 90px;
    height: 5px;
    background: #f6b93b;
    border-radius: 2px;
    margin: 22px auto 0;
  }

  /* ============ BIG TABLE ============ */
  .fee-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 20px;              /* bigger text */
    position: relative;
    z-index: 2;
    table-layout: fixed;           /* even column widths */
  }

  .fee-table thead th {
    background: linear-gradient(135deg, #0a3d62 0%, #1e5a8a 100%);
    color: #fff;
    padding: 32px 35px;            /* taller header */
    text-align: left;
    font-size: 16px;               /* bigger header text */
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    position: relative;
  }
  .fee-table thead th:first-child {
    border-top-left-radius: 16px;
    width: 32%;                    /* class column bigger */
  }
  .fee-table thead th:last-child {
    border-top-right-radius: 16px;
  }
  .fee-table thead th:not(:first-child) {
    text-align: center;            /* center the numbers */
  }
  .fee-table thead th:not(:last-child)::after {
    content: "";
    position: absolute;
    right: 0;
    top: 25%;
    height: 50%;
    width: 1px;
    background: rgba(255,255,255,.2);
  }

  .fee-table tbody tr {
    background: #fff;
    transition: all .3s ease;
  }
  .fee-table tbody tr:nth-child(even) {
    background: #fafbfd;
  }
  .fee-table tbody tr:hover {
    background: linear-gradient(90deg, #f0f7ff 0%, #fffbf0 100%);
    box-shadow: 0 8px 25px rgba(10,61,98,.10);
  }

  .fee-table tbody td {
    padding: 28px 35px;            /* taller rows */
    color: #2c3e50;
    font-weight: 500;
    border-bottom: 1px solid #f0f4f8;
    font-size: 19px;               /* bigger body text */
  }
  .fee-table tbody td:first-child {
    font-weight: 700;
    color: #0a3d62;
    font-size: 22px;               /* bigger class names */
    display: flex;
    align-items: center;
    gap: 18px;
  }
  .fee-table tbody td:not(:first-child) {
    text-align: center;            /* center amounts */
  }

  /* Class badge circle */
  .class-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 54px;
    height: 54px;
    border-radius: 14px;
    background: linear-gradient(135deg, #0a3d62, #1e5a8a);
    color: #f6b93b;
    font-size: 18px;
    font-weight: 800;
    flex-shrink: 0;
    box-shadow: 0 6px 15px rgba(10,61,98,.25);
  }

  .fee-amount {
    font-weight: 700;
    font-size: 20px;               /* bigger amount text */
    display: inline-flex;
    align-items: center;
    gap: 6px;
    letter-spacing: .5px;
  }
  .fee-amount.monthly { color: #0a3d62; }
  .fee-amount.admission { color: #16a34a; }
  .fee-amount.exam { color: #7c3aed; }

  .fee-amount::before {
    content: "Rs.";
    font-size: 14px;
    opacity: .65;
    font-weight: 600;
  }

  /* ============ FOOTER NOTE ============ */
  .fees-footer-note {
    text-align: center;
    margin-top: 40px;
    padding: 25px 30px;
    background: linear-gradient(90deg, #f0f7ff 0%, #fffbf0 100%);
    border-radius: 16px;
    border-left: 6px solid #f6b93b;
    color: #4a5568;
    font-size: 17px;
    position: relative;
    z-index: 2;
  }
  .fees-footer-note strong { color: #0a3d62; }

  /* ============ PAYMENT SECTION ============ */
  .pay-section {
    max-width: 1600px;
    margin: 60px auto 40px;
    padding: 0 15px;
  }
  .pay-section h2 {
    text-align: center;
    font-size: 42px;
    color: #0a3d62;
    margin-bottom: 14px;
    font-family: 'Playfair Display', serif;
  }
  .pay-section > p {
    text-align: center;
    color: #6b7c93;
    margin-bottom: 50px;
    font-size: 18px;
  }

  .pay-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 30px;
  }
  .pay-card {
    background: #fff;
    padding: 45px 35px;
    border-radius: 20px;
    box-shadow: 0 15px 45px rgba(10,61,98,.10);
    position: relative;
    overflow: hidden;
    transition: all .35s ease;
    border-top: 5px solid #0a3d62;
  }
  .pay-card:nth-child(2) { border-top-color: #f6b93b; }
  .pay-card:nth-child(3) { border-top-color: #16a34a; }
  .pay-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 25px 55px rgba(10,61,98,.18);
  }
  .pay-card::after {
    content: "";
    position: absolute;
    bottom: -60px; right: -60px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(10,61,98,.04);
  }
  .pay-card .pay-icon {
    font-size: 52px;
    margin-bottom: 22px;
    display: block;
  }
  .pay-card h3 {
    color: #0a3d62;
    font-size: 24px;
    margin-bottom: 20px;
    font-family: 'Playfair Display', serif;
  }
  .pay-card p {
    font-size: 16px;
    color: #4a5568;
    line-height: 2;
  }
  .pay-card code {
    background: #f0f4f8;
    padding: 12px 18px;
    border-radius: 10px;
    display: inline-block;
    font-size: 17px;
    font-weight: 700;
    color: #0a3d62;
    margin-top: 8px;
    letter-spacing: .5px;
    border: 1px dashed #cbd5e1;
  }

  /* ============ RESPONSIVE ============ */
  @media (max-width: 900px) {
    .fees-box { padding: 35px 20px; border-radius: 18px; }
    .fees-header h2 { font-size: 32px; }
    .fee-table { font-size: 15px; }
    .fee-table thead th { padding: 16px 12px; font-size: 12px; letter-spacing: 1px; }
    .fee-table tbody td { padding: 18px 12px; font-size: 15px; }
    .fee-table tbody td:first-child { font-size: 16px; gap: 10px; }
    .class-badge { width: 40px; height: 40px; font-size: 14px; }
    .fee-amount { font-size: 16px; }
    .pay-section h2 { font-size: 30px; }
  }
  @media (max-width: 600px) {
    .fee-table thead th { padding: 12px 8px; font-size: 10px; }
    .fee-table tbody td { padding: 14px 8px; font-size: 13px; }
    .fee-table tbody td:first-child { font-size: 14px; gap: 6px; }
    .class-badge { width: 32px; height: 32px; font-size: 12px; border-radius: 8px; }
    .fee-amount { font-size: 14px; }
  }
</style>

<!-- HERO -->
<div class="hero" style="padding:100px 20px; background: linear-gradient(rgba(10,61,98,.75), rgba(10,61,98,.85)), url('https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1600') center/cover;">
  <h2>Fee Structure</h2>
  <p>Transparent fees for every class. No hidden charges.</p>
</div>

<!-- BIG FEE BOX -->
<div class="fees-container">
  <div class="fees-box">

    <div class="fees-header">
      <h2>Class-wise Fees</h2>
      <p>Monthly, admission, and exam fees for all grades — 2026</p>
    </div>

    <table class="fee-table">
      <thead>
        <tr>
          <th>Class</th>
          <th>Monthly Fee</th>
          <th>Admission Fee</th>
          <th>Exam Fee</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($fees as $f): ?>
          <tr>
            <td>
              <span class="class-badge"><?= str_replace(['Grade ', 'grade '], '', $f['class_name']) ?></span>
              <?= htmlspecialchars($f['class_name']) ?>
            </td>
            <td><span class="fee-amount monthly"><?= number_format($f['monthly_fee']) ?></span></td>
            <td><span class="fee-amount admission"><?= number_format($f['admission_fee']) ?></span></td>
            <td><span class="fee-amount exam"><?= number_format($f['exam_fee']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="fees-footer-note">
      💡 <strong>Note:</strong> Fees are payable at the start of each month. Contact office for sibling discounts.
    </div>

  </div>
</div>

<!-- PAYMENT METHODS -->
<div class="pay-section">
  <h2>How to Pay Fees</h2>
  <p>Pay via EasyPaisa, bank transfer, or in person.</p>

  <div class="pay-grid">

    <div class="pay-card">
      <span class="pay-icon">📱</span>
      <h3>EasyPaisa</h3>
      <p>
        <strong>Account Number:</strong><br>
        <code><?= $payment['easypaisa'] ?></code><br><br>
        <strong>Account Title:</strong><br>
        <?= $payment['title'] ?>
      </p>
    </div>

    <div class="pay-card">
      <span class="pay-icon">🏦</span>
      <h3>Bank Transfer</h3>
      <p>
        <strong>Bank:</strong> <?= $payment['bank_name'] ?><br>
        <strong>Account Title:</strong> <?= $payment['title'] ?><br>
        <strong>IBAN:</strong><br>
        <code style="font-size:13px; word-break:break-all;"><?= $payment['iban'] ?></code><br>
        <strong>Branch:</strong> <?= $payment['branch'] ?>
      </p>
    </div>

    <div class="pay-card">
      <span class="pay-icon">🏫</span>
      <h3>In-Person</h3>
      <p>
        Visit the school office:<br><br>
        <strong>Monday – Saturday</strong><br>
        9:00 AM – 5:00 PM<br><br>
        Payment accepted in cash or cheque.
      </p>
    </div>

  </div>
</div>

<!-- CTA -->
<div class="cta">
  <h2>Need Help with Fees?</h2>
  <p>Our accounts office is here to help.</p>
  <a href="contact.php">Contact Us →</a>
</div>

<?php include 'includes/footer.php'; ?>
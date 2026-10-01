<?php
require 'includes/db.php';
require 'includes/mailer.php';
include 'includes/header.php';

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name    = trim($_POST['name'] ?? '');
  $email   = trim($_POST['email'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $message = trim($_POST['message'] ?? '');

  if (!$name || !$email || !$message) {
    $error = 'Please fill all required fields.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email address.';
  } else {
    // Save to database
    $stmt = $pdo->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?,?,?,?)");
    $stmt->execute([$name, $email, $subject, $message]);

    // ===== SEND EMAIL TO SCHOOL =====
    $schoolBody = "
      <h2 style='color:#0a3d62;'>📩 New Contact Form Message</h2>
      <table style='border-collapse:collapse; width:100%; max-width:600px;'>
        <tr><td style='padding:8px; background:#f0f4f8;'><strong>Name:</strong></td><td style='padding:8px;'>" . htmlspecialchars($name) . "</td></tr>
        <tr><td style='padding:8px; background:#f0f4f8;'><strong>Email:</strong></td><td style='padding:8px;'>" . htmlspecialchars($email) . "</td></tr>
        <tr><td style='padding:8px; background:#f0f4f8;'><strong>Subject:</strong></td><td style='padding:8px;'>" . htmlspecialchars($subject ?: '(No subject)') . "</td></tr>
        <tr><td style='padding:8px; background:#f0f4f8;'><strong>Message:</strong></td><td style='padding:8px;'>" . nl2br(htmlspecialchars($message)) . "</td></tr>
      </table>
      <p style='color:#6b7c93; font-size:13px; margin-top:20px;'>Sent from watanschool.edu contact form</p>
    ";
    send_mail(
      'watanschoolsystem956@gmail.com',
      'Watan School',
      'New Contact: ' . ($subject ?: 'Message from ' . $name),
      $schoolBody
    );

    // ===== SEND AUTO-REPLY TO VISITOR =====
    $replyBody = "
      <h2 style='color:#0a3d62;'>Thank you for contacting Watan School</h2>
      <p>Hi " . htmlspecialchars($name) . ",</p>
      <p>We have received your message and will get back to you within 24 hours.</p>
      <p style='background:#f0f4f8; padding:15px; border-left:4px solid #f6b93b; margin:20px 0;'>
        <strong>Your message:</strong><br>" . nl2br(htmlspecialchars($message)) . "
      </p>
      <p>If your matter is urgent, please call us at <strong>+92 000 0000000</strong>.</p>
      <p style='margin-top:30px;'>Best regards,<br><strong>Watan School</strong></p>
    ";
    send_mail($email, $name, 'We received your message — Watan School', $replyBody);

    $success = '✅ Thank you! We have received your message and will reply soon.';
  }
}
?>

<div class="hero" style="padding:100px 20px; background: linear-gradient(rgba(10,61,98,.70), rgba(10,61,98,.80)), url('https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?w=1600') center/cover;">
  <h2>Contact Us</h2>
  <p>We'd love to hear from you. Reach out anytime.</p>
</div>

<section>
  <h2>Get in Touch</h2>
  <p class="section-sub">Multiple ways to reach our team.</p>

  <div class="cards">
    <div class="card" style="text-align:center;">
      <span class="card-icon">📍</span>
      <h3>Visit Us</h3>
      <p>Watan Campus<br>Your City, Pakistan</p>
    </div>
    <div class="card" style="text-align:center;">
      <span class="card-icon">📞</span>
      <h3>Call Us</h3>
      <p>+92 0334 9976001<br>Mon–Sat · 9:00 AM – 5:00 PM</p>
    </div>
    <div class="card" style="text-align:center;">
      <span class="card-icon">✉️</span>
      <h3>Email Us</h3>
      <p>watanschoolsystem956@gmail.com</p>
    </div>
    <div class="card" style="text-align:center;">
      <span class="card-icon">🕐</span>
      <h3>Office Hours</h3>
      <p>Monday – Saturday<br>9:00 AM – 5:00 PM</p>
    </div>
  </div>
</section>

<section style="max-width:900px;">
  <h2>Send Us a Message</h2>
  <p class="section-sub">Fill the form below and we'll get back to you within 24 hours.</p>

  <?php if ($error): ?><p class="alert error"><?= $error ?></p><?php endif; ?>
  <?php if ($success): ?><p class="alert success"><?= $success ?></p><?php endif; ?>

  <form method="POST" class="form-box">
    <input name="name" placeholder="Your Full Name" required>
    <input name="email" type="email" placeholder="Your Email" required>
    <input name="subject" placeholder="Subject (optional)">
    <textarea name="message" placeholder="Your Message" rows="6" required></textarea>
    <button type="submit">Send Message</button>
  </form>
</section>

<section style="max-width:1200px;">
  <h2>Find Us</h2>
  <p class="section-sub">Visit our campus anytime.</p>

  <div style="border-radius:18px; overflow:hidden; box-shadow:0 10px 30px rgba(10,61,98,.15);">
    <iframe 
      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3401.3795!2d74.3436!3d31.5204!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzHCsDMxJzEzLjQiTiA3NMKwMjAnMzYuOSJF!5e0!3m2!1sen!2s!4v1600000000000"
      width="100%" 
      height="400" 
      style="border:0;" 
      allowfullscreen="" 
      loading="lazy">
    </iframe>
  </div>
</section>

<div class="cta">
  <h2>Ready to Join Us?</h2>
  <p>Start your application today — it only takes a few minutes.</p>
  <a href="apply.php">Apply Online →</a>
</div>

<?php include 'includes/footer.php'; ?>
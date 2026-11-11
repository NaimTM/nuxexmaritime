<?php
$page_title = "Contact Nuzex Maritime | 24/7 Marine Manning Support";
$meta_desc  = "Contact Nuzex Maritime in Dhaka, Bangladesh. 24/7 emergency crew support, WhatsApp, and email.";
require __DIR__ . '/includes/header.php';

$success = null; $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check($_POST['csrf'] ?? null);
  $name    = trim($_POST['name'] ?? '');
  $email   = trim($_POST['email'] ?? '');
  $phone   = trim($_POST['phone'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $message = trim($_POST['message'] ?? '');

  if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$message) {
    $error = 'Please provide name, valid email, and message.';
  } else {
    $pdo->prepare("INSERT INTO contact_messages (name,email,phone,subject,message) VALUES (?,?,?,?,?)")
        ->execute([$name,$email,$phone,$subject,$message]);
    $success = 'Message sent. We\'ll get back to you shortly.';
  }
}
?>
<section class="hero" style="padding:70px 0 50px">
  <div class="container" style="max-width:800px">
    <h1>Contact Us</h1>
    <p class="lead">24/7 support for emergency crew changes and general inquiries.</p>
  </div>
</section>

<section class="section" style="padding-top:40px">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 1.2fr;gap:2rem" class="contact-grid">

      <div>
        <div class="service-card" style="margin-bottom:1rem">
          <div class="service-icon">📍</div>
          <h3>Head Office</h3>
          <p>Dhaka, Bangladesh<br><span class="muted">[Full address to be added]</span></p>
        </div>
        <div class="service-card" style="margin-bottom:1rem">
          <div class="service-icon">📞</div>
          <h3>24/7 Operations</h3>
          <p><a href="tel:<?= e(WHATSAPP) ?>"><?= e(WHATSAPP) ?></a></p>
        </div>
        <div class="service-card" style="margin-bottom:1rem">
          <div class="service-icon">✉️</div>
          <h3>Email</h3>
          <p><a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a></p>
        </div>
        <div class="service-card">
          <div class="service-icon">💬</div>
          <h3>WhatsApp</h3>
          <p><a href="https://wa.me/<?= preg_replace('/\D/','',WHATSAPP) ?>" target="_blank">Chat with us</a></p>
        </div>
      </div>

      <div class="form-card">
        <h3>Send a Message</h3>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

        <form method="post" style="margin-top:1rem">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <div class="form-row">
            <div class="form-group"><label>Name *</label><input name="name" required></div>
            <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Phone</label><input name="phone"></div>
            <div class="form-group"><label>Subject</label><input name="subject"></div>
          </div>
          <div class="form-group">
            <label>Message *</label>
            <textarea name="message" required></textarea>
          </div>
          <button class="btn btn-teal btn-lg">Send Message</button>
        </form>
      </div>
    </div>

    <div style="margin-top:3rem;border-radius:12px;overflow:hidden;box-shadow:var(--shadow)">
      <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d233667.5!2d90.279!3d23.780!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sDhaka!5e0!3m2!1sen!2sbd!4v1"
              width="100%" height="360" style="border:0;display:block" loading="lazy"></iframe>
    </div>
  </div>
</section>

<style>
@media(max-width:800px){ .contact-grid{grid-template-columns:1fr !important} }
</style>
<?php require __DIR__ . '/includes/footer.php'; ?>
<?php
$page_title = "Privacy Policy | Nuzex Maritime";
$meta_desc  = "How Nuzex Maritime collects, uses, and protects your personal data.";
require __DIR__ . '/includes/header.php';
?>
<section class="section" style="padding-top:60px">
  <div class="container" style="max-width:800px">
    <h1>Privacy Policy</h1>
    <p class="muted">Last updated: <?= date('F Y') ?></p>

    <h2 style="margin-top:2rem">1. What We Collect</h2>
    <p>We collect the information you provide when you register as a seafarer or submit a crew request: name, email, phone, nationality, rank, certificate numbers, CV, and any supporting documents.</p>

    <h2 style="margin-top:2rem">2. How We Use It</h2>
    <p>Your data is used solely for recruitment purposes — matching you with shipowners and processing your applications. Shipowners only receive your CV when you actively apply to their job.</p>

    <h2 style="margin-top:2rem">3. Data Sharing</h2>
    <p>We do not sell your data. We share it only with shipowners and managers you apply to, and with service providers essential to the placement process (travel, medical, training).</p>

    <h2 style="margin-top:2rem">4. Your Rights</h2>
    <p>You may request a copy of your data, correct it, or request deletion at any time by emailing <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a>.</p>

    <h2 style="margin-top:2rem">5. Security</h2>
    <p>We use encrypted connections (HTTPS), hashed passwords, and restricted access controls to protect your data.</p>

    <h2 style="margin-top:2rem">6. Cookies</h2>
    <p>We use minimal cookies for session management and analytics. See our <a href="/cookies.php">Cookies Policy</a>.</p>

    <h2 style="margin-top:2rem">7. Contact</h2>
    <p>For any privacy concerns, contact us at <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a>.</p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
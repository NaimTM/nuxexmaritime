<?php
$page_title = "Seafarer Jobs & Registration | Nuzex Maritime";
$meta_desc  = "Register free with Nuzex Maritime. Apply for officer and rating jobs worldwide. Upload your CV and get matched with top shipowners.";
require __DIR__ . '/includes/header.php';
?>
<section class="hero" style="padding:80px 0 60px">
  <div class="container" style="max-width:800px">
    <h1>Your Next Contract<br>Starts Here.</h1>
    <p class="lead">Register free with Nuzex Maritime. Upload your CV and certificates, get matched to live vacancies, and apply with one click.</p>
    <div class="hero-actions">
      <a href="/register.php" class="btn btn-gold btn-lg">Register Free →</a>
      <a href="/jobs.php" class="btn btn-ghost-light btn-lg">Browse Jobs</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <h2>Why Join Nuzex</h2>
    <div class="why-grid">
      <div class="why-item"><div class="icon">🆓</div><div><h4>Free Forever</h4><p>No registration fees, no application fees. Always free for seafarers.</p></div></div>
      <div class="why-item"><div class="icon">🎯</div><div><h4>Real Jobs</h4><p>Every vacancy is verified before it's posted. No fake listings.</p></div></div>
      <div class="why-item"><div class="icon">📱</div><div><h4>One-Click Apply</h4><p>Apply with your saved CV — no re-uploading every time.</p></div></div>
      <div class="why-item"><div class="icon">🔔</div><div><h4>Job Alerts</h4><p>Get notified when jobs matching your rank are posted.</p></div></div>
      <div class="why-item"><div class="icon">🎧</div><div><h4>24/7 Support</h4><p>WhatsApp us any time — from shortlisting to sign-on.</p></div></div>
      <div class="why-item"><div class="icon">🔒</div><div><h4>Your Data, Protected</h4><p>GDPR-aligned handling. Your CV is shared only with your consent.</p></div></div>
    </div>

    <h2 style="margin-top:4rem">What You'll Need</h2>
    <div class="services-grid">
      <?php foreach ([
        ['🪪','Valid Passport','6+ months validity'],
        ['📜','COC','Certificate of Competency'],
        ['📋','COP','Certificate of Proficiency'],
        ['🎓','STCW','Basic Safety Training'],
        ['🏥','Medical','Fitness Certificate'],
        ['💉','Vaccinations','Yellow fever, others as required'],
        ['📓','CDC','Continuous Discharge Certificate'],
        ['📄','CV','Updated with sea service'],
      ] as [$i,$t,$d]): ?>
        <div class="service-card">
          <div class="service-icon"><?= $i ?></div>
          <h3><?= e($t) ?></h3>
          <p><?= e($d) ?></p>
        </div>
      <?php endforeach; ?>
    </div>

    <h2 style="margin-top:4rem">How It Works</h2>
    <div class="steps">
      <div class="step"><div class="step-num">1</div><h4>Register</h4><p>Create your free profile in 2 minutes.</p></div>
      <div class="step"><div class="step-num">2</div><h4>Upload CV</h4><p>Add your CV and certificates once.</p></div>
      <div class="step"><div class="step-num">3</div><h4>Get Matched</h4><p>We match you to jobs fitting your rank.</p></div>
      <div class="step"><div class="step-num">4</div><h4>Apply</h4><p>One click sends your CV to the shipowner.</p></div>
      <div class="step"><div class="step-num">5</div><h4>Interview</h4><p>Shortlisted? We arrange the interview.</p></div>
      <div class="step"><div class="step-num">6</div><h4>Join</h4><p>Travel and documents handled. Off you go.</p></div>
    </div>

    <div style="text-align:center;margin-top:4rem">
      <a href="/register.php" class="btn btn-teal btn-lg">Register as Seafarer</a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
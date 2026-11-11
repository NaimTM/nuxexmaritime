<?php
$page_title = "Hire Qualified Ship Crew | Nuzex Maritime";
$meta_desc  = "Source STCW-certified officers and ratings across all vessel types. Request crew from Nuzex Maritime in 24 hours.";
require __DIR__ . '/includes/header.php';

$ranks   = ['Master','Chief Officer','2nd Officer','3rd Officer','Chief Engineer','2nd Engineer','3rd Engineer','4th Engineer','ETO','Bosun','AB','Oiler','Fitter','Cook','Steward','Messman'];
$vessels = ['Oil Tanker','Chemical Tanker','Bulk Carrier','Container','General Cargo','Offshore','Cruise','Tug','Dredger'];
?>
<section class="hero" style="padding:80px 0 60px">
  <div class="container" style="max-width:800px">
    <h1>Crew You Can Count On.<br>Delivered On Time.</h1>
    <p class="lead">From single-rank replacements to full crews, Nuzex Maritime sources, screens, and delivers STCW-certified officers and ratings for vessels worldwide.</p>
    <div class="hero-actions">
      <a href="/request-crew.php" class="btn btn-teal btn-lg">Request Crew →</a>
      <a href="/for-shipowners-register.php" class="btn btn-teal btn-lg">Register Your Fleet →</a>
      <a href="/contact.php" class="btn btn-ghost-light btn-lg">Talk to Us</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <h2>What We Solve</h2>
    <div class="why-grid">
      <div class="why-item"><div class="icon">⚡</div><div><h4>Slow manning agents</h4><p>We respond to crew requests within 24 hours, shortlists in 48–72.</p></div></div>
      <div class="why-item"><div class="icon">🕵️</div><div><h4>Unverified certificates</h4><p>Every COC, COP, STCW, and CDC is checked against the source.</p></div></div>
      <div class="why-item"><div class="icon">💸</div><div><h4>Hidden fees</h4><p>One clear price. No surprise commissions on top.</p></div></div>
      <div class="why-item"><div class="icon">🌐</div><div><h4>Hard-to-fill ranks</h4><p>Access to Bangladeshi and international talent across senior ranks.</p></div></div>
    </div>

    <h2 style="margin-top:4rem">Ranks We Supply</h2>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1rem">
      <?php foreach ($ranks as $r): ?>
        <span class="job-tag" style="padding:.4rem .9rem"><?= e($r) ?></span>
      <?php endforeach; ?>
    </div>

    <h2 style="margin-top:3rem">Vessel Types We Crew</h2>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1rem">
      <?php foreach ($vessels as $v): ?>
        <span class="job-tag" style="padding:.4rem .9rem;background:rgba(212,175,55,.12);color:#8a6d1c"><?= e($v) ?></span>
      <?php endforeach; ?>
    </div>

    <h2 style="margin-top:4rem">Our Process</h2>
    <div class="steps">
      <div class="step"><div class="step-num">1</div><h4>Request</h4><p>Submit your crew request with ranks, vessel, and joining date.</p></div>
      <div class="step"><div class="step-num">2</div><h4>Source</h4><p>We match against our verified crew database and partner networks.</p></div>
      <div class="step"><div class="step-num">3</div><h4>Screen</h4><p>Certificates, references, medical, and background verified.</p></div>
      <div class="step"><div class="step-num">4</div><h4>Shortlist</h4><p>You receive CVs and interview-ready candidates within 24–72h.</p></div>
      <div class="step"><div class="step-num">5</div><h4>Join</h4><p>Documents, travel, and crew change handled end-to-end.</p></div>
      <div class="step"><div class="step-num">6</div><h4>Support</h4><p>24/7 support throughout the contract — any port, any time.</p></div>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container">
    <h2>Ready to Crew Up?</h2>
    <p>Submit your request and receive shortlisted, verified candidates within 24–72 hours.</p>
    <a href="/request-crew.php" class="btn btn-teal btn-lg">Request Crew</a>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
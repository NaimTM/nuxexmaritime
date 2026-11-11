<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Fetch latest published jobs
$jobs = $pdo->query("
  SELECT id, slug, title, rank_title, vessel_type, flag,
         salary_min, salary_max, currency, contract_length,
         joining_date, joining_port, likes_count, comments_count,
         shares_count, created_at
  FROM jobs
  WHERE status = 'published'
  ORDER BY created_at DESC
  LIMIT 3
")->fetchAll();

// Logged-in seafarer (for welcome banner)
$me = current_seafarer();

$page_title = "Nuzex Maritime | Marine Manning & Crew Management Agency in Bangladesh";
$meta_desc  = "Nuzex Maritime connects shipowners with qualified seafarers worldwide. Crew management, recruitment, documentation & compliance from Dhaka, Bangladesh.";
require __DIR__ . '/includes/header.php';
?>

<!-- ===================== WELCOME BACK (only when logged in) ===================== -->
<?php if ($me): ?>
  <div class="container" style="padding-top:20px">
    <div class="alert alert-info" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem">
      <span>
        👋 Welcome back, <strong><?= e($me['full_name']) ?></strong>
        <span class="muted"> · <?= e($me['rank'] ?? $me['rank_title'] ?? 'Seafarer') ?></span>
      </span>
      <span style="display:flex;gap:.6rem;flex-wrap:wrap">
        <a href="/jobs.php" class="btn btn-teal btn-sm">Browse Jobs</a>
        <a href="/profile.php" class="btn btn-ghost btn-sm">My Profile</a>
      </span>
    </div>
  </div>
<?php endif; ?>

<!-- ===================== HERO ===================== -->
<section class="hero">
  <div class="container">
    <h1>Reliable Crews.<br>Global Waters. One Manning Partner.</h1>
    <p class="lead">
      Nuzex Maritime connects shipowners, ship managers, and offshore operators with
      STCW-certified officers and ratings — backed by 24/7 support from Dhaka to every major port.
    </p>
    <div class="hero-actions">
      <a href="/request-crew.php" class="btn btn-teal btn-lg">Request Crew →</a>
      <a href="/register.php" class="btn btn-ghost-light btn-lg">Register as Seafarer</a>
    </div>
    <div class="hero-badges">
      <span>MLC 2006 aligned</span>
      <span>STCW compliant</span>
      <span>24/7 operations</span>
      <span>Global reach</span>
    </div>
  </div>
</section>

<!-- ===================== TRUST STRIP ===================== -->
<div class="trust-strip">
  <div class="container">
    <span><strong>Dhaka HQ</strong> · Bangladesh</span>
    <span><strong>Crew for all vessel types</strong> — tankers, bulk, container, offshore, cruise</span>
    <span><strong>24/7 Emergency Line</strong> for crew changes</span>
  </div>
</div>

<!-- ===================== DUAL PATH ===================== -->
<section class="section-sm">
  <div class="container">
    <div class="dual-path">
      <div class="path-card">
        <span class="path-icon">🚢</span>
        <h3>For Shipowners & Managers</h3>
        <p>Need crew fast? Submit a request and receive shortlisted, verified candidates within 24–72 hours.</p>
        <a href="/request-crew.php" class="btn btn-primary">Request Crew</a>
      </div>
      <div class="path-card">
        <span class="path-icon">⚓</span>
        <h3>For Seafarers</h3>
        <p>Looking for your next contract? Register free, upload your CV, and apply to live vacancies worldwide.</p>
        <a href="/register.php" class="btn btn-gold">Register as Seafarer</a>
      </div>
    </div>
  </div>
</section>

<!-- ===================== SERVICES ===================== -->
<section class="section bg-light">
  <div class="container center">
    <h2>End-to-End Crew Solutions</h2>
    <p style="max-width:640px;margin:0 auto">From recruitment to rotation, documentation to payroll — we handle the people side of shipping so you can focus on the voyage.</p>

    <div class="services-grid" style="text-align:left">
      <?php
      $services = [
        ['🚢','Crew Management','Full manning, rotation, welfare, and performance oversight across your fleet.'],
        ['🔍','Seafarer Recruitment','Verified officers and ratings for every vessel type — screened and ready.'],
        ['📄','Documentation','COC, COP, CDC, visas, flag-state paperwork, and endorsements.'],
        ['🎓','Training Coordination','STCW and specialized courses through approved training centers.'],
        ['✈️','Travel & Logistics','Flights, visas, transfers, and full crew-change planning.'],
        ['💰','Payroll & Allotments','On-time wages, allotments, and crew-family disbursements.'],
        ['✅','Compliance','MLC 2006, STCW, and flag-state adherence at every step.'],
      ];
      foreach ($services as [$icon,$title,$desc]): ?>
        <div class="service-card">
          <div class="service-icon"><?= $icon ?></div>
          <h3><?= e($title) ?></h3>
          <p><?= e($desc) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== LIVE JOBS ===================== -->
<section class="section">
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:end;flex-wrap:wrap;gap:1rem;margin-bottom:.5rem">
      <div>
        <h2 style="margin-bottom:.3rem">Latest Vacancies</h2>
        <p style="margin:0">Live opportunities from shipowners we work with.</p>
      </div>
      <a href="/jobs.php" class="btn btn-ghost">View All Jobs →</a>
    </div>

    <?php if (!$jobs): ?>
      <div class="alert alert-info" style="margin-top:2rem">
        No vacancies published yet. <a href="/register.php">Register as a seafarer</a> to be notified when jobs go live.
      </div>
    <?php else: ?>
      <div class="jobs-feed">
        <?php foreach ($jobs as $j): ?>
          <article class="job-card">
            <div class="job-card-top">
              <div>
                <span class="job-tag"><?= e($j['vessel_type'] ?: 'Vessel') ?></span>
                <h3 style="margin-top:.5rem"><a href="/job.php?slug=<?= e($j['slug']) ?>"><?= e($j['title']) ?></a></h3>
              </div>
              <div class="job-salary">
                <?= money($j['salary_min'], $j['currency']) ?>
                <?= $j['salary_max'] ? '– ' . money($j['salary_max'], $j['currency']) : '' ?>
              </div>
            </div>

            <div class="job-meta">
              <?php if ($j['flag']): ?><span>🏴 <?= e($j['flag']) ?></span><?php endif; ?>
              <?php if ($j['contract_length']): ?><span>⏱ <?= e($j['contract_length']) ?></span><?php endif; ?>
              <?php if ($j['joining_port']): ?><span>📍 <?= e($j['joining_port']) ?></span><?php endif; ?>
              <?php if ($j['joining_date']): ?><span>📅 <?= date('M j, Y', strtotime($j['joining_date'])) ?></span><?php endif; ?>
              <span>🕒 <?= time_ago($j['created_at']) ?></span>
            </div>

            <div class="job-actions">
              <a href="/job.php?slug=<?= e($j['slug']) ?>" class="btn btn-primary btn-sm">Apply with CV</a>
              <button class="action-btn" data-like="<?= (int)$j['id'] ?>">
                ♡ <span class="count"><?= (int)$j['likes_count'] ?></span>
              </button>
              <a class="action-btn" href="/job.php?slug=<?= e($j['slug']) ?>#comments">
                💬 <?= (int)$j['comments_count'] ?>
              </a>
              <button class="action-btn"
                      data-share="native"
                      data-id="<?= (int)$j['id'] ?>"
                      data-url="<?= SITE_URL ?>/job.php?slug=<?= e($j['slug']) ?>"
                      data-title="<?= e($j['title']) ?>">
                ↗ Share
              </button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ===================== HOW IT WORKS ===================== -->
<section class="section bg-light">
  <div class="container">
    <div class="center">
      <h2>How It Works</h2>
      <p style="max-width:600px;margin:0 auto">Simple, transparent, and fast — whether you're hiring or getting hired.</p>
    </div>

    <div class="steps">
      <div class="step"><div class="step-num">1</div><h4>Request or Register</h4><p>Shipowners submit a crew request. Seafarers create a free profile and upload their CV.</p></div>
      <div class="step"><div class="step-num">2</div><h4>Screen & Verify</h4><p>We check certificates, COC/COP, medical, and references — every time.</p></div>
      <div class="step"><div class="step-num">3</div><h4>Match & Interview</h4><p>Shortlists go out within 24–72 hours. Interviews are arranged and supported.</p></div>
      <div class="step"><div class="step-num">4</div><h4>Join & Support</h4><p>Travel, documents, and crew change handled. 24/7 support throughout the contract.</p></div>
    </div>
  </div>
</section>

<!-- ===================== WHY NUZEX ===================== -->
<section class="section">
  <div class="container">
    <div class="center">
      <h2>Why Nuzex Maritime</h2>
    </div>

    <div class="why-grid">
      <div class="why-item"><div class="icon">⚓</div><div><h4>Built by Mariners</h4><p>Founded by people who understand the sea, the ships, and the crews who run them.</p></div></div>
      <div class="why-item"><div class="icon">🛡️</div><div><h4>Verified Documents</h4><p>Every COC, COP, and STCW certificate checked before a candidate reaches you.</p></div></div>
      <div class="why-item"><div class="icon">⏱️</div><div><h4>24/7 Response</h4><p>Emergency crew changes handled around the clock — any time zone, any port.</p></div></div>
      <div class="why-item"><div class="icon">💼</div><div><h4>Transparent Pricing</h4><p>Clear manning fees. No hidden commissions. No surprises for shipowners.</p></div></div>
      <div class="why-item"><div class="icon">🌍</div><div><h4>Global Network</h4><p>Access to a growing pool of Bangladeshi and international seafarers across all ranks.</p></div></div>
      <div class="why-item"><div class="icon">🤝</div><div><h4>Free for Seafarers</h4><p>Registration, applications, and job alerts are always free for seafarers.</p></div></div>
    </div>
  </div>
</section>

<!-- ===================== COMPLIANCE BANNER ===================== -->
<section class="section-sm bg-light">
  <div class="container center">
    <h2 style="margin-bottom:1rem">Compliance Is Not Optional</h2>
    <p style="max-width:720px;margin:0 auto 1rem">
      We operate under <strong>MLC 2006</strong> and <strong>STCW</strong> standards, respect <strong>flag-state</strong> requirements,
      and handle seafarer data under <strong>GDPR-aligned</strong> practices. Certifications will be listed here as we obtain them.
    </p>
    <a href="/compliance.php" class="btn btn-ghost">Read Our Compliance Approach →</a>
  </div>
</section>

<!-- ===================== CTA BAND ===================== -->
<section class="cta-band">
  <div class="container">
    <h2>Ready to crew up? Ready to go to sea?</h2>
    <p>Whether you're a shipowner looking for reliable crew, or a seafarer looking for your next contract — we're ready to help.</p>
    <div class="hero-actions">
      <a href="/request-crew.php" class="btn btn-teal btn-lg">Request Crew</a>
      <a href="/register.php" class="btn btn-gold btn-lg">Register as Seafarer</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
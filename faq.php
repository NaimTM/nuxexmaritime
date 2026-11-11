<?php
$page_title = "FAQ | Nuzex Maritime Marine Manning Agency";
$meta_desc  = "Answers for shipowners and seafarers about crewing, registration, and applications.";
require __DIR__ . '/includes/header.php';
?>
<section class="hero" style="padding:70px 0 50px">
  <div class="container" style="max-width:800px">
    <h1>Frequently Asked Questions</h1>
    <p class="lead">Answers for shipowners, managers, and seafarers.</p>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:820px">

    <h2>For Shipowners</h2>
    <div style="margin-top:1rem">
      <?php foreach ([
        ['How fast can you provide crew?', 'For most ranks, we deliver shortlists within 24–72 hours. Emergency replacements can sometimes be arranged faster.'],
        ['Are candidates verified?', 'Yes. Every COC, COP, STCW, CDC, passport, medical, and reference is checked before a candidate reaches you.'],
        ['What are your fees?', 'One clear price per placement or a retainer arrangement for fleet-wide manning. Contact us for a quote tailored to your fleet.'],
        ['Which flag states do you cover?', 'We handle placements under all major flags. Contact us with your vessel flag and we\'ll confirm.'],
        ['What if a placed seafarer doesn\'t work out?', 'We offer replacement support under our standard agreement. Details are shared when you engage us.'],
      ] as [$q,$a]): ?>
        <details style="background:#fff;border:1px solid var(--line);border-radius:12px;padding:1rem 1.2rem;margin-bottom:.8rem">
          <summary style="font-weight:600;cursor:pointer;color:var(--navy)"><?= e($q) ?></summary>
          <p style="margin-top:.8rem;color:var(--text)"><?= e($a) ?></p>
        </details>
      <?php endforeach; ?>
    </div>

    <h2 style="margin-top:3rem">For Seafarers</h2>
    <div style="margin-top:1rem">
      <?php foreach ([
        ['Is registration free?', 'Yes, always free. We never charge seafarers for registration or applications.'],
        ['How do I apply to jobs?', 'Register, upload your CV, then click "Apply with CV" on any vacancy. Your profile is sent to the shipowner.'],
        ['Can I share jobs with friends?', 'Yes — every job has a share button for WhatsApp, Facebook, LinkedIn, X, Telegram, and email.'],
        ['How will I know if I\'m shortlisted?', 'You\'ll receive an email and WhatsApp notification when a shipowner shortlists you.'],
        ['What documents do I need to register?', 'Passport, COC, COP, STCW certificates, CDC, medical, and an updated CV. You can add them as you go.'],
        ['Is my personal data safe?', 'Yes. We handle data under GDPR-aligned practices. Your CV is only shared with shipowners when you apply.'],
      ] as [$q,$a]): ?>
        <details style="background:#fff;border:1px solid var(--line);border-radius:12px;padding:1rem 1.2rem;margin-bottom:.8rem">
          <summary style="font-weight:600;cursor:pointer;color:var(--navy)"><?= e($q) ?></summary>
          <p style="margin-top:.8rem;color:var(--text)"><?= e($a) ?></p>
        </details>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center;margin-top:3rem">
      <p>Still have questions?</p>
      <a href="/contact.php" class="btn btn-primary">Contact Us</a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
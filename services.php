<?php
$page_title = "Marine Manning Services | Nuzex Maritime";
$meta_desc  = "Crew management, recruitment, documentation, training, travel, payroll, and compliance — all under one roof at Nuzex Maritime.";
require __DIR__ . '/includes/header.php';

$services = [
  ['🚢','Crew Management','Full manning, rotation, welfare, and performance oversight across your fleet.','Crew planning, sign-on/off management, welfare checks, performance reviews, and 24/7 escalation support.'],
  ['🔍','Seafarer Recruitment','Verified officers and ratings for every vessel type.','Sourcing, screening, interview coordination, and shortlisting across all ranks — from Master to Messman.'],
  ['📄','Documentation','COC, COP, CDC, visas, flag-state paperwork, and endorsements.','Full document lifecycle: verification, flag state submissions, visa processing, and crew travel letters.'],
  ['🎓','Training Coordination','STCW and specialized courses through approved training centers.','Booking, follow-up, and certificate collection for STCW and advanced courses at accredited centers.'],
  ['✈️','Travel & Logistics','Flights, visas, transfers, and full crew-change planning.','Ticketing, hotel bookings, airport transfers, and end-to-end crew-change management at every port.'],
  ['💰','Payroll & Allotments','On-time wages, allotments, and crew-family disbursements.','Wage calculations, allotment processing, tax handling, and transparent monthly reporting.'],
  ['✅','Compliance','MLC 2006, STCW, and flag-state adherence at every step.','Regular audits, document verification, and a full compliance trail for every placement.'],
];
?>
<section class="hero" style="padding:80px 0 60px">
  <div class="container" style="max-width:800px">
    <h1>End-to-End Crew Solutions</h1>
    <p class="lead">From recruitment to rotation, documentation to payroll — we handle the people side of shipping so you can focus on the voyage.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="services-grid">
      <?php foreach ($services as [$icon,$title,$short,$long]): ?>
        <div class="service-card">
          <div class="service-icon"><?= $icon ?></div>
          <h3><?= e($title) ?></h3>
          <p><?= e($short) ?></p>
          <p style="margin-top:.8rem;font-size:.85rem"><strong>Includes:</strong> <?= e($long) ?></p>
        </div>
      <?php endforeach; ?>
    </div>

    <div style="margin-top:4rem;text-align:center">
      <h2>Sectors We Serve</h2>
      <p style="max-width:640px;margin:1rem auto 2rem">We crew vessels across every major sector in commercial shipping and offshore.</p>
      <div style="display:flex;gap:.8rem;flex-wrap:wrap;justify-content:center">
        <?php foreach (['Oil Tankers','Chemical Tankers','Bulk Carriers','Container Ships','General Cargo','Offshore Support','Cruise','Tugs & Dredgers'] as $s): ?>
          <span class="job-tag" style="padding:.5rem 1rem;font-size:.85rem"><?= e($s) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container">
    <h2>Need a Specific Rank?</h2>
    <p>Tell us what you need. We'll shortlist within 24–72 hours.</p>
    <a href="/request-crew.php" class="btn btn-teal btn-lg">Request Crew</a>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
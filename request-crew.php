<?php
$page_title = "Request Crew | Nuzex Maritime Manning Agency";
$meta_desc  = "Submit your crew request to Nuzex Maritime. We respond within 24 hours with qualified, STCW-certified candidates.";
require __DIR__ . '/includes/header.php';

$success = null; $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check($_POST['csrf'] ?? null);

  $data = [
    'company'        => trim($_POST['company'] ?? ''),
    'contact_name'   => trim($_POST['contact_name'] ?? ''),
    'email'          => trim($_POST['email'] ?? ''),
    'phone'          => trim($_POST['phone'] ?? ''),
    'vessel_type'    => trim($_POST['vessel_type'] ?? ''),
    'flag'           => trim($_POST['flag'] ?? ''),
    'ranks_needed'   => trim($_POST['ranks_needed'] ?? ''),
    'crew_count'     => (int)($_POST['crew_count'] ?? 0),
    'joining_date'   => $_POST['joining_date'] ?: null,
    'port'           => trim($_POST['port'] ?? ''),
    'contract_length'=> trim($_POST['contract_length'] ?? ''),
    'notes'          => trim($_POST['notes'] ?? ''),
  ];

  if (!$data['company'] || !$data['contact_name'] || !filter_var($data['email'], FILTER_VALIDATE_EMAIL) || !$data['ranks_needed']) {
    $error = 'Please fill in Company, Contact, valid Email, and Ranks needed.';
  } else {
    $pdo->prepare("INSERT INTO crew_requests (company,contact_name,email,phone,vessel_type,flag,ranks_needed,crew_count,joining_date,port,contract_length,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute(array_values($data));
    $success = 'Thank you. Our team will respond within 24 hours.';
  }
}
?>
<section class="hero" style="padding:70px 0 50px">
  <div class="container" style="max-width:800px">
    <h1>Request Crew</h1>
    <p class="lead">Tell us who you need. We respond within 24 hours with shortlisted, verified candidates.</p>
  </div>
</section>

<section class="section" style="padding-top:40px">
  <div class="container" style="max-width:760px">
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post" class="form-card">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

      <div class="form-row">
        <div class="form-group"><label>Company *</label><input name="company" required></div>
        <div class="form-group"><label>Contact Person *</label><input name="contact_name" required></div>
      </div>

      <div class="form-row">
        <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Phone / WhatsApp</label><input name="phone"></div>
      </div>

      <div class="form-row">
        <div class="form-group"><label>Vessel Type</label><input name="vessel_type" placeholder="VLCC / Bulk / Container"></div>
        <div class="form-group"><label>Flag</label><input name="flag" placeholder="Panama"></div>
      </div>

      <div class="form-group">
        <label>Ranks Needed * <small class="muted">(comma separated)</small></label>
        <input name="ranks_needed" required placeholder="Master, Chief Officer, AB × 3">
      </div>

      <div class="form-row">
        <div class="form-group"><label>Number of Crew</label><input type="number" name="crew_count" min="1"></div>
        <div class="form-group"><label>Joining Date</label><input type="date" name="joining_date"></div>
      </div>

      <div class="form-row">
        <div class="form-group"><label>Joining Port</label><input name="port" placeholder="Singapore"></div>
        <div class="form-group"><label>Contract Length</label><input name="contract_length" placeholder="6 months"></div>
      </div>

      <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" placeholder="Any specific requirements — tanker experience, English level, etc."></textarea>
      </div>

      <button class="btn btn-teal btn-lg">Submit Request →</button>
      <p class="form-help" style="margin-top:1rem">By submitting, you agree to our <a href="/privacy-policy.php">Privacy Policy</a>.</p>
    </form>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
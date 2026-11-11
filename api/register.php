<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// ============================================================
// ALREADY LOGGED IN? Show friendly message instead of forms
// ============================================================
if (is_seafarer()) {
  $page_title = "You're already signed in | Nuzex Maritime";
  $meta_desc  = "You are already logged in as a seafarer.";
  require __DIR__ . '/includes/header.php';
  ?>
  <section class="section" style="padding-top:60px">
    <div class="container" style="max-width:640px;text-align:center">
      <div style="font-size:3rem;margin-bottom:1rem">✅</div>
      <h1>You're already signed in</h1>
      <p>Welcome back, <strong><?= e($_SESSION['seafarer']['full_name']) ?></strong>.</p>
      <p class="muted">Logged in as <?= e($_SESSION['seafarer']['email']) ?></p>

      <div style="display:flex;gap:.7rem;justify-content:center;flex-wrap:wrap;margin-top:2rem">
        <a href="/profile.php" class="btn btn-teal">My Profile</a>
        <a href="/jobs.php" class="btn btn-primary">Browse Jobs</a>
        <a href="/my-applications.php" class="btn btn-ghost">My Applications</a>
        <a href="/settings.php" class="btn btn-ghost">Settings</a>
      </div>

      <p style="margin-top:2.5rem">
        <a href="/logout.php" style="color:var(--danger);font-weight:600">Log out of this account →</a>
      </p>
    </div>
  </section>
  <?php
  require __DIR__ . '/includes/footer.php';
  exit;
}

$redirect = $_GET['redirect'] ?? '/for-seafarers.php';
$error = null;

// ============================================================
// LOGIN
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
  csrf_check($_POST['csrf'] ?? null);
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';

  $u = $pdo->prepare("SELECT * FROM seafarers WHERE email = ?");
  $u->execute([$email]);
  $user = $u->fetch();

  if ($user && password_verify($pass, $user['password_hash'])) {
    $_SESSION['seafarer'] = $user;
    redirect($_POST['redirect'] ?? '/for-seafarers.php');
  }
  $error = 'Invalid email or password.';
}

// ============================================================
// REGISTER
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
  csrf_check($_POST['csrf'] ?? null);

  $data = [
    'full_name'         => trim($_POST['full_name'] ?? ''),
    'email'             => trim($_POST['email'] ?? ''),
    'phone'             => trim($_POST['phone'] ?? ''),
    'password'          => $_POST['password'] ?? '',
    'nationality'       => trim($_POST['nationality'] ?? ''),
    'rank_title'        => trim($_POST['rank_title'] ?? ''),
    'coc_no'            => trim($_POST['coc_no'] ?? ''),
    'cop_no'            => trim($_POST['cop_no'] ?? ''),
    'cdc_no'            => trim($_POST['cdc_no'] ?? ''),
    'vessel_experience' => trim($_POST['vessel_experience'] ?? ''),
  ];

  if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $error = 'Please enter a valid email.';
  } elseif (strlen($data['password']) < 8) {
    $error = 'Password must be at least 8 characters.';
  } elseif (!$data['full_name'] || !$data['phone'] || !$data['rank_title']) {
    $error = 'Please fill in all required fields.';
  } else {
    $chk = $pdo->prepare("SELECT 1 FROM seafarers WHERE email = ?");
    $chk->execute([$data['email']]);

    if ($chk->fetchColumn()) {
      $error = 'This email is already registered. Try logging in below.';
    } else {
      // Handle CV upload
      $cvPath = null;
      if (!empty($_FILES['cv']['name']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf','doc','docx']) && $_FILES['cv']['size'] < 10*1024*1024) {
          if (!is_dir(UPLOAD_CV)) mkdir(UPLOAD_CV, 0755, true);
          $name = 'cv_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
          if (move_uploaded_file($_FILES['cv']['tmp_name'], UPLOAD_CV . $name)) {
            $cvPath = 'uploads/cv/' . $name;
          }
        }
      }

      $stmt = $pdo->prepare("
        INSERT INTO seafarers
        (full_name, email, phone, password_hash, nationality, rank_title,
         coc_no, cop_no, cdc_no, vessel_experience, cv_path)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
      ");
      $stmt->execute([
        $data['full_name'], $data['email'], $data['phone'],
        password_hash($data['password'], PASSWORD_DEFAULT),
        $data['nationality'], $data['rank_title'],
        $data['coc_no'], $data['cop_no'], $data['cdc_no'],
        $data['vessel_experience'], $cvPath
      ]);

      $uid = (int)$pdo->lastInsertId();
      $u = $pdo->prepare("SELECT * FROM seafarers WHERE id = ?");
      $u->execute([$uid]);
      $_SESSION['seafarer'] = $u->fetch();
      redirect($redirect);
    }
  }
}

$page_title = "Register as Seafarer | Join Nuzex Maritime Crew Pool";
$meta_desc  = "Create your free seafarer profile with Nuzex Maritime. Upload your CV and documents, and get matched to global ship jobs.";
require __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:50px">
  <div class="container" style="max-width:800px">
    <div class="center" style="margin-bottom:2rem">
      <h1 style="font-size:2rem">Register as Seafarer</h1>
      <p>Free forever. Upload your CV once — apply to any job in one click.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <!-- ============ LOGIN ============ -->
    <div class="form-card">
      <h3>Already registered? Log in</h3>
      <form method="post" style="margin-top:1rem">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
        <div class="form-row">
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
          </div>
        </div>
        <button class="btn btn-primary">Log In</button>
      </form>
    </div>

    <!-- ============ REGISTER ============ -->
    <div class="form-card" style="margin-top:2rem">
      <h3>New here? Create your profile</h3>
      <form method="post" enctype="multipart/form-data" style="margin-top:1rem">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="register">

        <div class="form-row">
          <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" required></div>
          <div class="form-group"><label>Nationality</label><input type="text" name="nationality" value="Bangladeshi"></div>
        </div>

        <div class="form-row">
          <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
          <div class="form-group"><label>Phone / WhatsApp *</label><input type="tel" name="phone" required></div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Password * <small class="muted">(8+ chars)</small></label>
            <input type="password" name="password" minlength="8" required>
          </div>
          <div class="form-group">
            <label>Rank *</label>
            <select name="rank_title" required>
              <option value="">Select rank…</option>
              <?php foreach (['Master','Chief Officer','2nd Officer','3rd Officer','Chief Engineer','2nd Engineer','3rd Engineer','4th Engineer','ETO','Bosun','AB','Oiler','Fitter','Cook','Steward','Messman','Other'] as $r): ?>
                <option><?= e($r) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group"><label>COC Number</label><input type="text" name="coc_no"></div>
          <div class="form-group"><label>COP Number</label><input type="text" name="cop_no"></div>
        </div>

        <div class="form-row">
          <div class="form-group"><label>CDC Number</label><input type="text" name="cdc_no"></div>
          <div class="form-group"><label>Years / Vessel Experience</label><input type="text" name="vessel_experience" placeholder="e.g. 5 yrs tanker"></div>
        </div>

        <div class="form-group">
          <label>CV <small class="muted">(PDF/DOC · max 10 MB)</small></label>
          <div class="form-file"><input type="file" name="cv" accept=".pdf,.doc,.docx"></div>
        </div>

        <p class="form-help">
          By registering you agree to our
          <a href="/privacy-policy.php">Privacy Policy</a>.
        </p>

        <button class="btn btn-teal btn-lg">Create My Profile</button>
      </form>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
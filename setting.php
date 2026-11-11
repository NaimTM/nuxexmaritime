<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_seafarer();

$id = (int)$_SESSION['seafarer']['id'];
$stmt = $pdo->prepare("SELECT * FROM seafarers WHERE id = ?");
$stmt->execute([$id]);
$me = $stmt->fetch();

$page_title = "Account Settings | Nuzex Maritime";
$meta_desc  = "Manage your email, password, and account.";
require __DIR__ . '/includes/header.php';
?>

<section class="profile-hero" style="padding:40px 0 60px">
  <div class="container">
    <div class="profile-avatar"><?= e(strtoupper(substr($me['full_name'], 0, 1))) ?></div>
    <div class="profile-meta">
      <h1>Account Settings</h1>
      <p><?= e($me['email']) ?></p>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container" style="max-width:760px">

    <div class="profile-tabs" style="margin-top:-30px">
      <a href="/profile.php">Profile</a>
      <a href="/my-applications.php">Applications</a>
      <a href="/settings.php" class="active">Settings</a>
    </div>

    <?php if ($m = flash('settings_success')): ?>
      <div class="alert alert-success" style="margin-top:1.5rem"><?= e($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash('settings_error')): ?>
      <div class="alert alert-error" style="margin-top:1.5rem"><?= e($m) ?></div>
    <?php endif; ?>

    <!-- ============ CHANGE EMAIL ============ -->
    <form method="post" action="/api/settings.php" class="form-card" style="margin-top:1.5rem">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="email">
      <h3>Change Email</h3>
      <div class="form-group">
        <label>New Email</label>
        <input type="email" name="email" value="<?= e($me['email']) ?>" required>
      </div>
      <div class="form-group">
        <label>Confirm with your password</label>
        <input type="password" name="current_password" required>
      </div>
      <button class="btn btn-primary">Update Email</button>
    </form>

    <!-- ============ CHANGE PASSWORD ============ -->
    <form method="post" action="/api/settings.php" class="form-card" style="margin-top:1.5rem">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="password">
      <h3>Change Password</h3>
      <div class="form-group">
        <label>Current Password</label>
        <input type="password" name="current_password" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>New Password <small class="muted">(8+ chars)</small></label>
          <input type="password" name="new_password" minlength="8" required>
        </div>
        <div class="form-group">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" minlength="8" required>
        </div>
      </div>
      <button class="btn btn-primary">Change Password</button>
    </form>

    <!-- ============ DANGER ZONE ============ -->
    <div class="form-card" style="margin-top:1.5rem;border-color:rgba(217,74,74,.35)">
      <h3 style="color:var(--danger)">Danger Zone</h3>
      <p class="muted">Once deleted, your account and applications cannot be recovered.</p>

      <form method="post" action="/api/delete-account.php"
            onsubmit="return confirm('Are you absolutely sure? This cannot be undone.');">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-group">
          <label>Type DELETE to confirm</label>
          <input type="text" name="confirm" placeholder="DELETE" required>
        </div>
        <div class="form-group">
          <label>Your password</label>
          <input type="password" name="password" required>
        </div>
        <button class="btn btn-primary" style="background:var(--danger)">Delete My Account</button>
      </form>
    </div>

  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
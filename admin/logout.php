<?php
$admin_title = 'Applications';
require __DIR__ . '/_nav.php';
// ... your content ...
?>
  </main>
</div>
</body>
</html>
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
unset($_SESSION['admin']);
redirect('/admin/login.php');
<?php
// Backward-compatible route; the original settings page is named setting.php.
header('Location: /setting.php', true, 301);
exit;

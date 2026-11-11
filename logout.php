<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
unset($_SESSION['seafarer']);
redirect('/');
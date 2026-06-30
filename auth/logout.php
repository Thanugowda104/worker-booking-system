<?php
// auth/logout.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
session_destroy();
header('Location: http://localhost/WBS/auth/login.php');
exit;

<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

header("Location: " . BASE_URL . "auth/login.php");
exit;

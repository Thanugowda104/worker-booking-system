<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
// index.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
header("Location: " . BASE_URL . "auth/login.php");
exit;

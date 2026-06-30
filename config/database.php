<?php
define("BASE_URL", "/");
$host = "dpg-d91u61gk1i2s739s06h0-a.ohio-postgres.render.com";
$db   = "wbs_db_2dhx";
$user = "wbs_db_2dhx_user";
$pass = "6aL5qMnaliEEXUb9F6F4EA6VBLihdSJc";
$port = "5432";

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$db;sslmode=require",
        $user,
        $pass
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

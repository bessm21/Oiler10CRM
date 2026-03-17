<?php
// core/config.sample.php
// INSTRUCTIONS: Duplicate this file, rename it to config.php, and fill in the real passwords.

define('SUPABASE_URL', 'YOUR_SUPABASE_URL');
define('SUPABASE_ANON_KEY', 'YOUR_ANON_KEY');

$host = 'aws-0-us-west-2.pooler.supabase.com';
$port = 6543;
$db   = 'postgres';
$user = 'postgres.djlhptpzmyjhzhmvefbr';
$pass = 'YOUR_DATABASE_PASSWORD'; // Get this from the team lead

$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Connection error: " . $e->getMessage());
}
?>
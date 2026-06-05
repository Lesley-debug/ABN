<?php
require_once __DIR__ . '/session_bootstrap.php';
if (!isset($_SESSION['username'])) {
    header("Location: signin.php");
    exit();
}

?>
<?php include 'config.php'; ?>

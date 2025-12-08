<?php
// v1/logout.php
session_start();
session_destroy();
header("Location: login.php");
exit();
?>

<?php
require 'auth.php';
auth_logout();
header("Location: /login");
exit;
?>

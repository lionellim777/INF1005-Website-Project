<?php
require_once "inc/auth.inc.php";
logoutUser();
header('Location: /index.php?bye=1');
exit;

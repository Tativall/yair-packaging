<?php
require_once '../config/database.php';
logoutAdmin();
header('Location: login.php');
exit;

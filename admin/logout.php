<?php
require_once '../config/database.php';
startSession();
session_destroy();
header('Location: login.php');
exit;

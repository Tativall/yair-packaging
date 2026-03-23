<?php
require_once '../config/supabase.php';
logoutAdmin();
header('Location: login.php');
exit;

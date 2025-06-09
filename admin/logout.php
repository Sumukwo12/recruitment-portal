<?php
require_once '../config/config.php';
require_once '../models/Admin.php';

$adminModel = new Admin();
$adminModel->logout();

redirect('login.php');
?>

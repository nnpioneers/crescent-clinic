<?php
$_GET = ['action' => 'get_reports', 'period' => 'today'];
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'management';
require 'api/reports_api.php';

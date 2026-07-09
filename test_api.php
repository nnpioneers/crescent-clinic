<?php
require 'db.php';
$_SERVER['REQUEST_URI']='/api/inventory/search';
$_SERVER['REQUEST_METHOD']='GET';
$_GET['q']='a';
$_GET['category']='medicine';
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'pharmacist';
require 'api/api.php';

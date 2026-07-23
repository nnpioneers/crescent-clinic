<?php
require 'db.php';
$_SERVER['REQUEST_URI']='/api/generics/delete-multiple';
$_SERVER['REQUEST_METHOD']='POST';
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'pharmacist';
require 'api/api.php';

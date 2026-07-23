<?php
require 'db.php';
$_GET['q'] = 'd';
$_GET['category'] = 'medicine';
$_SERVER['REQUEST_URI'] = '/api/inventory/search';
$_SERVER['REQUEST_METHOD'] = 'GET';
require 'api/api.php';

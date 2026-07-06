<?php
// Mock php://input by replacing it in api.php execution
$_SERVER["REQUEST_METHOD"] = "POST";
$_SERVER["CONTENT_TYPE"] = "application/json";
$_SERVER["REQUEST_URI"] = "/api/inventory/auto_create_brand";

// Create a temporary file to act as php://input
$tempFile = tempnam(sys_get_temp_dir(), "mock_input");
file_put_contents($tempFile, json_encode([
    "generic_name" => "BI - FOLATE",
    "brand_name" => "mnki",
    "unit_price" => 1.5
]));

// Override stream wrapper for php://input? No, just modify $_POST if it parses it?
// Actually api.php uses file_get_contents("php://input").
// We can just execute a curl command against the running server instead!


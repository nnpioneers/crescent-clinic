<?php
require "db.php";
$conn = get_db();
$q = "a";
$category = "Injection";

$ai_conditions = ["LOWER(item_name) = '(unmapped brand)'", "generic_name IS NOT NULL", "TRIM(generic_name) != ''", "LOWER(generic_name) LIKE ?"];
$ai_params     = [strtolower("$q%")];
if ($category === "medicine") {
    $ai_conditions[] = "(category IS NULL OR category NOT IN ('Injection', 'INJ', 'IV'))";
} elseif ($category === "Injection" || $category === "INJ") {
    $ai_conditions[] = "category IN ('Injection', 'INJ')";
} elseif ($category) {
    $ai_conditions[] = "category = ?";
    $ai_params[] = $category;
}
$ai_query = "SELECT DISTINCT generic_name FROM agency_items WHERE " . implode(" AND ", $ai_conditions) . " ORDER BY CASE WHEN LOWER(generic_name) LIKE ? THEN 0 ELSE 1 END, generic_name ASC LIMIT 30";
$ai_params[] = strtolower("$q%");

$stmt_ai = $conn->prepare($ai_query);
$stmt_ai->execute($ai_params);
$ai_generics = $stmt_ai->fetchAll(PDO::FETCH_COLUMN);

echo "AI Generics count: " . count($ai_generics) . "\n";
print_r($ai_generics);


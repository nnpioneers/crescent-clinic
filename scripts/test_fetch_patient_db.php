<?php
require_once __DIR__ . '/../db.php';
$conn = get_db();

echo "--- PATIENTS TABLE RECENT RECORDS ---\n";
$stmt = $conn->query("SELECT id, name, phone, patient_id, created_at FROM patients ORDER BY id DESC LIMIT 20");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | Name: {$row['name']} | Phone: '{$row['phone']}' | PatientID: '{$row['patient_id']}' | Created: {$row['created_at']}\n";
}

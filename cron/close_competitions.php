<?php
// Bu dosyanın sadece komut satırından (CLI) çalıştırıldığından emin ol
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Forbidden: This script can only be run from the command line.");
}

// Veritabanı bağlantısını projenin ana dizininden al
require_once __DIR__ . '/../core/db.php';

$now = date('Y-m-d H:i:s');

// Bitiş tarihi geçmiş ve hala 'active' olan yarışmaları 'closed' yap
$stmt = $conn->prepare("UPDATE competitions SET status = 'closed' WHERE end_date < ? AND status = 'active'");
$stmt->bind_param("s", $now);
$stmt->execute();

$affected_rows = $stmt->affected_rows;

if ($affected_rows > 0) {
    echo "[$now] Success: $affected_rows competitions have been closed.\n";
} else {
    echo "[$now] Info: No competitions to close.\n";
}

$stmt->close();
$conn->close();
?>
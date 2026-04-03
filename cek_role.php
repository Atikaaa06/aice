<?php
session_start();
echo "<pre>";
echo "=== SESSION ===\n";
print_r($_SESSION);
echo "\n=== ROLE ===\n";
echo "Role: " . ($_SESSION['role'] ?? 'TIDAK ADA') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'TIDAK ADA') . "\n";
echo "</pre>";
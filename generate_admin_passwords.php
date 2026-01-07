<?php
// Generate password hashes for new super admin accounts
$password1 = 'admin123';
$password2 = 'superadmin456';

$hash1 = password_hash($password1, PASSWORD_DEFAULT);
$hash2 = password_hash($password2, PASSWORD_DEFAULT);

echo "Password 1: $password1\n";
echo "Hash 1: $hash1\n\n";

echo "Password 2: $password2\n";
echo "Hash 2: $hash2\n";
?> 
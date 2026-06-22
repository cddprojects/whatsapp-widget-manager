<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this script from the command line only.');
}

$email = strtolower(trim((string) (getenv('SUPERADMIN_EMAIL') ?: '')));
$password = (string) (getenv('SUPERADMIN_PASSWORD') ?: '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Set SUPERADMIN_EMAIL in .env before running seed-superadmin.php.\n");
    exit(1);
}

if ($password === '') {
    fwrite(STDERR, "Set SUPERADMIN_PASSWORD in .env before running seed-superadmin.php.\n");
    exit(1);
}

$stmt = db()->prepare('SELECT id, role FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$existing = $stmt->fetch();

if ($existing) {
    echo "Super admin already exists for {$email} (user #{$existing['id']}). No changes made.\n";
    exit(0);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = db()->prepare(
    'INSERT INTO users (name, email, password, role, status, password_changed_at)
     VALUES (:name, :email, :password, :role, :status, NOW())'
);
$stmt->execute([
    'name' => 'Super Admin',
    'email' => $email,
    'password' => $hash,
    'role' => 'superadmin',
    'status' => 'active',
]);

echo "Super admin created for {$email}.\n";
echo "Login at your app URL, then change the password after first login if desired.\n";

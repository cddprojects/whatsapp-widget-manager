<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$errors = [];
$name = '';
$email = '';
$status = USER_STATUS_ACTIVE;
$password = '';
$generatedPassword = null;

if (is_post()) {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $status = (string) ($_POST['status'] ?? USER_STATUS_ACTIVE);
    $password = (string) ($_POST['password'] ?? '');
    $autoGenerate = !empty($_POST['auto_generate']);

    if ($name === '') {
        $errors[] = 'Client name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if (!in_array($status, [USER_STATUS_ACTIVE, USER_STATUS_DISABLED], true)) {
        $status = USER_STATUS_ACTIVE;
    }

    if ($autoGenerate) {
        $password = generate_temporary_password();
        $generatedPassword = $password;
    }

    if ($password === '') {
        $errors[] = 'Temporary password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'This email is already registered.';
        } else {
            $stmt = db()->prepare(
                'INSERT INTO users (name, email, password, role, status, password_changed_at)
                 VALUES (:name, :email, :password, :role, :status, NOW())'
            );
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => ROLE_CLIENT,
                'status' => $status,
            ]);
            $clientId = (int) db()->lastInsertId();
            if ($generatedPassword !== null) {
                flash('success', 'Client account created. Copy the temporary password now.');
                $_SESSION['created_client_password'] = $generatedPassword;
            } else {
                flash('success', 'Client account created.');
            }
            redirect('admin-client-detail.php?id=' . $clientId);
        }
    }
}

$pageTitle = 'Add Client';
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow">Super admin</p>
    <h1>Add client</h1>
    <p>Create a new client account for the WhatsApp widget manager.</p>
</section>

<section class="settings-card">
    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="admin-form-grid">
        <?= csrf_field() ?>
        <label>
            <span>Client name</span>
            <input type="text" name="name" value="<?= e($name) ?>" required>
        </label>
        <label>
            <span>Client email</span>
            <input type="email" name="email" value="<?= e($email) ?>" required>
        </label>
        <label>
            <span>Temporary password</span>
            <input type="text" name="password" value="<?= e($password) ?>" minlength="8" autocomplete="new-password">
        </label>
        <label>
            <span>Status</span>
            <select name="status">
                <option value="<?= e(USER_STATUS_ACTIVE) ?>"<?= selected($status, USER_STATUS_ACTIVE) ?>>Active</option>
                <option value="<?= e(USER_STATUS_DISABLED) ?>"<?= selected($status, USER_STATUS_DISABLED) ?>>Disabled</option>
            </select>
        </label>
        <label class="checkbox-row">
            <input type="checkbox" name="auto_generate" value="1">
            <span>Auto-generate secure password on submit</span>
        </label>
        <div class="form-actions span-full">
            <button type="submit" class="btn btn-primary">Create client</button>
            <a class="btn btn-light" href="admin-clients.php">Cancel</a>
        </div>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

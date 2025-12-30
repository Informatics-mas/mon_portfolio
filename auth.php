<?php
session_start();

// If user already logged in, redirect to upload page
if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: upload.php');
    exit;
}

// Create a CSRF token if one doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// Stored password hash (sha256 of current password). Change the stored secret here if needed.
$stored_password_hash = hash('sha256', 'i4VBLeus');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request token.';
    } else {
        $pseudo_user = trim($_POST['pseudo'] ?? '');
        $password_user = $_POST['password'] ?? '';

        // Compare pseudo and hashed password using timing-safe comparison
        if ($pseudo_user === 'Admin' && hash_equals($stored_password_hash, hash('sha256', $password_user))) {
            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
            $_SESSION['pseudo'] = $pseudo_user;
            // regenerate token to prevent replay
            unset($_SESSION['csrf_token']);
            header('Location: upload.php');
            exit;
        } else {
            $error = 'Pseudo or password incorrect.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Authentification</title>
    <style>
        :root{ --accent: white; --bg: rgba(0, 0, 0, 0.15); }
        html,body{height:100%;margin:0;background:var(--bg);color:#fff;font-family:Arial,Helvetica,sans-serif}
        .container{min-height:100%;display:flex;align-items:center;justify-content:center;padding:2rem}
        .card{background: rgb(33, 48, 95);padding:2rem;border-radius:8px;max-width:420px;width:100%;box-shadow:0 6px 24px rgba(0,0,0,0.6)}
        .card h1{text-align:center;margin:0 0 1rem;font-size:1.4rem}
        label{display:block;margin:0.5rem 0 0.25rem;font-size:0.95rem}
        input[type="text"],input[type="password"]{width:100%;padding:0.75rem;border-radius:6px;border:none;background:rgba(255,255,255,0.03);color:#fff}
        input[type="submit"]{width:100%;margin-top:1rem;padding:0.75rem;background:var(--accent);color:#000;border:none;border-radius:6px;font-weight:700;cursor:pointer}
        .error{color:#ff6b6b;margin-top:0.75rem}
        @media (max-width:480px){.card{padding:1rem}}
    </style>
</head>
<body>
    <div class="container">
        <div class="card" role="region" aria-labelledby="login-title">
            <h1 id="login-title">Admin login</h1>
            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES); ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                <label for="pseudo">Pseudo</label>
                <input type="text" id="pseudo" name="pseudo" required autocomplete="username">

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">

                <input type="submit" value="Sign in">
                <?php if (!empty($error)): ?>
                    <div class="error" role="alert"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>

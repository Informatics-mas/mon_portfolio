<?php
session_start();

// Require a logged_in flag (consistent with auth system)
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    die('Accès refusé. Veuillez vous connecter.');
}

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// DB connection (store credentials securely in production)
require_once "bd.php";
if ($conn->connect_error) {
    error_log('DB connect error: ' . $conn->connect_error);
    die('Erreur interne.');
}

// Upload directories
$upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR;
$upload_rel = 'upload/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $messages[] = 'Jeton invalide.';
    } elseif (empty($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
        $messages[] = 'Aucun fichier ou erreur d\'upload.';
    } else {
        $file = $_FILES['fichier'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            $messages[] = 'Fichier trop volumineux (max 5MB).';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'application/pdf' => 'pdf',
            ];

            if (!isset($allowed[$mime])) {
                $messages[] = 'Type non autorisé.';
            } else {
                $orig = pathinfo($file['name'], PATHINFO_FILENAME);
                $ext = $allowed[$mime];
                $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', $orig);
                $uniq = $safe . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

                $target = $upload_dir . $uniq;
                $relative = $upload_rel . $uniq;

                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $stmt = $conn->prepare('INSERT INTO fichier (nom_fichier, chemin) VALUES (?, ?)');
                    if ($stmt) {
                        $stmt->bind_param('ss', $uniq, $relative);
                        if ($stmt->execute()) {
                            $messages[] = 'Fichier uploadé et enregistré.';
                        } else {
                            error_log('DB insert error: ' . $stmt->error);
                            $messages[] = 'Erreur lors de l\'enregistrement.';
                        }
                        $stmt->close();
                    } else {
                        error_log('DB prepare error: ' . $conn->error);
                        $messages[] = 'Erreur interne.';
                    }
                } else {
                    $messages[] = 'Impossible de déplacer le fichier.';
                }
            }
        }
    }

    // Regenerate CSRF token to avoid replay
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Upload</title>
    <style>
        :root{--accent:white;--bg:rgba(0, 0, 0, 0.15)}
        html,body{height:100%;margin:0;background:var(--bg);background-size:cover;color:#fff;font-family:Arial,Helvetica,sans-serif}
        .wrap{min-height:100%;display:flex;align-items:center;justify-content:center;padding:2rem}
        .box{background:rgb(33, 48, 95);padding:1.5rem;border-radius:8px;max-width:520px;width:100%}
        label{display:block;margin-bottom:.5rem}
        input[type=file]{display:block;margin-bottom:.75rem}
        input[type=submit]{background:var(--accent);color:#000;padding:.6rem 1rem;border:0;border-radius:6px;font-weight:700;cursor:pointer}
        .msg{margin-top:.75rem}
        .msg p{margin:.25rem 0}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="box">
            <h2>Uploader un fichier</h2>
            <form method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES); ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                <label for="fichier">Choisir un fichier (jpg, png, gif, pdf — max 5MB)</label>
                <input type="file" name="fichier" id="fichier" required>
                <input type="submit" value="Uploader">
            </form>

            <div class="msg">
                <?php foreach ($messages as $m): ?>
                    <p><?= htmlspecialchars($m); ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
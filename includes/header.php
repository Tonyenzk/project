<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['user_id'] ?? null;
$theme = 'light'; // default

if ($user_id) {
    // Fetch from DB (replace with your DB logic)
    require_once __DIR__ . '/../config/database.php';
    $stmt = $pdo->prepare("SELECT theme_preference FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $theme = $stmt->fetchColumn() ?: 'light';
}
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Social Land'; ?></title>
    <link rel="stylesheet" href="./css/modal-lock.css" />
    <link href="./css/style.css" rel="stylesheet">
    <!-- Prevent FOUC: set theme class ASAP -->
    <script>
        (function() {
            var theme = <?php echo json_encode($theme); ?>;
            document.documentElement.classList.add(theme);
        })();
    </script>
    <!-- Initialize global variables for JavaScript -->
    <script src="/js/global-init.js"></script>
    <?php if (isset($additionalStyles)) echo $additionalStyles; ?>
</head>
<body class="bg-white dark:bg-black"> 
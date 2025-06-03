<?php
require_once 'config/database.php';
session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid form submission";
    } else {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($username)) {
            $errors[] = "Username is required";
        } elseif (strlen($username) > 12) {
            $errors[] = "Username must be 12 characters or less";
        } elseif (!preg_match('/^[a-z]+$/', $username)) {
            $errors[] = "Username can only contain lowercase letters";
        }

        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, and one number";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }

        // Check if username or email already exists
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Username or email already exists";
            }
        }

        // If no errors, proceed with registration
        if (empty($errors)) {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $default_profile_picture = './images/profile_placeholder.webp';
                
                $stmt = $pdo->prepare("INSERT INTO users (username, name, email, password_hash, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $username, $email, $hashed_password, $default_profile_picture]);
                
                // Generate new CSRF token after successful registration
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['success_message'] = "Registration successful! Please login.";
                
                // Redirect to prevent form resubmission
                header("Location: login.php");
                exit();
            } catch (PDOException $e) {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
      <?php include 'includes/header.php'; ?>
      <link rel="stylesheet" href="./css/login-register.css">
      <script>
         document.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.getElementById('username');
            usernameInput.addEventListener('input', function(e) {
                this.value = this.value.toLowerCase();
            });
         });
      </script>
   </head>
   <body>
<div class="pb-14 md:pb-0 snipcss-TVoAP">
    <div class="container relative h-screen flex flex-col items-center justify-center">
        <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
            <div class="flex flex-col space-y-2 text-center"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" class="mx-auto h-6 w-6">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M4 8v-2a2 2 0 0 1 2 -2h2"></path>
                    <path d="M4 16v2a2 2 0 0 0 2 2h2"></path>
                    <path d="M16 4h2a2 2 0 0 1 2 2v2"></path>
                    <path d="M16 20h2a2 2 0 0 0 2 -2v-2"></path>
                    <path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                </svg>
                <h1 class="text-2xl font-semibold tracking-tight">Create an account</h1>
                <p class="text-sm text-muted-foreground">Enter your details below to create your account</p>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <div class="p-6 border border-border rounded-lg shadow-sm">
                <form class="space-y-4" method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="space-y-2"><label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="username">Username</label><input class="flex h-10 w-full rounded-lg border bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 outline-none ring-0 border-neutral-200 dark:border-neutral-800" placeholder="johndoe" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required></div>
                    <div class="space-y-2"><label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="email">Email</label><input class="flex h-10 w-full rounded-lg border bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 outline-none ring-0 border-neutral-200 dark:border-neutral-800" placeholder="example@email.com" id="email" name="email" type="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required></div>
                    <div class="space-y-2"><label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="password">Password</label><input class="flex h-10 w-full rounded-lg border bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 outline-none ring-0 border-neutral-200 dark:border-neutral-800" placeholder="••••••••" id="password" name="password" type="password" required></div>
                    <div class="space-y-2"><label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="confirm_password">Confirm Password</label><input class="flex h-10 w-full rounded-lg border bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 outline-none ring-0 border-neutral-200 dark:border-neutral-800" placeholder="••••••••" id="confirm_password" name="confirm_password" type="password" required></div><button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2 w-full" type="submit">Create account</button>
                </form>
            </div>
            <p class="px-8 text-center text-sm text-muted-foreground">Already have an account? <a class="underline underline-offset-4 hover:text-primary" href="login.php">Sign in</a></p>
        </div>
    </div>
</div>
   </body>
</html>

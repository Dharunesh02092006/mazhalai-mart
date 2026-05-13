<?php
/**
 * Admin Login Page for Mazhalai Mart Admin Panel
 */

define('ADMIN_LOGIN_PAGE', true);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Get admin by username or email
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE (username = ? OR email = ?) AND status = 'active'");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $loginSuccess = false;
            
            if ($admin && password_verify($password, $admin['password'])) {
                $loginSuccess = true;
            } else {
                // Fallback to environment credentials if database admin not found
                $envCredentials = getAdminCredentials();
                if ($username === $envCredentials['username'] && $password === $envCredentials['password']) {
                    // Create a virtual admin object from environment
                    $admin = [
                        'id' => 1,
                        'username' => $envCredentials['username'],
                        'email' => $envCredentials['email'],
                        'full_name' => $envCredentials['full_name'],
                        'role' => 'super_admin'
                    ];
                    $loginSuccess = true;
                }
            }
            
            if ($loginSuccess) {
                // Set session
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_full_name'] = $admin['full_name'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_login_time'] = time();
                
                // Log activity
                try {
                    $logStmt = $pdo->prepare("
                        INSERT INTO admin_activity_log (admin_id, action, description, ip_address, user_agent) 
                        VALUES (?, 'login', 'Admin logged in', ?, ?)
                    ");
                    $logStmt->execute([
                        $admin['id'],
                        $_SERVER['REMOTE_ADDR'] ?? null,
                        $_SERVER['HTTP_USER_AGENT'] ?? null
                    ]);
                } catch (Exception $e) {
                    error_log("Admin login log error: " . $e->getMessage());
                }
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } catch (Exception $e) {
            error_log("Admin login error: " . $e->getMessage());
            $error = 'Login failed. Please try again.';
        }
    }
}

// Handle URL parameters
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'session_expired':
            $error = 'Your session has expired. Please login again.';
            break;
        case 'account_inactive':
            $error = 'Your account is inactive. Please contact the system administrator.';
            break;
    }
}

if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'logged_out':
            $success = 'You have been logged out successfully.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Mazhalai Mart</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin-styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="admin-login-body">
    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-login-header">
                <h1>Mazhalai Mart</h1>
                <p>Admin Panel</p>
                <h2>Administrator Login</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="admin-login-form">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Enter your username or email"
                           value="<?php echo htmlspecialchars($username ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                </div>
                
                <button type="submit" class="admin-login-btn">
                    <span class="btn-text">Login to Admin Panel</span>
                </button>
            </form>
            
            <div class="admin-login-footer">
                <p><a href="../index.html">← Back to Website</a></p>
                <p class="admin-note">Default: <?php echo env('ADMIN_USERNAME', 'admin'); ?> / <?php echo env('ADMIN_PASSWORD', 'admin123'); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
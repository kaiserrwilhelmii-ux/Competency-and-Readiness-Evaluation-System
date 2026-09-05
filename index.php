<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 0);

session_start();
include_once __DIR__ . '/db_connect.php';

// If user is already logged in, redirect straight to their dashboard
if (isset($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'supervisor') {
        header("Location: supervisor_dashboard.php");
        exit();
    } else {
        header("Location: dashboard.php");
        exit();
    }
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = trim($_POST['login_identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    // Search for either username OR email
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $login_input, $login_input);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Verify user exists and password is correct
        if ($user && password_verify($password, $user['password'])) {
            // Check if the account is suspended
            if (strtolower(trim($user['status'] ?? '')) === 'suspended') {
                $error = "This account is suspended. Please contact the administrator.";
            } else {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['fullname'] = $user['fullname'];
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } elseif ($user['role'] === 'supervisor') {
                    header("Location: supervisor_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            }
        } else {
            $error = "Invalid username/email or password.";
        }
    } else {
        $error = "Database query error.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Competency and Readiness Evaluation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: var(--bg-color, #f4f7f6); margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .login-box { background: var(--card-bg, #fff); padding: 40px; border-radius: 8px; box-shadow: var(--card-shadow, 0 4px 15px rgba(0,0,0,0.1)); width: 100%; max-width: 400px; text-align: center; box-sizing: border-box; margin: 20px; }
        .login-box h2 { margin-top: 0; color: var(--text-color, #333); margin-bottom: 25px; }
        .form-control { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid var(--border-color, #ccc); border-radius: 4px; box-sizing: border-box; background: var(--input-bg, #fff); color: var(--text-color, #333); }
        .btn-submit { width: 100%; padding: 12px; background: #2980b9; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; transition: background 0.3s; }
        .btn-submit:hover { background: #2471a3; }
        .landing-link { display: inline-block; margin-top: 15px; color: #7f8c8d; font-size: 13px; text-decoration: none; }
        .landing-link:hover { color: #2980b9; }
    </style>
</head>
<body class="light-mode">
    <div class="login-box">
        <h2><i class="fas fa-graduation-cap" style="color:#2980b9;"></i> System Login</h2>
        <?php if($error) echo "<p style='color:#e74c3c; background:#fdedec; padding:10px; border-radius:4px; font-size:14px; margin-bottom:20px;'><i class='fas fa-exclamation-circle'></i> $error</p>"; ?>
        
       <form method="POST">
            <input type="text" name="login_identifier" class="form-control" placeholder="Username or Email" required autocomplete="username">
            <input type="password" name="password" class="form-control" placeholder="Password" required autocomplete="current-password">
            <button type="submit" class="btn-submit">Sign In</button>
            
            <a href="register.php" style="display:block; margin-top:15px; color:#2980b9; text-decoration:none; font-size:14px;">Don't have an account? Register here</a>
            <a href="request_reset.php" style="display:block; margin-top:8px; color:#e67e22; text-decoration:none; font-size:13px;">Forgot Password?</a>
            <a href="landing.php" class="landing-link"><i class="fas fa-home"></i> View Landing Page</a>
        </form>
    </div>
</body>
</html>
<?php
// Prevent crashes from uncaught connection errors
mysqli_report(MYSQLI_REPORT_OFF);

// 1. Auto-load .env file if present
if (file_exists(__DIR__ . '/.env')) {
    $env_lines = @file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($env_lines) {
        foreach ($env_lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!getenv($name)) {
                putenv("$name=$value");
            }
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// 2. Parse Railway / Cloud URL or Individual Variables
$db_url = getenv('MYSQL_URL') ?: (getenv('DATABASE_URL') ?: ($_ENV['MYSQL_URL'] ?? ($_ENV['DATABASE_URL'] ?? '')));

if (!empty($db_url) && strpos($db_url, '://') !== false) {
    $parsed_url = parse_url($db_url);
    $host = $parsed_url['host'] ?? '127.0.0.1';
    $user = $parsed_url['user'] ?? 'root';
    $pass = $parsed_url['pass'] ?? '';
    $db   = isset($parsed_url['path']) ? ltrim($parsed_url['path'], '/') : 'railway';
    $port = isset($parsed_url['port']) ? intval($parsed_url['port']) : 3306;
} else {
    $host = getenv('MYSQLHOST') ?: (getenv('MYSQL_HOST') ?: (getenv('DB_HOST') ?: ($_ENV['MYSQLHOST'] ?? ($_ENV['MYSQL_HOST'] ?? ($_ENV['DB_HOST'] ?? '127.0.0.1')))));
    $user = getenv('MYSQLUSER') ?: (getenv('MYSQL_USER') ?: (getenv('DB_USER') ?: ($_ENV['MYSQLUSER'] ?? ($_ENV['MYSQL_USER'] ?? ($_ENV['DB_USER'] ?? 'root')))));
    $pass = getenv('MYSQLPASSWORD') ?: (getenv('MYSQL_PASSWORD') ?: (getenv('DB_PASSWORD') ?: ($_ENV['MYSQLPASSWORD'] ?? ($_ENV['MYSQL_PASSWORD'] ?? ($_ENV['DB_PASSWORD'] ?? '')))));
    $db   = getenv('MYSQLDATABASE') ?: (getenv('MYSQL_DATABASE') ?: (getenv('DB_NAME') ?: ($_ENV['MYSQLDATABASE'] ?? ($_ENV['MYSQL_DATABASE'] ?? ($_ENV['DB_NAME'] ?? 'railway')))));
    $port = intval(getenv('MYSQLPORT') ?: (getenv('MYSQL_PORT') ?: (getenv('DB_PORT') ?: ($_ENV['MYSQLPORT'] ?? ($_ENV['MYSQL_PORT'] ?? ($_ENV['DB_PORT'] ?? 3306))))));
}

// 3. Connect to Database
$conn = @mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    // If the specific database doesn't exist yet, try connecting without DB and creating it
    $conn_raw = @mysqli_connect($host, $user, $pass, null, $port);
    if ($conn_raw) {
        @mysqli_query($conn_raw, "CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        @mysqli_close($conn_raw);
        $conn = @mysqli_connect($host, $user, $pass, $db, $port);
    }
}

if (!$conn) {
    die("<div style='font-family:sans-serif; padding:30px; background:#fff3f3; color:#c0392b; border:1px solid #ebccd1; border-radius:8px; margin:30px auto; max-width:600px;'>
        <h2 style='margin-top:0;'>Database Connection Error</h2>
        <p>Could not connect to MySQL server at <strong>" . htmlspecialchars($host) . ":" . htmlspecialchars($port) . "</strong>.</p>
        <p><strong>Error details:</strong> " . htmlspecialchars(mysqli_connect_error()) . "</p>
        <hr style='border:0; border-top:1px solid #e0b4b4; margin:15px 0;'>
        <p style='font-size:13px; color:#555;'>If you are deploying on <strong>Railway</strong>, ensure your MySQL service is added and environment variables (<code>MYSQLHOST</code>, <code>MYSQLUSER</code>, <code>MYSQLPASSWORD</code>, <code>MYSQLDATABASE</code>, <code>MYSQLPORT</code> or <code>MYSQL_URL</code>) are configured.</p>
    </div>");
}

@mysqli_set_charset($conn, "utf8mb4");

// 4. Ensure Upload Folders Exist
$upload_dirs = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/profiles'
];
foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

// 5. Automatic Schema Verification & Initialization
$check_table = @mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (!$check_table || mysqli_num_rows($check_table) == 0) {
    // Create users table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        fullname VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('student_teacher', 'supervisor', 'admin') DEFAULT 'student_teacher',
        status VARCHAR(50) DEFAULT 'active',
        college VARCHAR(255) NULL,
        course VARCHAR(255) NULL,
        year_level VARCHAR(50) NULL,
        section VARCHAR(50) NULL,
        birthdate DATE NULL,
        age INT NULL,
        gender VARCHAR(50) NULL,
        address TEXT NULL,
        partner_school VARCHAR(255) NULL,
        assigned_supervisor_id INT NULL,
        profile_pic VARCHAR(500) NULL,
        reset_request INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create submissions table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        file_path VARCHAR(500) NULL,
        chat_transcript LONGTEXT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create evaluations table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS evaluations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        evaluator_id INT NOT NULL,
        submission_id INT DEFAULT 0,
        evaluation_title VARCHAR(255) NOT NULL,
        competency_score INT NOT NULL,
        readiness_notes LONGTEXT NULL,
        file_path VARCHAR(500) NULL,
        status VARCHAR(50) DEFAULT 'pending',
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create chat_logs table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS chat_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        sender ENUM('user', 'ai') NOT NULL,
        message LONGTEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// 6. Seed Default Admin Account if no admin exists
$admin_check = @mysqli_query($conn, "SELECT id FROM users WHERE role='admin' LIMIT 1");
if ($admin_check && mysqli_num_rows($admin_check) == 0) {
    $default_admin_user = 'admin';
    $default_admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $default_admin_name = 'System Administrator';
    $default_admin_mail = 'admin@core-evaluation.edu';
    
    $seed_stmt = mysqli_prepare($conn, "INSERT INTO users (username, fullname, email, password, role, status) VALUES (?, ?, ?, ?, 'admin', 'active')");
    if ($seed_stmt) {
        mysqli_stmt_bind_param($seed_stmt, "ssss", $default_admin_user, $default_admin_name, $default_admin_mail, $default_admin_pass);
        mysqli_stmt_execute($seed_stmt);
    }
}
?>
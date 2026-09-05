-- Database Schema for Competency and Readiness Evaluation System (CORE)

CREATE TABLE IF NOT EXISTS users (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    file_path VARCHAR(500) NULL,
    chat_transcript LONGTEXT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS evaluations (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sender ENUM('user', 'ai') NOT NULL,
    message LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

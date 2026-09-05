<?php
// landing.php - AI Lesson Plan Evaluator Landing Page
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$dashboard_url = 'dashboard.php';
if ($is_logged_in) {
    if (($_SESSION['role'] ?? '') === 'admin') $dashboard_url = 'admin_dashboard.php';
    elseif (($_SESSION['role'] ?? '') === 'supervisor') $dashboard_url = 'supervisor_dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CORE - Competency and Evaluation Readiness</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a, #1e3a8a, #2563eb);
            min-height: 100vh;
            color: white;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 8%;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 1.6rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-buttons a {
            text-decoration: none;
            margin-left: 12px;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: bold;
            transition: 0.3s ease;
        }

        .login-btn {
            background: white;
            color: #1e3a8a;
        }

        .register-btn {
            background: transparent;
            border: 2px solid white;
            color: white;
        }

        .nav-buttons a:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        .hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 80px 8%;
            flex-wrap: wrap;
            gap: 40px;
        }

        .hero-text {
            flex: 1;
            min-width: 320px;
        }

        .hero-text h1 {
            font-size: 3.4rem;
            line-height: 1.2;
            margin-bottom: 20px;
        }

        .hero-text p {
            font-size: 1.15rem;
            line-height: 1.8;
            color: #dbeafe;
            margin-bottom: 30px;
        }

        .highlight-tagline {
            display: inline-block;
            background: linear-gradient(90deg, #facc15, #fb7185);
            color: #0f172a;
            padding: 12px 18px;
            border-radius: 14px;
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
        }

        .cta-buttons a {
            display: inline-block;
            text-decoration: none;
            padding: 14px 26px;
            border-radius: 12px;
            margin-right: 15px;
            font-weight: bold;
            transition: 0.3s ease;
        }

        .primary-btn {
            background: white;
            color: #1e3a8a;
        }

        .secondary-btn {
            border: 2px solid white;
            color: white;
        }

        .cta-buttons a:hover {
            transform: scale(1.05);
        }

        .hero-card {
            flex: 1;
            min-width: 300px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
        }

        .hero-card h3 {
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .feature {
            margin-bottom: 18px;
            padding: 14px;
            background: rgba(255,255,255,0.08);
            border-radius: 12px;
        }

        .features-section {
            padding: 70px 8%;
            background: rgba(0,0,0,0.15);
        }

        .features-section h2 {
            text-align: center;
            font-size: 2.4rem;
            margin-bottom: 40px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .feature-box {
            background: rgba(255,255,255,0.1);
            padding: 25px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .feature-box h3 {
            margin-bottom: 12px;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #dbeafe;
        }

        @media (max-width: 768px) {
            .hero-text h1 {
                font-size: 2.3rem;
            }

            .navbar {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo">🎓 CORE</div>
        <div class="nav-buttons">
            <?php if ($is_logged_in): ?>
                <a href="<?php echo $dashboard_url; ?>" class="login-btn">My Dashboard</a>
                <a href="logout.php" class="register-btn">Logout</a>
            <?php else: ?>
                <a href="index.php" class="login-btn">Login</a>
                <a href="register.php" class="register-btn">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-text">
            <h1>CORE<br><span style="font-size: 2rem; color: #dbeafe;">Competency and Evaluation Readiness</span></h1>
            <div class="highlight-tagline">✨ Your virtual consultant for your teaching journey starts here</div>
            <p>
                Upload student-created lesson plans and receive instant AI-based evaluation,
                curriculum alignment checks, teaching strategy analysis, rubric-based scoring,
                and personalized improvement recommendations.
            </p>
            <div class="cta-buttons">
                <?php if ($is_logged_in): ?>
                    <a href="<?php echo $dashboard_url; ?>" class="primary-btn">Go to Dashboard</a>
                <?php else: ?>
                    <a href="index.php" class="primary-btn">Get Started</a>
                    <a href="register.php" class="secondary-btn">Create Account</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="hero-card">
            <h3>What AI Evaluates</h3>
            <div class="feature">📘 Lesson Objectives Alignment (HOTS)</div>
            <div class="feature">🧠 Teaching Methodology & Strategies</div>
            <div class="feature">📊 Assessment & Evaluation Tool Validity</div>
            <div class="feature">✍️ Structure, Clarity, and Professional Flow</div>
            <div class="feature">🎯 PPST Standards & Curriculum Compliance</div>
        </div>
    </section>

    <section class="features-section">
        <h2>Why Choose CORE?</h2>
        <div class="feature-grid">
            <div class="feature-box">
                <h3>⚡ Instant Feedback</h3>
                <p>Eliminate long manual checking by generating evaluations in seconds.</p>
            </div>
            <div class="feature-box">
                <h3>📋 100-Point Rubric</h3>
                <p>Evaluate lesson plans using standardized Philippine teacher education criteria.</p>
            </div>
            <div class="feature-box">
                <h3>💡 Support Copilot</h3>
                <p>Interactive AI mentor providing actionable suggestions to strengthen teaching plans.</p>
            </div>
            <div class="feature-box">
                <h3>🚀 Cloud Ready</h3>
                <p>Lightweight, resilient deployment structure ready for Railway and modern hosting.</p>
            </div>
        </div>
    </section>

    <footer>
        © <?php echo date('Y'); ?> CORE (Competency and Evaluation Readiness) — Your Virtual Consultant for Teaching Excellence
    </footer>

</body>
</html>
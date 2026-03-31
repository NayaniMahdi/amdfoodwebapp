<?php
/**
 * Nouriq — Landing Page
 */
require_once __DIR__ . '/includes/functions.php';
startSecureSession();

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Nouriq — Your Intelligent Nutrition Coach. Track food, build healthy habits, and get personalized recommendations.">
    <title>Nouriq — Smart Nutrition Coach</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>main.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>auth.css">
    <style>
        .landing-hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: var(--space-xl);
            position: relative;
            overflow: hidden;
        }
        .landing-hero::before {
            content: '';
            position: absolute;
            top: -20%; left: -20%;
            width: 60vw; height: 60vw;
            background: radial-gradient(circle, rgba(108,92,231,0.15), transparent 60%);
            animation: float 12s ease-in-out infinite;
        }
        .landing-hero::after {
            content: '';
            position: absolute;
            bottom: -30%; right: -20%;
            width: 50vw; height: 50vw;
            background: radial-gradient(circle, rgba(0,206,201,0.1), transparent 60%);
            animation: float 15s ease-in-out infinite reverse;
        }

        .hero-content { position: relative; z-index: 2; max-width: 700px; }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            border-radius: var(--radius-full);
            background: var(--bg-glass);
            border: 1px solid var(--border);
            font-size: var(--text-sm);
            color: var(--accent-light);
            margin-bottom: var(--space-xl);
            animation: fadeInUp 0.6s ease;
        }

        .hero-title {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: var(--space-lg);
            animation: fadeInUp 0.6s ease 0.1s backwards;
        }
        .hero-title span {
            background: linear-gradient(135deg, var(--accent), #00cec9, var(--accent-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            background-size: 200% auto;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% center; }
            50% { background-position: 100% center; }
        }

        .hero-subtitle {
            font-size: var(--text-lg);
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: var(--space-2xl);
            max-width: 540px;
            margin-left: auto;
            margin-right: auto;
            animation: fadeInUp 0.6s ease 0.2s backwards;
        }

        .hero-actions {
            display: flex;
            gap: var(--space-md);
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 0.6s ease 0.3s backwards;
        }

        .hero-features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: var(--space-lg);
            margin-top: var(--space-3xl);
            max-width: 800px;
            animation: fadeInUp 0.6s ease 0.4s backwards;
        }

        .hero-feature {
            padding: var(--space-lg);
            border-radius: var(--radius-lg);
            background: var(--bg-glass);
            border: 1px solid var(--border);
            text-align: center;
            transition: all var(--transition-base);
        }
        .hero-feature:hover {
            border-color: var(--border-hover);
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        .hero-feature .feature-icon {
            font-size: 32px;
            margin-bottom: var(--space-sm);
        }
        .hero-feature .feature-title {
            font-weight: 600;
            font-size: var(--text-sm);
            margin-bottom: 4px;
        }
        .hero-feature .feature-desc {
            font-size: var(--text-xs);
            color: var(--text-secondary);
        }

        .landing-nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 100;
            background: rgba(10,10,15,0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
        }
        [data-theme="light"] .landing-nav { background: rgba(245,246,250,0.9); }
        .landing-nav .nav-logo {
            font-size: 22px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--text-primary), var(--accent-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        @media (max-width: 768px) {
            .hero-features { grid-template-columns: 1fr; max-width: 400px; }
            .landing-nav { padding: 12px 16px; }
        }
    </style>
</head>
<body>
    <div class="auth-bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <nav class="landing-nav">
        <div class="nav-logo">🧬 Nouriq</div>
        <div class="flex gap-sm">
            <a href="auth/login.php" class="btn btn-ghost btn-sm">Sign In</a>
            <a href="auth/register.php" class="btn btn-primary btn-sm">Get Started</a>
        </div>
    </nav>

    <section class="landing-hero">
        <div class="hero-content">
            <div class="hero-badge">
                ✨ Your Intelligent Nutrition Coach
            </div>
            <h1 class="hero-title">
                Eat Smart.<br><span>Live Better.</span>
            </h1>
            <p class="hero-subtitle">
                Nouriq helps you make better food decisions, build sustainable eating habits, and improve your health with data-driven insights and personalized recommendations.
            </p>
            <div class="hero-actions">
                <a href="auth/register.php" class="btn btn-primary btn-lg">
                    Start Free →
                </a>
                <a href="auth/login.php" class="btn btn-secondary btn-lg">
                    Sign In
                </a>
            </div>

            <div class="hero-features">
                <div class="hero-feature">
                    <div class="feature-icon">🔍</div>
                    <div class="feature-title">Smart Tracking</div>
                    <div class="feature-desc">Log meals from 120+ food items with auto-calculated nutrition data</div>
                </div>
                <div class="hero-feature">
                    <div class="feature-icon">🧠</div>
                    <div class="feature-title">AI Insights</div>
                    <div class="feature-desc">Get behavior analysis, pattern detection, and actionable recommendations</div>
                </div>
                <div class="hero-feature">
                    <div class="feature-icon">🏆</div>
                    <div class="feature-title">Gamification</div>
                    <div class="feature-desc">Earn points, unlock achievements, and build healthy streaks</div>
                </div>
            </div>
        </div>
    </section>

    <script src="<?php echo JS_PATH; ?>theme.js"></script>
</body>
</html>

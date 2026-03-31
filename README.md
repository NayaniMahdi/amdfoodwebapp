# Nouriq — Intelligent Nutrition Coach 🧬

Nouriq is a premium, AI-driven nutrition coaching platform built with PHP, MySQL, and vanilla JavaScript. Unlike basic calorie counters, Nouriq behaves like a personal nutrition coach by analyzing your eating patterns, identifying unhealthy habits, and providing actionable, real-time insights to help you build sustainable health habits.

## ✨ Core Features

### 🍱 Intelligent Food Logging
- **120+ Seeded Food Items**: Search a categorized, pre-populated database.
- **Dynamic Serving Sizes**: Adjust portion sizes with real-time macro and calorie recalculations.
- **Custom Foods**: Create and log your own unique food items.

### 🧠 9-Rule Recommendation Engine
Nouriq analyzes your behavior to provide contextual insights:
1. **Calorie Alerts**: Warns when you are in a surplus or extreme deficit.
2. **Protein Tracking**: Monitors if you are hitting your lean mass targets.
3. **Meal Skipping**: Detects if you regularly skip breakfast or lunch.
4. **Late-Night Eating**: Flags unhealthy eating patterns after 9 PM.
5. **Smart Food Swaps**: Maps and suggests healthy alternatives to 18 common junk foods.
6. **Junk Food Ratio**: Warns if your junk-to-healthy food ratio exceeds healthy limits.
7. **Weekly Trends**: Identifies sustained over/under eating over 7-day periods.
8. **Logging Reminders**: Motivates you to start tracking if you forget.
9. **Hydration Prompts**: Time-based water consumption reminders.

### 🏆 Gamification & Progression
- **Points & Levels**: Earn points for logging food and making healthy choices to level up.
- **Streaks**: Maintain streaks for Daily Logging, No-Junk, Protein Targets, and Calorie Compliance.
- **Achievements**: Unlock 8 different achievement badges based on your milestones.

### 📊 Behavioral Analytics & Charts
- **30-Day Health Ratio**: Visualize the percentage of healthy choices over the last month.
- **Custom Zero-Dependency Charts**: Animated Canvas-based donut, bar, and area line charts providing deep visual insights without bulky external libraries.

### 🎨 Premium UI / UX
- **Glassmorphism Design**: A stunning, modern dark theme utilizing CSS variables and backdrop blurs.
- **Responsive Animations**: Beautiful entrance animations and interactive hover states.

## 🛠️ Tech Stack

- **Frontend**: HTML5, CSS3 (70+ Custom CSS Tokens), Vanilla JavaScript (Zero Dependencies)
- **Backend**: Core PHP 8+ (Secure, Modular Structure with PDO)
- **Database**: MySQL (10 Optimized Tables)
- **Security**: Bcrypt Password Hashing, CSRF Protection, HTTPOnly Sessions

## 🚀 Getting Started

### Prerequisites
- XAMPP / WAMP / LAMP stack (PHP 8.0+ and MySQL)

### Installation
1. **Clone the repository** to your local web root directory (e.g., `htdocs` for XAMPP):
   ```bash
   git clone https://github.com/YourUsername/nouriq.git amdfoodapp
   ```
2. **Configure Web Server**: Ensure your web server points to the project directory.
3. **Run the Installer**:
   - Open your browser and navigate to `http://localhost/amdfoodapp/install.php`
   - The script will automatically create the `nouriq_db` database, build 10 tables, and seed over 200 rows of food and achievement data.
4. **Log into the Admin Panel**:
   - The installer creates a default admin account.
   - **Email**: `admin@nouriq.com`
   - **Password**: `Admin@123`
5. **Complete your Profile**: Register a new user account, enter your stats, and Nouriq will calculate your tailored BMI and daily calorie goals.

## 🔐 Security Built-In
- **CSRF Tokens**: Defends against Cross-Site Request Forgeries on all forms.
- **Input Sanitization**: All data is securely sanitized before hitting the database.
- **Secure Sessions**: Prevents session hijacking securely routing via middleware (`auth-check.php` & `admin-check.php`).

---
*Built to make healthy eating effortless and beautiful.*

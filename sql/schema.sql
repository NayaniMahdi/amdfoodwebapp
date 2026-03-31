-- ============================================================
-- NOURIQ — Food & Health Intelligence Platform
-- Database Schema + Seed Data
-- ============================================================

CREATE DATABASE IF NOT EXISTS nouriq_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nouriq_db;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    remember_token VARCHAR(255) NULL,
    reset_token VARCHAR(255) NULL,
    reset_expires DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_remember_token (remember_token),
    INDEX idx_reset_token (reset_token)
) ENGINE=InnoDB;

-- ============================================================
-- PROFILES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    first_name VARCHAR(100) DEFAULT '',
    last_name VARCHAR(100) DEFAULT '',
    age INT NULL,
    gender ENUM('male','female','other') NULL,
    height_cm DECIMAL(5,1) NULL,
    weight_kg DECIMAL(5,1) NULL,
    bmi DECIMAL(4,1) NULL,
    activity_level ENUM('sedentary','light','moderate','active','very_active') DEFAULT 'moderate',
    diet_type ENUM('omnivore','vegetarian','vegan','keto','paleo','mediterranean') DEFAULT 'omnivore',
    health_goal ENUM('weight_loss','muscle_gain','maintenance','general_health') DEFAULT 'maintenance',
    allergies JSON NULL,
    daily_calorie_target INT DEFAULT 2000,
    daily_protein_target INT DEFAULT 50,
    daily_carb_target INT DEFAULT 250,
    daily_fat_target INT DEFAULT 65,
    avatar_url VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- FOOD ITEMS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS food_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category ENUM('fruits','vegetables','grains','protein','dairy','fast_food','snacks','beverages','desserts','other') NOT NULL,
    serving_size VARCHAR(100) NOT NULL DEFAULT '100g',
    calories DECIMAL(7,1) NOT NULL DEFAULT 0,
    protein_g DECIMAL(6,1) NOT NULL DEFAULT 0,
    carbs_g DECIMAL(6,1) NOT NULL DEFAULT 0,
    fat_g DECIMAL(6,1) NOT NULL DEFAULT 0,
    fiber_g DECIMAL(6,1) NOT NULL DEFAULT 0,
    sugar_g DECIMAL(6,1) NOT NULL DEFAULT 0,
    is_healthy TINYINT(1) NOT NULL DEFAULT 1,
    tags JSON NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_category (category),
    INDEX idx_is_healthy (is_healthy),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- FOOD LOGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS food_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_item_id INT NOT NULL,
    meal_type ENUM('breakfast','lunch','dinner','snack') NOT NULL,
    servings DECIMAL(4,1) NOT NULL DEFAULT 1.0,
    calories DECIMAL(7,1) NOT NULL DEFAULT 0,
    protein_g DECIMAL(6,1) NOT NULL DEFAULT 0,
    carbs_g DECIMAL(6,1) NOT NULL DEFAULT 0,
    fat_g DECIMAL(6,1) NOT NULL DEFAULT 0,
    logged_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_date (user_id, logged_at),
    INDEX idx_meal_type (meal_type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_item_id) REFERENCES food_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- RECOMMENDATIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('meal_suggestion','food_swap','alert','tip','meal_plan') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_read (user_id, is_read),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICATIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('meal_reminder','hydration','behavior_alert','health_tip','achievement') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    icon VARCHAR(50) DEFAULT '🔔',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_read (user_id, is_read),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ACHIEVEMENTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(100) NOT NULL DEFAULT '🏆',
    points INT NOT NULL DEFAULT 10,
    condition_type VARCHAR(100) NOT NULL,
    condition_value INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- USER ACHIEVEMENTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- USER STREAKS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS user_streaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    streak_type VARCHAR(100) NOT NULL,
    current_count INT NOT NULL DEFAULT 0,
    best_count INT NOT NULL DEFAULT 0,
    last_logged_date DATE NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_streak (user_id, streak_type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- USER POINTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS user_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    total_points INT NOT NULL DEFAULT 0,
    level INT NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA: ACHIEVEMENTS
-- ============================================================
INSERT INTO achievements (name, description, icon, points, condition_type, condition_value) VALUES
('First Bite', 'Log your first meal', '🍽️', 10, 'total_logs', 1),
('Logging Streak 3', 'Log meals for 3 consecutive days', '🔥', 25, 'log_streak', 3),
('Logging Streak 7', 'Log meals for 7 consecutive days', '⚡', 50, 'log_streak', 7),
('Logging Streak 30', 'Log meals for 30 consecutive days', '💎', 200, 'log_streak', 30),
('No Junk 3', 'Avoid junk food for 3 days', '🥬', 30, 'no_junk_streak', 3),
('No Junk 5', 'Avoid junk food for 5 days', '🥗', 50, 'no_junk_streak', 5),
('No Junk 14', 'Avoid junk food for 14 days', '🏅', 150, 'no_junk_streak', 14),
('Protein Master', 'Hit protein target for 7 days', '💪', 75, 'protein_streak', 7),
('Calorie Champion', 'Stay within calorie goal for 7 days', '🎯', 75, 'calorie_streak', 7),
('Hydration Hero', 'Log water for 5 consecutive days', '💧', 40, 'hydration_streak', 5),
('Meal Planner', 'Log all 3 main meals in a single day', '📋', 20, 'full_day_log', 1),
('Century Club', 'Log 100 total meals', '💯', 100, 'total_logs', 100),
('Veggie Lover', 'Log 20 vegetable servings', '🥦', 60, 'veggie_count', 20),
('Fruit Fanatic', 'Log 15 fruit servings', '🍎', 50, 'fruit_count', 15),
('Early Bird', 'Log breakfast before 9 AM for 5 days', '🌅', 40, 'early_breakfast', 5),
('Balanced Warrior', 'Hit all macro targets in one day', '⚖️', 50, 'balanced_day', 1);

-- ============================================================
-- SEED DATA: FOOD ITEMS (120+ items with real nutritional data)
-- ============================================================

-- FRUITS
INSERT INTO food_items (name, category, serving_size, calories, protein_g, carbs_g, fat_g, fiber_g, sugar_g, is_healthy, tags) VALUES
('Apple', 'fruits', '1 medium (182g)', 95.0, 0.5, 25.1, 0.3, 4.4, 18.9, 1, '["low-calorie","high-fiber"]'),
('Banana', 'fruits', '1 medium (118g)', 105.0, 1.3, 27.0, 0.4, 3.1, 14.4, 1, '["high-potassium","energy-boost"]'),
('Orange', 'fruits', '1 medium (131g)', 62.0, 1.2, 15.4, 0.2, 3.1, 12.2, 1, '["high-vitamin-c","low-calorie"]'),
('Strawberries', 'fruits', '1 cup (152g)', 49.0, 1.0, 11.7, 0.5, 3.0, 7.4, 1, '["antioxidant","low-calorie"]'),
('Blueberries', 'fruits', '1 cup (148g)', 84.0, 1.1, 21.4, 0.5, 3.6, 14.7, 1, '["antioxidant","brain-health"]'),
('Mango', 'fruits', '1 cup (165g)', 99.0, 1.4, 24.7, 0.6, 2.6, 22.5, 1, '["high-vitamin-a","tropical"]'),
('Grapes', 'fruits', '1 cup (151g)', 104.0, 1.1, 27.3, 0.2, 1.4, 23.4, 1, '["antioxidant","natural-sugar"]'),
('Watermelon', 'fruits', '1 cup (152g)', 46.0, 0.9, 11.5, 0.2, 0.6, 9.4, 1, '["hydrating","low-calorie"]'),
('Pineapple', 'fruits', '1 cup (165g)', 82.0, 0.9, 21.6, 0.2, 2.3, 16.3, 1, '["digestive-aid","tropical"]'),
('Avocado', 'fruits', '1 whole (200g)', 322.0, 4.0, 17.1, 29.5, 13.5, 1.3, 1, '["healthy-fats","keto-friendly"]'),
('Kiwi', 'fruits', '1 medium (76g)', 42.0, 0.8, 10.1, 0.4, 2.1, 6.2, 1, '["high-vitamin-c","digestive-aid"]'),
('Papaya', 'fruits', '1 cup (145g)', 62.0, 0.7, 15.7, 0.4, 2.5, 11.3, 1, '["digestive-aid","tropical"]'),
('Pomegranate', 'fruits', '1/2 cup seeds (87g)', 72.0, 1.5, 16.3, 1.0, 3.5, 11.9, 1, '["antioxidant","heart-health"]');

-- VEGETABLES
INSERT INTO food_items (name, category, serving_size, calories, protein_g, carbs_g, fat_g, fiber_g, sugar_g, is_healthy, tags) VALUES
('Broccoli', 'vegetables', '1 cup (91g)', 31.0, 2.6, 6.0, 0.3, 2.4, 1.5, 1, '["high-fiber","low-calorie","superfood"]'),
('Spinach', 'vegetables', '1 cup raw (30g)', 7.0, 0.9, 1.1, 0.1, 0.7, 0.1, 1, '["iron-rich","low-calorie","superfood"]'),
('Carrot', 'vegetables', '1 medium (61g)', 25.0, 0.6, 5.8, 0.1, 1.7, 2.9, 1, '["high-vitamin-a","low-calorie"]'),
('Sweet Potato', 'vegetables', '1 medium (130g)', 103.0, 2.3, 23.6, 0.1, 3.8, 4.8, 1, '["complex-carbs","high-fiber"]'),
('Bell Pepper', 'vegetables', '1 medium (119g)', 31.0, 1.0, 6.0, 0.3, 2.1, 4.2, 1, '["high-vitamin-c","low-calorie"]'),
('Tomato', 'vegetables', '1 medium (123g)', 22.0, 1.1, 4.8, 0.2, 1.5, 3.2, 1, '["lycopene","low-calorie"]'),
('Cucumber', 'vegetables', '1 cup (104g)', 16.0, 0.7, 3.1, 0.2, 0.5, 1.7, 1, '["hydrating","low-calorie"]'),
('Cauliflower', 'vegetables', '1 cup (107g)', 27.0, 2.1, 5.3, 0.3, 2.1, 2.0, 1, '["low-carb","keto-friendly"]'),
('Kale', 'vegetables', '1 cup chopped (67g)', 33.0, 2.9, 6.0, 0.6, 1.3, 0.0, 1, '["superfood","high-iron","low-calorie"]'),
('Green Beans', 'vegetables', '1 cup (125g)', 34.0, 2.0, 7.9, 0.1, 3.4, 1.5, 1, '["high-fiber","low-calorie"]'),
('Mushrooms', 'vegetables', '1 cup (70g)', 15.0, 2.2, 2.3, 0.2, 0.7, 1.4, 1, '["low-calorie","vitamin-d"]'),
('Onion', 'vegetables', '1 medium (110g)', 44.0, 1.2, 10.3, 0.1, 1.9, 4.7, 1, '["prebiotic","anti-inflammatory"]'),
('Zucchini', 'vegetables', '1 medium (196g)', 33.0, 2.4, 6.1, 0.6, 2.0, 4.9, 1, '["low-calorie","low-carb"]');

-- GRAINS
INSERT INTO food_items (name, category, serving_size, calories, protein_g, carbs_g, fat_g, fiber_g, sugar_g, is_healthy, tags) VALUES
('Brown Rice', 'grains', '1 cup cooked (195g)', 216.0, 5.0, 44.8, 1.8, 3.5, 0.7, 1, '["complex-carbs","whole-grain"]'),
('White Rice', 'grains', '1 cup cooked (186g)', 206.0, 4.3, 44.5, 0.4, 0.6, 0.1, 1, '["staple","energy"]'),
('Oats', 'grains', '1/2 cup dry (40g)', 150.0, 5.0, 27.0, 3.0, 4.0, 1.0, 1, '["high-fiber","heart-healthy","breakfast"]'),
('Quinoa', 'grains', '1 cup cooked (185g)', 222.0, 8.1, 39.4, 3.6, 5.2, 0.0, 1, '["complete-protein","gluten-free"]'),
('Whole Wheat Bread', 'grains', '1 slice (28g)', 69.0, 3.6, 11.6, 1.2, 1.9, 1.4, 1, '["whole-grain","fiber"]'),
('White Bread', 'grains', '1 slice (25g)', 67.0, 2.0, 12.7, 0.8, 0.6, 1.3, 0, '["refined","low-fiber"]'),
('Pasta (cooked)', 'grains', '1 cup (140g)', 220.0, 8.1, 43.2, 1.3, 2.5, 0.8, 1, '["energy","complex-carbs"]'),
('Corn Tortilla', 'grains', '1 tortilla (26g)', 52.0, 1.4, 10.7, 0.7, 1.5, 0.2, 1, '["gluten-free","whole-grain"]'),
('Granola', 'grains', '1/2 cup (61g)', 299.0, 7.3, 32.5, 14.7, 3.6, 11.5, 0, '["high-calorie","breakfast"]'),
('Couscous', 'grains', '1 cup cooked (157g)', 176.0, 6.0, 36.5, 0.3, 2.2, 0.0, 1, '["quick-cook","light"]');

-- PROTEIN
INSERT INTO food_items (name, category, serving_size, calories, protein_g, carbs_g, fat_g, fiber_g, sugar_g, is_healthy, tags) VALUES
('Chicken Breast (grilled)', 'protein', '100g', 165.0, 31.0, 0.0, 3.6, 0.0, 0.0, 1, '["high-protein","lean","muscle-building"]'),
('Salmon (baked)', 'protein', '100g', 208.0, 20.4, 0.0, 13.4, 0.0, 0.0, 1, '["omega-3","high-protein","heart-healthy"]'),
('Eggs (boiled)', 'protein', '1 large (50g)', 78.0, 6.3, 0.6, 5.3, 0.0, 0.6, 1, '["complete-protein","breakfast"]'),
('Tuna (canned)', 'protein', '100g', 116.0, 25.5, 0.0, 0.8, 0.0, 0.0, 1, '["high-protein","lean","low-calorie"]'),
('Turkey Breast', 'protein', '100g', 135.0, 30.0, 0.0, 1.0, 0.0, 0.0, 1, '["high-protein","lean"]'),
('Shrimp', 'protein', '100g', 85.0, 20.1, 0.0, 0.5, 0.0, 0.0, 1, '["high-protein","low-calorie","low-fat"]'),
('Tofu (firm)', 'protein', '100g', 144.0, 17.3, 2.8, 8.7, 2.3, 0.0, 1, '["plant-protein","vegan","low-carb"]'),
('Lentils (cooked)', 'protein', '1 cup (198g)', 230.0, 17.9, 39.9, 0.8, 15.6, 3.6, 1, '["plant-protein","high-fiber","iron-rich"]'),
('Chickpeas (cooked)', 'protein', '1 cup (164g)', 269.0, 14.5, 45.0, 4.2, 12.5, 7.9, 1, '["plant-protein","high-fiber"]'),
('Paneer', 'protein', '100g', 265.0, 18.3, 1.2, 20.8, 0.0, 0.0, 1, '["high-protein","calcium","vegetarian"]'),
('Beef (lean, grilled)', 'protein', '100g', 250.0, 26.0, 0.0, 15.0, 0.0, 0.0, 1, '["high-protein","iron-rich"]'),
('Black Beans', 'protein', '1 cup cooked (172g)', 227.0, 15.2, 40.8, 0.9, 15.0, 0.6, 1, '["plant-protein","high-fiber","vegan"]'),
('Greek Yogurt', 'protein', '1 cup (245g)', 130.0, 22.0, 8.0, 0.7, 0.0, 7.0, 1, '["high-protein","probiotic","calcium"]'),
('Cottage Cheese', 'protein', '1 cup (226g)', 206.0, 28.0, 6.2, 9.0, 0.0, 5.6, 1, '["high-protein","calcium"]'),
('Whey Protein Shake', 'protein', '1 scoop (30g)', 120.0, 24.0, 3.0, 1.5, 0.5, 1.0, 1, '["high-protein","supplement","muscle-building"]');

-- DAIRY
INSERT INTO food_items (name, category, serving_size, calories, protein_g, carbs_g, fat_g, fiber_g, sugar_g, is_healthy, tags) VALUES
('Whole Milk', 'dairy', '1 cup (244ml)', 149.0, 8.0, 12.0, 8.0, 0.0, 12.0, 1, '["calcium","vitamin-d"]'),
('Skim Milk', 'dairy', '1 cup (245ml)', 83.0, 8.3, 12.2, 0.2, 0.0, 12.5, 1, '["low-fat","calcium","high-protein"]'),
('Cheddar Cheese', 'dairy', '1 oz (28g)', 113.0, 7.1, 0.4, 9.3, 0.0, 0.1, 1, '["calcium","keto-friendly"]'),
('Mozzarella', 'dairy', '1 oz (28g)', 85.0, 6.3, 0.7, 6.3, 0.0, 0.3, 1, '["calcium","low-carb"]'),
('Butter', 'dairy', '1 tbsp (14g)', 102.0, 0.1, 0.0, 11.5, 0.0, 0.0, 0, '["high-fat","keto-friendly"]'),
('Plain Yogurt', 'dairy', '1 cup (245g)', 149.0, 8.5, 11.4, 8.0, 0.0, 11.4, 1, '["probiotic","calcium"]'),
('Almond Milk', 'dairy', '1 cup (240ml)', 39.0, 1.0, 3.4, 2.5, 0.5, 2.1, 1, '["low-calorie","dairy-free","vegan"]'),
('Soy Milk', 'dairy', '1 cup (243ml)', 105.0, 6.3, 12.0, 3.6, 0.5, 8.9, 1, '["plant-based","protein","vegan"]');

-- FAST FOOD
INSERT INTO food_items (name, category, serving_size, calories, protein_g, carbs_g, fat_g, fiber_g, sugar_g, is_healthy, tags) VALUES
('Cheeseburger', 'fast_food', '1 burger', 535.0, 28.0, 40.0, 29.0, 2.0, 8.0, 0, '["high-calorie","high-fat","junk"]'),
('French Fries (large)', 'fast_food', '1 serving (154g)', 498.0, 5.3, 63.2, 24.4, 5.9, 0.4, 0, '["high-calorie","fried","junk"]'),
('Pepperoni Pizza', 'fast_food', '1 slice (107g)', 298.0, 13.3, 33.6, 12.2, 2.3, 3.8, 0, '["high-calorie","high-fat","junk"]'),
('Fried Chicken (2 pc)', 'fast_food', '2 pieces', 476.0, 34.5, 16.0, 30.0, 0.5, 0.3, 0, '["high-fat","fried","junk"]'),
('Hot Dog', 'fast_food', '1 hot dog', 290.0, 10.4, 24.3, 17.1, 0.8, 4.1, 0, '["processed","high-sodium","junk"]'),
('Chicken Nuggets', 'fast_food', '6 pieces', 286.0, 14.4, 17.6, 17.4, 0.9, 0.3, 0, '["fried","processed","junk"]'),
('Nachos with Cheese', 'fast_food', '1 serving (113g)', 346.0, 9.1, 36.3, 18.9, 2.8, 1.3, 0, '["high-calorie","high-fat","junk"]'),
('Beef Burrito', 'fast_food', '1 burrito', 431.0, 22.1, 49.8, 16.5, 4.2, 3.5, 0, '["high-calorie","filling"]'),
('Fish & Chips', 'fast_food', '1 serving', 585.0, 22.0, 52.0, 32.0, 3.0, 1.0, 0, '["fried","high-calorie","junk"]'),
('Onion Rings', 'fast_food', '8 rings', 276.0, 3.7, 31.3, 15.5, 0.9, 3.2, 0, '["fried","junk","high-calorie"]'),
('Samosa (fried)', 'fast_food', '1 piece (100g)', 262.0, 4.5, 28.0, 14.8, 2.1, 2.5, 0, '["fried","indian","junk"]'),
('Butter Chicken', 'fast_food', '1 cup (250g)', 438.0, 28.5, 12.0, 31.0, 1.5, 4.8, 0, '["high-fat","indian","rich"]');

-- SNACKS
INSERT INTO food_items (name, category, serving_size, calories, protein_g, carbs_g, fat_g, fiber_g, sugar_g, is_healthy, tags) VALUES
('Almonds', 'snacks', '1 oz (28g)', 164.0, 6.0, 6.1, 14.2, 3.5, 1.2, 1, '["healthy-fats","high-protein","heart-healthy"]'),
('Walnuts', 'snacks', '1 oz (28g)', 185.0, 4.3, 3.9, 18.5, 1.9, 0.7, 1, '["omega-3","healthy-fats","brain-health"]'),
('Peanut Butter', 'snacks', '2 tbsp (32g)', 188.0, 8.0, 6.0, 16.0, 1.9, 3.0, 1, '["high-protein","healthy-fats"]'),
('Trail Mix', 'snacks', '1/4 cup (38g)', 173.0, 5.0, 16.0, 11.0, 2.0, 10.0, 1, '["energy","hiking","mixed"]'),
('Potato Chips', 'snacks', '1 oz (28g)', 152.0, 2.0, 15.0, 9.8, 1.2, 0.1, 0, '["high-fat","salty","junk"]'),
('Dark Chocolate (70%)', 'snacks', '1 oz (28g)', 170.0, 2.2, 13.0, 12.0, 3.1, 6.8, 1, '["antioxidant","mood-boost"]'),
('Protein Bar', 'snacks', '1 bar (60g)', 210.0, 20.0, 22.0, 7.0, 3.0, 6.0, 1, '["high-protein","convenient"]'),
('Rice Cakes', 'snacks', '2 cakes (18g)', 70.0, 1.4, 14.8, 0.5, 0.4, 0.1, 1, '["low-calorie","gluten-free"]'),
('Popcorn (air-popped)', 'snacks', '3 cups (24g)', 93.0, 3.1, 18.7, 1.1, 3.6, 0.0, 1, '["high-fiber","low-calorie","whole-grain"]'),
('Cookies (chocolate chip)', 'snacks', '2 cookies (60g)', 280.0, 3.0, 38.0, 13.0, 1.0, 22.0, 0, '["high-sugar","treat","junk"]'),
('Candy Bar', 'snacks', '1 bar (52g)', 250.0, 4.0, 33.0, 12.0, 1.4, 25.0, 0, '["high-sugar","junk","treat"]'),
('Hummus', 'snacks', '2 tbsp (30g)', 70.0, 2.0, 4.0, 5.0, 1.0, 0.3, 1, '["plant-protein","healthy-fats"]'),
('Makhana (Fox Nuts)', 'snacks', '1 cup (32g)', 106.0, 3.9, 18.5, 0.6, 1.5, 0.0, 1, '["low-calorie","indian","healthy"]');

-- BEVERAGES
INSERT INTO food_items (name, category, serving_size, calories, protein_g, carbs_g, fat_g, fiber_g, sugar_g, is_healthy, tags) VALUES
('Black Coffee', 'beverages', '1 cup (237ml)', 2.0, 0.3, 0.0, 0.0, 0.0, 0.0, 1, '["zero-calorie","energy"]'),
('Green Tea', 'beverages', '1 cup (237ml)', 2.0, 0.5, 0.0, 0.0, 0.0, 0.0, 1, '["antioxidant","metabolism-boost"]'),
('Orange Juice', 'beverages', '1 cup (248ml)', 112.0, 1.7, 25.8, 0.5, 0.5, 20.8, 1, '["vitamin-c","natural-sugar"]'),
('Cola', 'beverages', '1 can (355ml)', 140.0, 0.0, 39.0, 0.0, 0.0, 39.0, 0, '["high-sugar","empty-calories","junk"]'),
('Coconut Water', 'beverages', '1 cup (240ml)', 46.0, 1.7, 8.9, 0.5, 2.6, 6.3, 1, '["electrolytes","hydrating"]'),
('Protein Smoothie', 'beverages', '1 glass (350ml)', 250.0, 20.0, 30.0, 5.0, 3.0, 18.0, 1, '["high-protein","meal-replacement"]'),
('Masala Chai', 'beverages', '1 cup (200ml)', 105.0, 3.0, 12.0, 4.5, 0.0, 10.0, 1, '["indian","warm","energizing"]'),
('Lemonade', 'beverages', '1 glass (240ml)', 99.0, 0.2, 26.0, 0.1, 0.2, 25.1, 0, '["high-sugar","refreshing"]'),
('Lassi (sweet)', 'beverages', '1 glass (200ml)', 160.0, 5.5, 22.0, 5.0, 0.0, 20.0, 1, '["probiotic","indian","calcium"]'),
('Water', 'beverages', '1 glass (240ml)', 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1, '["hydrating","essential"]');

-- DESSERTS
INSERT INTO food_items (name, category, serving_size, calories, protein_g, carbs_g, fat_g, fiber_g, sugar_g, is_healthy, tags) VALUES
('Vanilla Ice Cream', 'desserts', '1/2 cup (66g)', 137.0, 2.3, 15.6, 7.3, 0.5, 14.0, 0, '["high-sugar","treat","junk"]'),
('Chocolate Cake', 'desserts', '1 slice (95g)', 352.0, 5.0, 50.7, 14.3, 1.8, 36.3, 0, '["high-calorie","high-sugar","junk"]'),
('Gulab Jamun', 'desserts', '2 pieces (80g)', 300.0, 4.0, 40.0, 14.0, 0.3, 32.0, 0, '["high-sugar","indian","junk"]'),
('Brownie', 'desserts', '1 piece (56g)', 227.0, 2.7, 35.8, 9.1, 1.2, 21.0, 0, '["high-calorie","treat","junk"]'),
('Fruit Salad', 'desserts', '1 cup (181g)', 87.0, 1.2, 22.4, 0.3, 2.8, 18.0, 1, '["low-calorie","healthy","vitamins"]'),
('Kheer (Rice Pudding)', 'desserts', '1 serving (150g)', 195.0, 5.5, 30.0, 6.0, 0.2, 22.0, 0, '["indian","high-sugar"]'),
('Jalebi', 'desserts', '2 pieces (60g)', 282.0, 2.0, 48.0, 9.5, 0.0, 40.0, 0, '["high-sugar","fried","indian","junk"]'),
('Protein Ball', 'desserts', '1 ball (30g)', 95.0, 5.0, 10.0, 4.5, 1.5, 5.0, 1, '["high-protein","healthy-treat"]'),
('Rasgulla', 'desserts', '2 pieces (80g)', 186.0, 5.0, 34.0, 3.0, 0.0, 28.0, 0, '["indian","high-sugar"]'),
('Dark Chocolate Mousse', 'desserts', '1/2 cup (100g)', 180.0, 4.0, 20.0, 10.0, 2.0, 16.0, 0, '["treat","antioxidant"]');

-- OTHER
INSERT INTO food_items (name, category, serving_size, calories, protein_g, carbs_g, fat_g, fiber_g, sugar_g, is_healthy, tags) VALUES
('Olive Oil', 'other', '1 tbsp (14ml)', 119.0, 0.0, 0.0, 13.5, 0.0, 0.0, 1, '["healthy-fats","cooking"]'),
('Honey', 'other', '1 tbsp (21g)', 64.0, 0.1, 17.3, 0.0, 0.0, 17.2, 1, '["natural-sweetener","energy"]'),
('Ghee', 'other', '1 tbsp (14g)', 123.0, 0.0, 0.0, 13.9, 0.0, 0.0, 1, '["healthy-fats","indian","keto-friendly"]'),
('Roti (Chapati)', 'other', '1 piece (40g)', 120.0, 3.0, 18.0, 3.7, 2.0, 0.5, 1, '["whole-grain","staple","indian"]'),
('Dal (Lentil Curry)', 'other', '1 cup (200g)', 198.0, 12.5, 30.0, 3.5, 8.0, 2.5, 1, '["plant-protein","high-fiber","indian"]'),
('Idli', 'other', '2 pieces (120g)', 156.0, 4.8, 32.0, 0.4, 1.5, 0.5, 1, '["steamed","south-indian","breakfast"]'),
('Dosa', 'other', '1 piece (100g)', 168.0, 4.0, 28.0, 4.5, 1.0, 1.0, 1, '["south-indian","breakfast"]'),
('Upma', 'other', '1 cup (200g)', 210.0, 5.0, 30.0, 8.0, 2.0, 1.5, 1, '["south-indian","breakfast"]');

-- ============================================================
-- SEED: DEFAULT ADMIN USER
-- Password: Admin@123 (bcrypt hash)
-- ============================================================
INSERT INTO users (email, username, password_hash, role) VALUES
('admin@nouriq.com', 'admin', '$2y$10$LEN0uBO7kdEDMcIV2im1D./exDgFVdsIV2kAtN4w85dsy3hLrr5k6', 'admin');

INSERT INTO profiles (user_id, first_name, last_name, age, gender, height_cm, weight_kg, bmi, activity_level, diet_type, health_goal, daily_calorie_target) VALUES
(1, 'Admin', 'Nouriq', 30, 'male', 175.0, 70.0, 22.9, 'active', 'omnivore', 'maintenance', 2500);

INSERT INTO user_points (user_id, total_points, level) VALUES (1, 0, 1);

<?php
/**
 * Nouriq — Application Configuration
 */

// App Info
define('APP_NAME', 'Nouriq');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/amdfoodapp');
define('APP_TAGLINE', 'Your Intelligent Nutrition Coach');

// Paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('ASSETS_PATH', APP_URL . '/assets/');
define('CSS_PATH', ASSETS_PATH . 'css/');
define('JS_PATH', ASSETS_PATH . 'js/');
define('IMG_PATH', ASSETS_PATH . 'images/');

// Session
define('SESSION_LIFETIME', 86400 * 7); // 7 days
define('REMEMBER_ME_LIFETIME', 86400 * 30); // 30 days

// Security
define('CSRF_TOKEN_NAME', 'nouriq_csrf_token');
define('BCRYPT_COST', 10);

// Nutrition Defaults
define('DEFAULT_CALORIE_TARGET', 2000);
define('DEFAULT_PROTEIN_TARGET', 50);
define('DEFAULT_CARB_TARGET', 250);
define('DEFAULT_FAT_TARGET', 65);

// Activity Multipliers (for BMR calculation)
define('ACTIVITY_MULTIPLIERS', [
    'sedentary'   => 1.2,
    'light'       => 1.375,
    'moderate'    => 1.55,
    'active'      => 1.725,
    'very_active' => 1.9
]);

// Health Goal Adjustments (calorie adjustment)
define('GOAL_ADJUSTMENTS', [
    'weight_loss'    => -500,
    'muscle_gain'    => 300,
    'maintenance'    => 0,
    'general_health' => 0
]);

// Macro Ratios by goal
define('MACRO_RATIOS', [
    'weight_loss'    => ['protein' => 0.35, 'carbs' => 0.35, 'fat' => 0.30],
    'muscle_gain'    => ['protein' => 0.35, 'carbs' => 0.40, 'fat' => 0.25],
    'maintenance'    => ['protein' => 0.25, 'carbs' => 0.45, 'fat' => 0.30],
    'general_health' => ['protein' => 0.25, 'carbs' => 0.45, 'fat' => 0.30]
]);

// Meal timing suggestions
define('MEAL_TIMES', [
    'breakfast' => ['start' => '06:00', 'end' => '10:00', 'ideal' => '08:00'],
    'lunch'     => ['start' => '11:00', 'end' => '14:00', 'ideal' => '12:30'],
    'dinner'    => ['start' => '18:00', 'end' => '21:00', 'ideal' => '19:30'],
    'snack'     => ['start' => '10:00', 'end' => '22:00', 'ideal' => '15:00']
]);

// Late night threshold
define('LATE_NIGHT_HOUR', 21); // 9 PM

// Gamification
define('POINTS_PER_LOG', 5);
define('POINTS_PER_HEALTHY_MEAL', 10);
define('POINTS_LEVEL_MULTIPLIER', 100); // Points per level

// Food categories display
define('FOOD_CATEGORIES', [
    'fruits'      => ['label' => 'Fruits', 'icon' => '🍎', 'color' => '#ff6b6b'],
    'vegetables'  => ['label' => 'Vegetables', 'icon' => '🥦', 'color' => '#00cec9'],
    'grains'      => ['label' => 'Grains', 'icon' => '🌾', 'color' => '#fdcb6e'],
    'protein'     => ['label' => 'Protein', 'icon' => '🥩', 'color' => '#e17055'],
    'dairy'       => ['label' => 'Dairy', 'icon' => '🥛', 'color' => '#74b9ff'],
    'fast_food'   => ['label' => 'Fast Food', 'icon' => '🍔', 'color' => '#ff7675'],
    'snacks'      => ['label' => 'Snacks', 'icon' => '🍿', 'color' => '#a29bfe'],
    'beverages'   => ['label' => 'Beverages', 'icon' => '☕', 'color' => '#55a3f7'],
    'desserts'    => ['label' => 'Desserts', 'icon' => '🍰', 'color' => '#fd79a8'],
    'other'       => ['label' => 'Other', 'icon' => '🍽️', 'color' => '#636e72']
]);

// Food swaps mapping (unhealthy → healthy)
define('FOOD_SWAPS', [
    'Cheeseburger'         => 'Chicken Breast (grilled)',
    'French Fries (large)' => 'Sweet Potato',
    'Fried Chicken (2 pc)' => 'Chicken Breast (grilled)',
    'Cola'                 => 'Green Tea',
    'Potato Chips'         => 'Almonds',
    'Vanilla Ice Cream'    => 'Greek Yogurt',
    'Chocolate Cake'       => 'Dark Chocolate (70%)',
    'White Bread'          => 'Whole Wheat Bread',
    'Candy Bar'            => 'Protein Bar',
    'Cookies (chocolate chip)' => 'Protein Ball',
    'Onion Rings'          => 'Popcorn (air-popped)',
    'Hot Dog'              => 'Turkey Breast',
    'Nachos with Cheese'   => 'Hummus',
    'Samosa (fried)'       => 'Idli',
    'Jalebi'               => 'Fruit Salad',
    'Gulab Jamun'          => 'Protein Ball',
    'Lemonade'             => 'Coconut Water',
    'Granola'              => 'Oats',
    'Fish & Chips'         => 'Salmon (baked)',
]);

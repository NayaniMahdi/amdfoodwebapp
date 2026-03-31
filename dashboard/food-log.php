<?php
/**
 * Nouriq — Food Log Page
 */
require_once __DIR__ . '/../includes/auth-check.php';
$pageTitle = 'Food Log';

$categories = FOOD_CATEGORIES;
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Log — Nouriq</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>main.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="page-content">
        <!-- Top Actions -->
        <div class="flex items-center justify-between flex-wrap gap-md" style="margin-bottom:var(--space-xl)">
            <div>
                <input type="date" id="logDate" class="form-input" value="<?php echo today(); ?>" style="width:auto">
            </div>
            <div class="flex gap-sm">
                <button class="btn btn-secondary" onclick="Modal.open('customFoodModal')">✏️ Custom Food</button>
                <button class="btn btn-primary" onclick="Modal.open('addMealModal')">+ Log Meal</button>
            </div>
        </div>

        <!-- Daily Summary Bar -->
        <div class="glass-card-static" style="padding:var(--space-lg);margin-bottom:var(--space-xl)">
            <div class="flex items-center justify-between flex-wrap gap-lg">
                <div class="flex items-center gap-lg flex-wrap">
                    <div class="text-center">
                        <div class="font-mono font-bold text-lg" id="totalCal" style="color:var(--color-calories)">0</div>
                        <div class="text-xs text-secondary">Calories</div>
                    </div>
                    <div style="width:1px;height:32px;background:var(--border)"></div>
                    <div class="text-center">
                        <div class="font-mono font-semibold" id="totalProtein" style="color:var(--color-protein)">0g</div>
                        <div class="text-xs text-secondary">Protein</div>
                    </div>
                    <div class="text-center">
                        <div class="font-mono font-semibold" id="totalCarbs" style="color:var(--color-carbs)">0g</div>
                        <div class="text-xs text-secondary">Carbs</div>
                    </div>
                    <div class="text-center">
                        <div class="font-mono font-semibold" id="totalFat" style="color:var(--color-fat)">0g</div>
                        <div class="text-xs text-secondary">Fat</div>
                    </div>
                </div>
                <div class="flex items-center gap-md">
                    <div class="progress-bar" style="width:200px">
                        <div class="progress-fill" id="dailyProgress" style="width:0%"></div>
                    </div>
                    <span class="text-sm font-mono" id="dailyPct">0%</span>
                </div>
            </div>
        </div>

        <!-- Meal Sections -->
        <div id="mealSections">
            <div class="skeleton skeleton-card" style="height:200px;margin-bottom:16px"></div>
            <div class="skeleton skeleton-card" style="height:200px;margin-bottom:16px"></div>
        </div>
    </div>

    <!-- Add Meal Modal -->
    <div class="modal-overlay" id="addMealModal">
        <div class="modal" style="max-width:560px">
            <div class="modal-header">
                <h3>🍱 Log a Meal</h3>
                <button class="btn btn-ghost btn-sm" onclick="Modal.close('addMealModal')">✕</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Meal Type</label>
                    <div class="flex gap-sm flex-wrap">
                        <button class="btn btn-secondary btn-sm meal-type-btn active" data-meal="breakfast" onclick="selectMealType(this)">🌅 Breakfast</button>
                        <button class="btn btn-secondary btn-sm meal-type-btn" data-meal="lunch" onclick="selectMealType(this)">☀️ Lunch</button>
                        <button class="btn btn-secondary btn-sm meal-type-btn" data-meal="dinner" onclick="selectMealType(this)">🌙 Dinner</button>
                        <button class="btn btn-secondary btn-sm meal-type-btn" data-meal="snack" onclick="selectMealType(this)">🍪 Snack</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Search Food</label>
                    <div class="food-search-container">
                        <span class="food-search-icon">🔍</span>
                        <input type="text" class="food-search-input" id="foodSearchInput" 
                               placeholder="Search for a food item..." autocomplete="off">
                        <div class="food-search-results" id="foodSearchResults"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Filter by Category</label>
                    <select class="form-select" id="categoryFilter" onchange="filterByCategory()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $key => $cat): ?>
                        <option value="<?php echo $key; ?>"><?php echo $cat['icon'] . ' ' . $cat['label']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="selectedFoodCard" class="hidden">
                    <div class="glass-card" style="padding:var(--space-md);margin-bottom:var(--space-md)">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-semibold" id="selectedFoodName"></div>
                                <div class="text-xs text-secondary" id="selectedFoodServing"></div>
                            </div>
                            <button class="btn btn-ghost btn-sm" onclick="clearSelectedFood()">✕</button>
                        </div>
                        <div class="flex gap-lg" style="margin-top:8px">
                            <span class="text-xs" style="color:var(--color-calories)">🔥 <span id="selCal">0</span> cal</span>
                            <span class="text-xs" style="color:var(--color-protein)">💪 <span id="selPro">0</span>g P</span>
                            <span class="text-xs" style="color:var(--color-carbs)">🌾 <span id="selCarb">0</span>g C</span>
                            <span class="text-xs" style="color:var(--color-fat)">💧 <span id="selFat">0</span>g F</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Servings</label>
                        <div class="flex items-center gap-sm">
                            <button class="btn btn-secondary btn-icon" onclick="adjustServings(-0.5)">−</button>
                            <input type="number" id="servingInput" class="form-input" value="1" min="0.5" max="10" step="0.5" 
                                   style="width:80px;text-align:center" onchange="updateNutrition()">
                            <button class="btn btn-secondary btn-icon" onclick="adjustServings(0.5)">+</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="Modal.close('addMealModal')">Cancel</button>
                <button class="btn btn-primary" id="logMealBtn" onclick="logMeal()" disabled>Log Meal</button>
            </div>
        </div>
    </div>

    <!-- Custom Food Modal -->
    <div class="modal-overlay" id="customFoodModal">
        <div class="modal">
            <div class="modal-header">
                <h3>✏️ Add Custom Food</h3>
                <button class="btn btn-ghost btn-sm" onclick="Modal.close('customFoodModal')">✕</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Food Name *</label>
                    <input type="text" class="form-input" id="customName" placeholder="e.g. Homemade Pasta">
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select class="form-select" id="customCategory">
                        <?php foreach ($categories as $key => $cat): ?>
                        <option value="<?php echo $key; ?>"><?php echo $cat['icon'] . ' ' . $cat['label']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Serving Size</label>
                    <input type="text" class="form-input" id="customServing" placeholder="e.g. 1 cup, 100g">
                </div>
                <div class="grid grid-2" style="gap:12px">
                    <div class="form-group">
                        <label class="form-label">Calories</label>
                        <input type="number" class="form-input" id="customCal" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Protein (g)</label>
                        <input type="number" class="form-input" id="customPro" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Carbs (g)</label>
                        <input type="number" class="form-input" id="customCarb" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fat (g)</label>
                        <input type="number" class="form-input" id="customFat" placeholder="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="Modal.close('customFoodModal')">Cancel</button>
                <button class="btn btn-primary" onclick="addCustomFood()">Add Food</button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</div>

<script src="<?php echo JS_PATH; ?>food-log.js"></script>
</body>
</html>

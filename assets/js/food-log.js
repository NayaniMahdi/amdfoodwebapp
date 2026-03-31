/**
 * Nouriq — Food Log Interactivity
 */

let selectedFood = null;
let selectedMealType = 'breakfast';

const categoryIcons = {
    'fruits':'🍎','vegetables':'🥦','grains':'🌾','protein':'🥩',
    'dairy':'🥛','fast_food':'🍔','snacks':'🍿','beverages':'☕',
    'desserts':'🍰','other':'🍽️'
};

const mealIcons = {
    'breakfast': '🌅', 'lunch': '☀️', 'dinner': '🌙', 'snack': '🍪'
};

document.addEventListener('DOMContentLoaded', () => {
    loadFoodLog();
    
    document.getElementById('logDate').addEventListener('change', loadFoodLog);
    
    // Food search
    const searchInput = document.getElementById('foodSearchInput');
    searchInput.addEventListener('input', debounce(searchFood, 300));
    searchInput.addEventListener('focus', () => {
        if (searchInput.value.length >= 1) searchFood();
    });
    
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.food-search-container')) {
            document.getElementById('foodSearchResults').classList.remove('active');
        }
    });
});

async function loadFoodLog() {
    const date = document.getElementById('logDate').value;
    const result = await NouriqAPI.get(`/log-meal.php?action=list&date=${date}`);
    const container = document.getElementById('mealSections');
    
    if (!result.success) { container.innerHTML = '<p class="text-secondary">Error loading food log</p>'; return; }
    
    const meals = result.data;
    let totalCal = 0, totalPro = 0, totalCarbs = 0, totalFat = 0;
    
    let html = '';
    const mealOrder = ['breakfast', 'lunch', 'dinner', 'snack'];
    const mealNames = { breakfast: 'Breakfast', lunch: 'Lunch', dinner: 'Dinner', snack: 'Snacks' };
    
    mealOrder.forEach(mealType => {
        const entries = meals[mealType] || [];
        const mealCal = entries.reduce((sum, e) => sum + parseFloat(e.calories), 0);
        
        entries.forEach(e => {
            totalCal += parseFloat(e.calories);
            totalPro += parseFloat(e.protein_g);
            totalCarbs += parseFloat(e.carbs_g);
            totalFat += parseFloat(e.fat_g);
        });
        
        html += `
        <div class="meal-section">
            <div class="meal-section-header">
                <div class="meal-section-title">
                    <span class="meal-icon">${mealIcons[mealType]}</span>
                    ${mealNames[mealType]}
                    <span class="badge badge-accent" style="font-size:11px">${entries.length} items</span>
                </div>
                <span class="meal-section-calories">${Math.round(mealCal)} cal</span>
            </div>`;
        
        if (entries.length === 0) {
            html += `
                <div class="glass-card" style="padding:var(--space-lg);text-align:center">
                    <p class="text-secondary text-sm">No ${mealNames[mealType].toLowerCase()} logged</p>
                    <button class="btn btn-ghost btn-sm" style="margin-top:8px" onclick="openAddMeal('${mealType}')">+ Add</button>
                </div>`;
        } else {
            entries.forEach(entry => {
                html += `
                <div class="food-entry animate-fade-in">
                    <div class="food-entry-icon" style="background:rgba(108,92,231,0.1)">
                        ${categoryIcons[entry.category] || '🍽️'}
                    </div>
                    <div class="food-entry-info">
                        <div class="food-entry-name">${escapeHtml(entry.food_name)}</div>
                        <div class="food-entry-serving">${entry.serving_size} × ${entry.servings}</div>
                    </div>
                    <div class="food-entry-macros">
                        <span style="color:var(--color-protein)">P: ${Math.round(entry.protein_g)}g</span>
                        <span style="color:var(--color-carbs)">C: ${Math.round(entry.carbs_g)}g</span>
                        <span style="color:var(--color-fat)">F: ${Math.round(entry.fat_g)}g</span>
                    </div>
                    <span class="food-entry-calories">${Math.round(entry.calories)} cal</span>
                    <div class="food-entry-actions">
                        <button class="delete-btn" onclick="deleteEntry(${entry.id})" title="Delete">🗑️</button>
                    </div>
                </div>`;
            });
        }
        html += '</div>';
    });
    
    container.innerHTML = html;
    
    // Update summary bar
    document.getElementById('totalCal').textContent = Math.round(totalCal);
    document.getElementById('totalProtein').textContent = Math.round(totalPro) + 'g';
    document.getElementById('totalCarbs').textContent = Math.round(totalCarbs) + 'g';
    document.getElementById('totalFat').textContent = Math.round(totalFat) + 'g';
    
    // Fetch target for progress
    const statsResult = await NouriqAPI.get('/get-stats.php?action=today');
    if (statsResult.success) {
        const target = statsResult.data.calories.target;
        const pct = Math.min(Math.round((totalCal / target) * 100), 100);
        document.getElementById('dailyProgress').style.width = pct + '%';
        document.getElementById('dailyPct').textContent = pct + '%';
    }
}

async function searchFood() {
    const query = document.getElementById('foodSearchInput').value.trim();
    const category = document.getElementById('categoryFilter')?.value || '';
    const results = document.getElementById('foodSearchResults');
    
    if (query.length < 1 && !category) { results.classList.remove('active'); return; }
    
    let url = `/food-search.php?q=${encodeURIComponent(query)}`;
    if (category) url += `&category=${category}`;
    
    const result = await NouriqAPI.get(url);
    
    if (result.success && result.data.length > 0) {
        results.innerHTML = result.data.map(food => `
            <div class="food-search-item" onclick='selectFood(${JSON.stringify(food)})'>
                <span class="item-cat-icon">${categoryIcons[food.category] || '🍽️'}</span>
                <span class="item-name">${escapeHtml(food.name)}</span>
                <span class="item-cal">${Math.round(food.calories)} cal</span>
            </div>
        `).join('');
        results.classList.add('active');
    } else {
        results.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text-secondary);font-size:13px">No results found</div>';
        results.classList.add('active');
    }
}

function filterByCategory() {
    searchFood();
}

function selectFood(food) {
    selectedFood = food;
    document.getElementById('foodSearchResults').classList.remove('active');
    document.getElementById('foodSearchInput').value = food.name;
    document.getElementById('selectedFoodCard').classList.remove('hidden');
    document.getElementById('selectedFoodName').textContent = food.name;
    document.getElementById('selectedFoodServing').textContent = food.serving_size;
    document.getElementById('servingInput').value = 1;
    document.getElementById('logMealBtn').disabled = false;
    updateNutrition();
}

function clearSelectedFood() {
    selectedFood = null;
    document.getElementById('selectedFoodCard').classList.add('hidden');
    document.getElementById('foodSearchInput').value = '';
    document.getElementById('logMealBtn').disabled = true;
}

function updateNutrition() {
    if (!selectedFood) return;
    const servings = parseFloat(document.getElementById('servingInput').value) || 1;
    document.getElementById('selCal').textContent = Math.round(selectedFood.calories * servings);
    document.getElementById('selPro').textContent = Math.round(selectedFood.protein_g * servings);
    document.getElementById('selCarb').textContent = Math.round(selectedFood.carbs_g * servings);
    document.getElementById('selFat').textContent = Math.round(selectedFood.fat_g * servings);
}

function adjustServings(delta) {
    const input = document.getElementById('servingInput');
    let val = parseFloat(input.value) + delta;
    val = Math.max(0.5, Math.min(10, val));
    input.value = val;
    updateNutrition();
}

function selectMealType(btn) {
    document.querySelectorAll('.meal-type-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    selectedMealType = btn.dataset.meal;
}

function openAddMeal(mealType) {
    selectedMealType = mealType;
    document.querySelectorAll('.meal-type-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.meal === mealType);
    });
    Modal.open('addMealModal');
}

async function logMeal() {
    if (!selectedFood) return;
    
    const servings = parseFloat(document.getElementById('servingInput').value) || 1;
    const date = document.getElementById('logDate').value;
    const loggedAt = date + ' ' + new Date().toTimeString().slice(0, 8);
    
    const result = await NouriqAPI.post('/log-meal.php', {
        action: 'add',
        food_item_id: selectedFood.id,
        meal_type: selectedMealType,
        servings: servings,
        logged_at: loggedAt
    });
    
    if (result.success) {
        Toast.success('Meal Logged!', `${selectedFood.name} added to ${selectedMealType}`);
        Modal.close('addMealModal');
        clearSelectedFood();
        loadFoodLog();
        
        // Show achievement notifications
        if (result.data.new_achievements && result.data.new_achievements.length > 0) {
            result.data.new_achievements.forEach(ach => {
                setTimeout(() => {
                    Toast.success('🏆 Achievement Unlocked!', `${ach.icon} ${ach.name} — +${ach.points} points`);
                }, 1000);
            });
        }
    } else {
        Toast.error('Error', result.message);
    }
}

async function deleteEntry(logId) {
    if (!confirm('Remove this entry?')) return;
    
    const result = await NouriqAPI.post('/log-meal.php', { action: 'delete', log_id: logId });
    if (result.success) {
        Toast.success('Entry removed');
        loadFoodLog();
    }
}

async function addCustomFood() {
    const name = document.getElementById('customName').value.trim();
    if (!name) { Toast.error('Please enter a food name'); return; }
    
    const result = await NouriqAPI.post('/log-meal.php', {
        action: 'add_custom',
        name: name,
        category: document.getElementById('customCategory').value,
        serving_size: document.getElementById('customServing').value || '1 serving',
        calories: document.getElementById('customCal').value || 0,
        protein_g: document.getElementById('customPro').value || 0,
        carbs_g: document.getElementById('customCarb').value || 0,
        fat_g: document.getElementById('customFat').value || 0
    });
    
    if (result.success) {
        Toast.success('Custom food added!', 'You can now find it in the search.');
        Modal.close('customFoodModal');
        // Clear form
        ['customName','customServing','customCal','customPro','customCarb','customFat'].forEach(id => {
            document.getElementById(id).value = '';
        });
    }
}

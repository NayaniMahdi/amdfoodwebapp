/**
 * Nouriq — Dashboard Interactivity
 */
document.addEventListener('DOMContentLoaded', () => {
    loadDashboardStats();
    loadWeeklyChart();
    loadRecommendationsPreview();
    loadTodayLogPreview();
});

async function loadDashboardStats() {
    const result = await NouriqAPI.get('/get-stats.php?action=today');
    if (!result.success) return;
    
    const d = result.data;
    const cal = d.calories;
    const pct = Math.min(Math.round((cal.consumed / cal.target) * 100), 150);
    
    // Animate stats
    animateValue(document.getElementById('statCalories'), 0, cal.consumed, 1200);
    animateValue(document.getElementById('statProtein'), 0, d.macros.protein.consumed, 1000);
    animateValue(document.getElementById('statStreak'), 0, d.streak, 800);
    animateValue(document.getElementById('statPoints'), 0, d.points.total_points, 1000);
    
    document.getElementById('statLevel').textContent = 'Level ' + d.points.level;
    document.getElementById('calRemaining').textContent = formatCalories(cal.remaining);
    document.getElementById('ringValue').textContent = formatCalories(cal.consumed);
    document.getElementById('ringTarget').textContent = formatCalories(cal.target);
    
    // Calorie progress bar
    const calProg = document.getElementById('calProgress');
    calProg.style.width = Math.min(pct, 100) + '%';
    calProg.className = 'progress-fill' + (pct > 100 ? ' danger' : pct > 85 ? ' warning' : '');
    
    // Protein progress
    const protPct = Math.min(Math.round((d.macros.protein.consumed / d.macros.protein.target) * 100), 100);
    document.getElementById('proteinProgress').style.width = protPct + '%';
    
    // Calorie badge
    const calBadge = document.getElementById('calBadge');
    if (pct > 100) { calBadge.textContent = 'Over target'; calBadge.className = 'badge badge-danger'; }
    else if (pct > 85) { calBadge.textContent = 'Almost there'; calBadge.className = 'badge badge-warning'; }
    else { calBadge.textContent = pct + '% Goal'; calBadge.className = 'badge badge-info'; }
    
    // Donut chart
    NouriqCharts.drawDonut('calorieRingCanvas', cal.consumed, cal.target, { size: 200 });
    
    // Macro bars
    NouriqCharts.drawHorizontalBars('macroBars', [
        { label: 'Protein', value: d.macros.protein.consumed, max: d.macros.protein.target, color: '#ff6b6b' },
        { label: 'Carbs', value: d.macros.carbs.consumed, max: d.macros.carbs.target, color: '#fdcb6e' },
        { label: 'Fat', value: d.macros.fat.consumed, max: d.macros.fat.target, color: '#74b9ff' }
    ]);
}

async function loadWeeklyChart() {
    const result = await NouriqAPI.get('/get-stats.php?action=weekly');
    if (!result.success) return;
    
    NouriqCharts.drawBarChart('weeklyChart', 
        result.data.data.map(d => ({ label: d.label, value: d.calories })),
        { target: result.data.target, colors: ['#6C5CE7', '#a29bfe'] }
    );
}

async function loadChart(period) {
    document.querySelectorAll('.chart-period button').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
    
    const result = await NouriqAPI.get(`/get-stats.php?action=${period}`);
    if (!result.success) return;
    
    if (period === 'monthly') {
        NouriqCharts.drawLineChart('weeklyChart', 
            [{ data: result.data.data.map(d => d.calories), color: '#6C5CE7', fill: true }],
            result.data.data.map(d => d.label),
            { height: 250 }
        );
    } else {
        NouriqCharts.drawBarChart('weeklyChart',
            result.data.data.map(d => ({ label: d.label, value: d.calories })),
            { target: result.data.target }
        );
    }
}

async function loadRecommendationsPreview() {
    const result = await NouriqAPI.get('/get-recommendations.php');
    const container = document.getElementById('quickRecommendations');
    
    if (result.success && result.data && result.data.length > 0) {
        container.innerHTML = result.data.slice(0, 3).map(rec => `
            <div class="glass-card rec-card ${rec.class || ''}">
                <span class="rec-icon">${rec.icon}</span>
                <div class="rec-content">
                    <div class="rec-title">${escapeHtml(rec.title)}</div>
                    <div class="rec-message">${escapeHtml(rec.message).substring(0, 120)}${rec.message.length > 120 ? '...' : ''}</div>
                </div>
            </div>
        `).join('');
    } else {
        container.innerHTML = `
            <div class="glass-card empty-state" style="padding:32px">
                <span style="font-size:32px;">✨</span>
                <h3 style="margin-top:8px;font-size:14px;">All caught up!</h3>
                <p class="text-sm text-secondary">Start logging meals for personalized insights</p>
            </div>
        `;
    }
}

async function loadTodayLogPreview() {
    const result = await NouriqAPI.get('/log-meal.php?action=list');
    const container = document.getElementById('todayLogPreview');
    
    if (!result.success) { container.innerHTML = '<p class="text-secondary text-sm">Unable to load</p>'; return; }
    
    const categories = {
        'fruits': '🍎', 'vegetables': '🥦', 'grains': '🌾', 'protein': '🥩',
        'dairy': '🥛', 'fast_food': '🍔', 'snacks': '🍿', 'beverages': '☕',
        'desserts': '🍰', 'other': '🍽️'
    };
    
    const allEntries = Object.values(result.data).flat();
    
    if (allEntries.length === 0) {
        container.innerHTML = `
            <div class="glass-card empty-state" style="padding:24px">
                <span style="font-size:32px">🍽️</span>
                <p class="text-sm text-secondary" style="margin-top:8px">No meals logged today</p>
                <a href="food-log.php" class="btn btn-primary btn-sm" style="margin-top:12px">Log your first meal</a>
            </div>
        `;
        return;
    }
    
    container.innerHTML = allEntries.slice(0, 5).map(entry => `
        <div class="food-entry">
            <div class="food-entry-icon" style="background:rgba(108,92,231,0.1)">${categories[entry.category] || '🍽️'}</div>
            <div class="food-entry-info">
                <div class="food-entry-name">${escapeHtml(entry.food_name)}</div>
                <div class="food-entry-serving">${entry.meal_type} • ${entry.servings}x</div>
            </div>
            <span class="food-entry-calories">${Math.round(entry.calories)} cal</span>
        </div>
    `).join('');
    
    if (allEntries.length > 5) {
        container.innerHTML += `<a href="food-log.php" class="btn btn-ghost btn-sm w-full" style="margin-top:8px">View ${allEntries.length - 5} more →</a>`;
    }
}

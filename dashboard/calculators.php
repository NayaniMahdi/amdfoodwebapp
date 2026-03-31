<?php
require_once __DIR__ . '/../includes/auth-check.php';
$pageTitle = 'Health Calculators';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculators — Nouriq</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>main.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="page-content">
        <h2 class="section-title" style="margin-bottom:var(--space-xl)">🧮 Health Calculators</h2>

        <div class="grid grid-2" style="gap:var(--space-xl)">
            <!-- BMI Calculator -->
            <div class="glass-card-static" style="padding:var(--space-xl)">
                <h3 style="margin-bottom:var(--space-lg)">📊 BMI Calculator</h3>
                <div class="form-group">
                    <label class="form-label">Weight (kg)</label>
                    <input type="number" class="form-input" id="bmiWeight" placeholder="70" step="0.1">
                </div>
                <div class="form-group">
                    <label class="form-label">Height (cm)</label>
                    <input type="number" class="form-input" id="bmiHeight" placeholder="170" step="0.1">
                </div>
                <button class="btn btn-primary w-full" onclick="calcBMI()">Calculate BMI</button>
                
                <div id="bmiResult" class="hidden" style="margin-top:var(--space-lg)">
                    <div class="glass-card" style="padding:var(--space-lg);text-align:center">
                        <div class="font-mono" style="font-size:var(--text-4xl);font-weight:800" id="bmiValue">--</div>
                        <div class="text-lg font-semibold" id="bmiCategory" style="margin-top:4px">--</div>
                        <div class="progress-bar" style="margin-top:12px">
                            <div class="progress-fill" id="bmiBar" style="width:0%"></div>
                        </div>
                        <div class="text-xs text-secondary mt-sm" id="bmiAdvice"></div>
                    </div>
                </div>
            </div>

            <!-- Calorie Calculator -->
            <div class="glass-card-static" style="padding:var(--space-xl)">
                <h3 style="margin-bottom:var(--space-lg)">🔥 Daily Calorie Calculator</h3>
                <div class="grid grid-2" style="gap:12px">
                    <div class="form-group">
                        <label class="form-label">Age</label>
                        <input type="number" class="form-input" id="calAge" placeholder="25">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select class="form-select" id="calGender">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Weight (kg)</label>
                    <input type="number" class="form-input" id="calWeight" placeholder="70" step="0.1">
                </div>
                <div class="form-group">
                    <label class="form-label">Height (cm)</label>
                    <input type="number" class="form-input" id="calHeight" placeholder="170" step="0.1">
                </div>
                <div class="form-group">
                    <label class="form-label">Activity Level</label>
                    <select class="form-select" id="calActivity">
                        <option value="1.2">Sedentary (little/no exercise)</option>
                        <option value="1.375">Light (1-3 days/week)</option>
                        <option value="1.55" selected>Moderate (3-5 days/week)</option>
                        <option value="1.725">Active (6-7 days/week)</option>
                        <option value="1.9">Very Active (2x/day)</option>
                    </select>
                </div>
                <button class="btn btn-primary w-full" onclick="calcCalories()">Calculate</button>
                
                <div id="calResult" class="hidden" style="margin-top:var(--space-lg)">
                    <div class="glass-card" style="padding:var(--space-lg)">
                        <div class="flex justify-between items-center" style="margin-bottom:12px">
                            <span class="text-secondary">BMR (Base)</span>
                            <span class="font-mono font-bold" id="bmrValue">--</span>
                        </div>
                        <div class="flex justify-between items-center" style="margin-bottom:12px">
                            <span class="text-secondary">Maintenance</span>
                            <span class="font-mono font-bold" style="color:var(--success)" id="maintainValue">--</span>
                        </div>
                        <div class="flex justify-between items-center" style="margin-bottom:12px">
                            <span class="text-secondary">Weight Loss</span>
                            <span class="font-mono font-bold" style="color:var(--warning)" id="lossValue">--</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-secondary">Muscle Gain</span>
                            <span class="font-mono font-bold" style="color:var(--color-protein)" id="gainValue">--</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</div>
<script>
function calcBMI() {
    const w = parseFloat(document.getElementById('bmiWeight').value);
    const h = parseFloat(document.getElementById('bmiHeight').value);
    if (!w || !h) { Toast.error('Please fill in both fields'); return; }
    
    const bmi = (w / Math.pow(h / 100, 2)).toFixed(1);
    let cat, color, advice, pct;
    
    if (bmi < 18.5) { cat = 'Underweight'; color = 'var(--info)'; advice = 'Consider increasing your calorie intake with nutrient-dense foods.'; pct = (bmi / 40) * 100; }
    else if (bmi < 25) { cat = 'Normal Weight'; color = 'var(--success)'; advice = 'You\'re in a healthy range. Maintain your current lifestyle!'; pct = (bmi / 40) * 100; }
    else if (bmi < 30) { cat = 'Overweight'; color = 'var(--warning)'; advice = 'A balanced diet and regular exercise can help you reach optimal weight.'; pct = (bmi / 40) * 100; }
    else { cat = 'Obese'; color = 'var(--danger)'; advice = 'Consult a healthcare provider for a personalized weight management plan.'; pct = Math.min((bmi / 40) * 100, 100); }
    
    document.getElementById('bmiResult').classList.remove('hidden');
    document.getElementById('bmiValue').textContent = bmi;
    document.getElementById('bmiValue').style.color = color;
    document.getElementById('bmiCategory').textContent = cat;
    document.getElementById('bmiCategory').style.color = color;
    document.getElementById('bmiBar').style.width = pct + '%';
    document.getElementById('bmiBar').style.background = color;
    document.getElementById('bmiAdvice').textContent = advice;
}

function calcCalories() {
    const age = parseInt(document.getElementById('calAge').value);
    const gender = document.getElementById('calGender').value;
    const w = parseFloat(document.getElementById('calWeight').value);
    const h = parseFloat(document.getElementById('calHeight').value);
    const activity = parseFloat(document.getElementById('calActivity').value);
    
    if (!age || !w || !h) { Toast.error('Please fill all fields'); return; }
    
    let bmr;
    if (gender === 'male') bmr = (10 * w) + (6.25 * h) - (5 * age) + 5;
    else bmr = (10 * w) + (6.25 * h) - (5 * age) - 161;
    
    const maintain = Math.round(bmr * activity);
    
    document.getElementById('calResult').classList.remove('hidden');
    document.getElementById('bmrValue').textContent = Math.round(bmr) + ' cal';
    document.getElementById('maintainValue').textContent = maintain + ' cal';
    document.getElementById('lossValue').textContent = Math.max(1200, maintain - 500) + ' cal';
    document.getElementById('gainValue').textContent = (maintain + 300) + ' cal';
}
</script>
</body>
</html>

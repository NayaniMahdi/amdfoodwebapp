<?php
require_once __DIR__ . '/../includes/auth-check.php';
$pageTitle = 'Profile';
$userId = getCurrentUserId();
$profile = getUserProfile($userId);
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — Nouriq</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>main.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="page-content">
        <!-- Profile Header -->
        <div class="glass-card-static profile-header-card" style="margin-bottom:var(--space-xl)">
            <div class="profile-avatar"><?php echo strtoupper(substr($profile['first_name'] ?: $user['username'], 0, 1)); ?></div>
            <div class="profile-info">
                <h2><?php echo sanitize(getDisplayName($user)); ?></h2>
                <div class="profile-email"><?php echo sanitize($user['email']); ?> • @<?php echo sanitize($user['username']); ?></div>
                <div class="profile-stats">
                    <div class="profile-stat">
                        <div class="stat-num"><?php echo $profile['bmi'] ? number_format($profile['bmi'], 1) : '--'; ?></div>
                        <div class="stat-lbl">BMI</div>
                    </div>
                    <div class="profile-stat">
                        <div class="stat-num"><?php echo $profile['daily_calorie_target'] ?? '--'; ?></div>
                        <div class="stat-lbl">Daily Cal Target</div>
                    </div>
                    <div class="profile-stat">
                        <div class="stat-num" id="totalLogsCount">--</div>
                        <div class="stat-lbl">Meals Logged</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="glass-card-static" style="padding:var(--space-xl)">
            <h3 style="margin-bottom:var(--space-lg)">Edit Profile</h3>
            <form id="profileForm">
                <div class="grid grid-2" style="gap:var(--space-md)">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-input" name="first_name" value="<?php echo sanitize($profile['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-input" name="last_name" value="<?php echo sanitize($profile['last_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Age</label>
                        <input type="number" class="form-input" name="age" value="<?php echo $profile['age'] ?? ''; ?>" min="1" max="120">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="male" <?php echo ($profile['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($profile['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($profile['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Height (cm)</label>
                        <input type="number" class="form-input" name="height_cm" value="<?php echo $profile['height_cm'] ?? ''; ?>" step="0.1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" class="form-input" name="weight_kg" value="<?php echo $profile['weight_kg'] ?? ''; ?>" step="0.1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Activity Level</label>
                        <select class="form-select" name="activity_level">
                            <?php foreach (['sedentary'=>'Sedentary','light'=>'Lightly Active','moderate'=>'Moderately Active','active'=>'Active','very_active'=>'Very Active'] as $k=>$v): ?>
                            <option value="<?php echo $k; ?>" <?php echo ($profile['activity_level'] ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Diet Type</label>
                        <select class="form-select" name="diet_type">
                            <?php foreach (['omnivore'=>'Omnivore','vegetarian'=>'Vegetarian','vegan'=>'Vegan','keto'=>'Keto','paleo'=>'Paleo','mediterranean'=>'Mediterranean'] as $k=>$v): ?>
                            <option value="<?php echo $k; ?>" <?php echo ($profile['diet_type'] ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Health Goal</label>
                        <select class="form-select" name="health_goal">
                            <?php foreach (['weight_loss'=>'🎯 Weight Loss','muscle_gain'=>'💪 Muscle Gain','maintenance'=>'⚖️ Maintenance','general_health'=>'❤️ General Health'] as $k=>$v): ?>
                            <option value="<?php echo $k; ?>" <?php echo ($profile['health_goal'] ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div id="calculatedResults" class="hidden" style="margin-top:var(--space-lg)">
                    <div class="glass-card" style="padding:var(--space-md)">
                        <h4 style="margin-bottom:8px">📊 Calculated Targets</h4>
                        <div class="flex gap-xl flex-wrap">
                            <div><span class="text-secondary text-sm">BMI:</span> <strong id="resBmi">--</strong> <span id="resBmiCat" class="badge badge-info">--</span></div>
                            <div><span class="text-secondary text-sm">Daily Calories:</span> <strong class="font-mono" id="resCal">--</strong></div>
                            <div><span class="text-secondary text-sm">Protein:</span> <strong class="font-mono" id="resPro">--</strong>g</div>
                            <div><span class="text-secondary text-sm">Carbs:</span> <strong class="font-mono" id="resCarb">--</strong>g</div>
                            <div><span class="text-secondary text-sm">Fat:</span> <strong class="font-mono" id="resFat">--</strong>g</div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top:var(--space-lg)">
                    <button type="submit" class="btn btn-primary" id="saveProfileBtn">Save Profile</button>
                </div>
            </form>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Load total logs count
    NouriqAPI.get('/get-stats.php?action=today').then(r => {
        // Use a separate query for total
    });
    
    document.getElementById('profileForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('saveProfileBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner spinner-sm"></span> Saving...';
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        const result = await NouriqAPI.post('/profile.php', data);
        
        btn.disabled = false;
        btn.textContent = 'Save Profile';
        
        if (result.success) {
            Toast.success('Profile Updated!', 'Your nutrition targets have been recalculated.');
            
            const d = result.data;
            document.getElementById('calculatedResults').classList.remove('hidden');
            document.getElementById('resBmi').textContent = d.bmi;
            document.getElementById('resBmiCat').textContent = d.bmi_category.category;
            document.getElementById('resCal').textContent = d.calorie_target;
            document.getElementById('resPro').textContent = d.macros.protein;
            document.getElementById('resCarb').textContent = d.macros.carbs;
            document.getElementById('resFat').textContent = d.macros.fat;
        } else {
            Toast.error('Error', result.message);
        }
    });
});
</script>
</body>
</html>

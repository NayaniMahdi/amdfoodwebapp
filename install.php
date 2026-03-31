<?php
/**
 * Nouriq — Database Installer
 * Run this file once to set up the database and seed data.
 * Visit: http://localhost/amdfoodapp/install.php
 */

$host     = 'localhost';
$username = 'root';
$password = '';
$dbname   = 'nouriq_db';

$results  = [];
$hasError = false;

try {
    // ── Step 1: Connect without database ──────────────────────
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $results[] = ['status' => 'ok', 'msg' => 'Connected to MySQL server'];

    // ── Step 2: Create database ──────────────────────────────
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $results[] = ['status' => 'ok', 'msg' => "Database &laquo;$dbname&raquo; created / verified"];

    // ── Step 3: Reconnect WITH the database selected ─────────
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $results[] = ['status' => 'ok', 'msg' => "Connected to &laquo;$dbname&raquo;"];

    // ── Step 4: Read schema file ─────────────────────────────
    $schemaFile = __DIR__ . '/sql/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("schema.sql not found at: $schemaFile");
    }
    $sql = file_get_contents($schemaFile);
    $results[] = ['status' => 'ok', 'msg' => 'Schema file loaded (' . round(strlen($sql)/1024, 1) . ' KB)'];

    // ── Step 5: Parse SQL into individual statements ─────────
    // Remove SQL single-line comments (-- ...)
    $lines = explode("\n", $sql);
    $cleanLines = [];
    foreach ($lines as $line) {
        $trimmed = ltrim($line);
        // Skip pure comment lines and the CREATE DATABASE / USE lines (we already handled those)
        if (strpos($trimmed, '--') === 0) continue;
        if (preg_match('/^\s*CREATE\s+DATABASE/i', $trimmed)) continue;
        if (preg_match('/^\s*USE\s+/i', $trimmed)) continue;
        $cleanLines[] = $line;
    }
    $cleanSql = implode("\n", $cleanLines);

    // Split by semicolons
    $rawStatements = explode(';', $cleanSql);
    $statements = [];
    foreach ($rawStatements as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt) && strlen($stmt) > 5) {
            $statements[] = $stmt;
        }
    }

    $results[] = ['status' => 'ok', 'msg' => count($statements) . ' SQL statements parsed'];

    // ── Step 6: Execute each statement ───────────────────────
    $successCount = 0;
    $skipCount    = 0;
    $errorDetails = [];

    foreach ($statements as $index => $statement) {
        try {
            $pdo->exec($statement);
            $successCount++;
        } catch (PDOException $e) {
            $errCode = $e->getCode();
            $errMsg  = $e->getMessage();

            // 23000 = duplicate entry (safe to skip on re-run)
            // 42S01 = table already exists (safe to skip)
            if ($errCode == '23000' || strpos($errMsg, 'Duplicate entry') !== false || strpos($errMsg, 'already exists') !== false) {
                $skipCount++;
            } else {
                $hasError = true;
                // Get first 80 chars of the failing statement for context
                $snippet = substr(preg_replace('/\s+/', ' ', $statement), 0, 80);
                $errorDetails[] = [
                    'index'   => $index + 1,
                    'error'   => $errMsg,
                    'snippet' => $snippet
                ];
            }
        }
    }

    $results[] = ['status' => 'ok', 'msg' => "$successCount statements executed successfully"];
    if ($skipCount > 0) {
        $results[] = ['status' => 'info', 'msg' => "$skipCount duplicate entries skipped (safe — re-run detected)"];
    }

    // ── Step 7: Verify tables exist ──────────────────────────
    $expectedTables = ['users', 'profiles', 'food_items', 'food_logs', 'recommendations', 'notifications', 'achievements', 'user_achievements', 'user_streaks', 'user_points'];
    $existingTables = [];
    $tableCheck = $pdo->query("SHOW TABLES");
    while ($row = $tableCheck->fetch(PDO::FETCH_NUM)) {
        $existingTables[] = $row[0];
    }

    $missingTables = array_diff($expectedTables, $existingTables);
    if (empty($missingTables)) {
        $results[] = ['status' => 'ok', 'msg' => 'All ' . count($expectedTables) . ' tables verified ✓'];
    } else {
        $hasError = true;
        $results[] = ['status' => 'error', 'msg' => 'Missing tables: ' . implode(', ', $missingTables)];
    }

    // ── Step 8: Verify food items seeded ─────────────────────
    $foodCount = $pdo->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
    if ($foodCount > 0) {
        $results[] = ['status' => 'ok', 'msg' => "$foodCount food items seeded"];
    } else {
        $hasError = true;
        $results[] = ['status' => 'error', 'msg' => 'No food items found — seeding may have failed'];
    }

    // ── Step 9: Verify achievement seeded ────────────────────
    $achCount = $pdo->query("SELECT COUNT(*) FROM achievements")->fetchColumn();
    if ($achCount > 0) {
        $results[] = ['status' => 'ok', 'msg' => "$achCount achievements seeded"];
    } else {
        $hasError = true;
        $results[] = ['status' => 'error', 'msg' => 'No achievements found — seeding may have failed'];
    }

    // ── Step 10: Verify admin user ───────────────────────────
    $adminCheck = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($adminCheck > 0) {
        $results[] = ['status' => 'ok', 'msg' => 'Admin account verified (admin@nouriq.com / Admin@123)'];
    } else {
        $hasError = true;
        $results[] = ['status' => 'error', 'msg' => 'Admin user not created'];
    }

} catch (PDOException $e) {
    $hasError = true;
    $results[] = ['status' => 'error', 'msg' => 'PDO Error: ' . $e->getMessage()];
} catch (Exception $e) {
    $hasError = true;
    $results[] = ['status' => 'error', 'msg' => $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouriq — Installation</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #0a0a0f;
            color: #f0f0f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .install-card {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 24px;
            padding: 48px;
            max-width: 600px;
            width: 100%;
        }
        .header { text-align: center; margin-bottom: 32px; }
        .logo { font-size: 48px; margin-bottom: 12px; }
        h1 { font-size: 28px; font-weight: 800; margin-bottom: 4px; }
        h1 span {
            background: linear-gradient(135deg, #6C5CE7, #a29bfe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle { color: #8888a0; font-size: 14px; }

        .step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 6px;
            font-size: 14px;
            line-height: 1.5;
            transition: background 0.2s;
        }
        .step:hover { background: rgba(255,255,255,0.02); }
        .step .icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
        .step .text { flex: 1; }
        .step.ok .icon { color: #00cec9; }
        .step.info .icon { color: #fdcb6e; }
        .step.error .icon { color: #ff6b6b; }
        .step.error { background: rgba(255,107,107,0.06); border: 1px solid rgba(255,107,107,0.12); }

        .error-details {
            margin-top: 16px;
            padding: 16px;
            background: rgba(255,107,107,0.06);
            border: 1px solid rgba(255,107,107,0.12);
            border-radius: 12px;
        }
        .error-details h4 { color: #ff6b6b; font-size: 13px; margin-bottom: 8px; }
        .error-item {
            padding: 10px 12px;
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: 12px;
        }
        .error-item:last-child { margin-bottom: 0; }
        .error-item .err-msg { color: #ff6b6b; margin-bottom: 4px; font-weight: 500; }
        .error-item .err-sql { color: #8888a0; font-family: 'JetBrains Mono', monospace; font-size: 11px; word-break: break-all; }

        .divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,0.06);
            margin: 24px 0;
        }

        .result-banner {
            text-align: center;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 20px;
        }
        .result-banner.success {
            background: rgba(0,206,201,0.08);
            border: 1px solid rgba(0,206,201,0.2);
        }
        .result-banner.failure {
            background: rgba(255,107,107,0.08);
            border: 1px solid rgba(255,107,107,0.2);
        }
        .result-icon { font-size: 40px; }
        .result-title { font-size: 20px; font-weight: 700; margin-top: 8px; }
        .result-msg { font-size: 13px; color: #8888a0; margin-top: 4px; }

        .actions { text-align: center; margin-top: 24px; }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            margin: 0 6px;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary {
            background: linear-gradient(135deg, #6C5CE7, #a29bfe);
            color: white;
            box-shadow: 0 4px 16px rgba(108,92,231,0.3);
        }
        .btn-primary:hover { box-shadow: 0 8px 24px rgba(108,92,231,0.4); }
        .btn-secondary {
            background: rgba(255,255,255,0.06);
            color: #f0f0f5;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .credentials {
            margin-top: 16px;
            padding: 16px;
            background: rgba(108,92,231,0.06);
            border: 1px solid rgba(108,92,231,0.15);
            border-radius: 12px;
            font-size: 13px;
        }
        .credentials strong { color: #a29bfe; }
        .credentials .mono { font-family: 'JetBrains Mono', monospace; color: #f0f0f5; }
    </style>
</head>
<body>
    <div class="install-card">
        <div class="header">
            <div class="logo">🧬</div>
            <h1><span>Nouriq</span> Installation</h1>
            <p class="subtitle">Database setup & data seeding</p>
        </div>

        <!-- Result Banner -->
        <?php if (!$hasError): ?>
        <div class="result-banner success">
            <div class="result-icon">✅</div>
            <div class="result-title">Installation Successful!</div>
            <div class="result-msg">All tables created and data seeded</div>
        </div>
        <?php else: ?>
        <div class="result-banner failure">
            <div class="result-icon">⚠️</div>
            <div class="result-title">Installation Completed with Errors</div>
            <div class="result-msg">Some steps had issues — review details below</div>
        </div>
        <?php endif; ?>

        <!-- Step-by-step results -->
        <?php foreach ($results as $r): ?>
        <div class="step <?php echo $r['status']; ?>">
            <span class="icon"><?php echo $r['status'] === 'ok' ? '✅' : ($r['status'] === 'info' ? '⏩' : '❌'); ?></span>
            <span class="text"><?php echo $r['msg']; ?></span>
        </div>
        <?php endforeach; ?>

        <!-- Error details -->
        <?php if (!empty($errorDetails)): ?>
        <div class="error-details">
            <h4>⚠️ Error Details (<?php echo count($errorDetails); ?> failures)</h4>
            <?php foreach ($errorDetails as $err): ?>
            <div class="error-item">
                <div class="err-msg">Statement #<?php echo $err['index']; ?>: <?php echo htmlspecialchars($err['error']); ?></div>
                <div class="err-sql"><?php echo htmlspecialchars($err['snippet']); ?>…</div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <hr class="divider">

        <!-- Admin credentials -->
        <?php if (!$hasError): ?>
        <div class="credentials">
            <strong>🔑 Admin Login Credentials</strong><br>
            Email: <span class="mono">admin@nouriq.com</span><br>
            Password: <span class="mono">Admin@123</span>
        </div>
        <?php endif; ?>

        <!-- Action buttons -->
        <div class="actions">
            <?php if (!$hasError): ?>
            <a href="auth/login.php" class="btn btn-primary">Launch Nouriq →</a>
            <?php else: ?>
            <a href="install.php" class="btn btn-secondary">🔄 Retry Installation</a>
            <a href="auth/login.php" class="btn btn-primary">Try Anyway →</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

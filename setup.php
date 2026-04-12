<!DOCTYPE html>
<?php
// ============================================================
// BISU Planner — Browser-based Setup Wizard
// setup.php  (delete this file after installation!)
// ============================================================

// Security: block if already installed
if (file_exists(__DIR__ . '/installed.lock')) {
    die('<h2 style="font-family:sans-serif;text-align:center;margin-top:4rem;color:#991b1b">
         ⚠️ Setup already completed. Delete <code>installed.lock</code> to re-run.</h2>');
}

$step    = (int)($_GET['step'] ?? 1);
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $name = trim($_POST['db_name'] ?? 'bisu_planner');
    $user = trim($_POST['db_user'] ?? 'root');
    $pass = $_POST['db_pass'] ?? '';
    $url  = trim($_POST['app_url'] ?? 'http://localhost/bisu_planner');

    $adminFirst = trim($_POST['admin_first'] ?? 'System');
    $adminLast  = trim($_POST['admin_last']  ?? 'Administrator');
    $adminEmail = trim($_POST['admin_email'] ?? 'admin@bisu-calape.edu.ph');
    $adminPass  = $_POST['admin_pass'] ?? '';

    try {
        // 1. Test DB connection
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // 2. Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$name`");

        // 3. Run schema SQL
        $sql = file_get_contents(__DIR__ . '/database/schema.sql');
        // Remove the USE statement since we already selected db
        $sql = preg_replace('/USE bisu_planner;/', '', $sql);
        // Split and execute statement by statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $stmt) {
            if (!empty($stmt)) $pdo->exec($stmt);
        }

        // 4. Update/insert admin with real password
        $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
        $existing = $pdo->query("SELECT user_id FROM users WHERE username='admin' LIMIT 1")->fetch();
        if ($existing) {
            $pdo->prepare("UPDATE users SET password_hash=:h, first_name=:fn, last_name=:ln, email=:em WHERE username='admin'")
                ->execute([':h'=>$hash,':fn'=>$adminFirst,':ln'=>$adminLast,':em'=>$adminEmail]);
        } else {
            $pdo->prepare("INSERT INTO users (username,email,password_hash,first_name,last_name,role) VALUES('admin',:em,:h,:fn,:ln,'admin')")
                ->execute([':em'=>$adminEmail,':h'=>$hash,':fn'=>$adminFirst,':ln'=>$adminLast]);
            $adminId = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO admins (user_id,department) VALUES(:id,'ICT Department')")->execute([':id'=>$adminId]);
        }

        // 5. Write config file
        $config = "<?php
// ============================================================
// BISU Planner - Database Configuration (auto-generated)
// backend/config/database.php
// ============================================================

define('DB_HOST',    '$host');
define('DB_NAME',    '$name');
define('DB_USER',    '$user');
define('DB_PASS',    '$pass');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'BISU Academic Planner');
define('APP_URL',  '$url');
define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
define('SESSION_LIFETIME', 3600);

function getDB(): PDO {
    static \$pdo = null;
    if (\$pdo === null) {
        \$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        \$options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try { \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options); }
        catch (PDOException \$e) {
            http_response_code(500);
          die(json_encode(['success'=>false,'message'=>'We can’t sign you in right now. Please try again in a moment.']));
        }
    }
    return \$pdo;
}
";
        file_put_contents(__DIR__ . '/backend/config/database.php', $config);

        // 6. Create uploads directory
        $uploadsDir = __DIR__ . '/uploads/submissions/';
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

        // 7. Write lock file
        file_put_contents(__DIR__ . '/installed.lock', date('Y-m-d H:i:s'));

        $success = true;
        $message = "Installation completed successfully! The system is now ready.";
        $step    = 3;

    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BISU Planner — Setup Wizard</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DM Sans', sans-serif; background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
  .setup-card { background: #fff; border-radius: 16px; width: 100%; max-width: 560px; box-shadow: 0 20px 60px rgba(0,0,0,.35); overflow: hidden; }
  .setup-header { background: linear-gradient(135deg, #0f172a, #1e3a5f); padding: 2.5rem; text-align: center; }
  .setup-header .icon { width: 64px; height: 64px; background: #c9a227; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: 1rem; }
  .setup-header h1 { font-family: 'Playfair Display', serif; color: #c9a227; font-size: 1.6rem; }
  .setup-header p  { color: rgba(255,255,255,.55); font-size: .88rem; margin-top: .4rem; }
  .steps { display: flex; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
  .step-item { flex: 1; padding: .75rem; text-align: center; font-size: .75rem; font-weight: 600; color: #94a3b8; border-bottom: 2px solid transparent; }
  .step-item.active { color: #1e3a5f; border-bottom-color: #c9a227; }
  .step-item.done   { color: #22c55e; }
  .setup-body { padding: 2rem; }
  .form-group { margin-bottom: 1.25rem; }
  label { display: block; font-size: .8rem; font-weight: 700; color: #0f172a; margin-bottom: .4rem; text-transform: uppercase; letter-spacing: .04em; }
  input, select { width: 100%; padding: .72rem .9rem; border: 1.5px solid #e2e8f0; border-radius: 7px; font-family: 'DM Sans', sans-serif; font-size: .9rem; outline: none; transition: border-color .2s; }
  input:focus, select:focus { border-color: #c9a227; box-shadow: 0 0 0 3px rgba(201,162,39,.12); }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
  .btn-install { width: 100%; padding: .9rem; background: #1e3a5f; color: #fff; border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background .2s; margin-top: .5rem; }
  .btn-install:hover { background: #0f172a; }
  .alert { padding: .9rem 1rem; border-radius: 7px; font-size: .88rem; margin-bottom: 1.25rem; display: flex; gap: .5rem; align-items: flex-start; }
  .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
  .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
  .divider { border: none; border-top: 1px solid #e2e8f0; margin: 1.5rem 0; }
  .section-label { font-size: .7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 1rem; }
  .info-box { background: #fef9c3; border: 1px solid #fde047; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; font-size: .83rem; color: #854d0e; }
  .info-box strong { display: block; margin-bottom: .3rem; }
  .success-icon { font-size: 3.5rem; text-align: center; margin-bottom: 1rem; }
  .go-btn { display: block; width: 100%; padding: 1rem; background: #c9a227; color: #0f172a; border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 1rem; font-weight: 700; cursor: pointer; text-align: center; text-decoration: none; margin-top: 1rem; transition: background .2s; }
  .go-btn:hover { background: #b8911f; }
  ul.checklist { list-style: none; }
  ul.checklist li { padding: .4rem 0; font-size: .88rem; color: #475569; display: flex; gap: .5rem; align-items: center; }
  ul.checklist li::before { content: '✓'; color: #22c55e; font-weight: 700; }
</style>
</head>
<body>
<div class="setup-card">

  <div class="setup-header">
    <div class="icon">🎓</div>
    <h1>BISU Academic Planner</h1>
    <p>Calape Campus — Installation Wizard</p>
  </div>

  <div class="steps">
    <div class="step-item <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>">1. Welcome</div>
    <div class="step-item <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>">2. Configure</div>
    <div class="step-item <?= $step >= 3 ? 'active' : '' ?>">3. Complete</div>
  </div>

  <div class="setup-body">

    <?php if ($step === 1): ?>
      <h2 style="font-family:'Playfair Display',serif;color:#0f172a;margin-bottom:.5rem">Welcome!</h2>
      <p style="color:#64748b;margin-bottom:1.5rem;font-size:.9rem">This wizard will help you install the BISU Academic Planner system. Before you begin, make sure you have:</p>

      <ul class="checklist">
        <li>PHP 8.0 or higher installed</li>
        <li>MySQL 8.0 or higher running</li>
        <li>Apache with mod_rewrite enabled</li>
        <li>Your MySQL username and password ready</li>
        <li>The <code>database/schema.sql</code> file present</li>
      </ul>

      <div class="info-box" style="margin-top:1.5rem">
        <strong>⚠️ Important Security Note</strong>
        Delete this <code>setup.php</code> file after installation is complete to prevent unauthorized access.
      </div>

      <a href="?step=2" class="btn-install" style="display:block;text-align:center;text-decoration:none;line-height:1">
        Continue to Configuration →
      </a>

    <?php elseif ($step === 2): ?>
      <?php if ($message): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <form method="POST" action="?step=2">
        <div class="section-label">Database Configuration</div>
        <div class="form-row">
          <div class="form-group">
            <label>Database Host</label>
            <input type="text" name="db_host" value="localhost" required>
          </div>
          <div class="form-group">
            <label>Database Name</label>
            <input type="text" name="db_name" value="bisu_planner" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>MySQL Username</label>
            <input type="text" name="db_user" value="root" required>
          </div>
          <div class="form-group">
            <label>MySQL Password</label>
            <input type="password" name="db_pass" placeholder="Leave blank if none">
          </div>
        </div>
        <div class="form-group">
          <label>Application URL</label>
          <input type="url" name="app_url" value="http://localhost/bisu_planner" required>
        </div>

        <hr class="divider">
        <div class="section-label">Admin Account</div>

        <div class="form-row">
          <div class="form-group">
            <label>Admin First Name</label>
            <input type="text" name="admin_first" value="System" required>
          </div>
          <div class="form-group">
            <label>Admin Last Name</label>
            <input type="text" name="admin_last" value="Administrator" required>
          </div>
        </div>
        <div class="form-group">
          <label>Admin Email</label>
          <input type="email" name="admin_email" value="admin@bisu-calape.edu.ph" required>
        </div>
        <div class="form-group">
          <label>Admin Password (min. 8 characters)</label>
          <input type="password" name="admin_pass" placeholder="Choose a strong password" required minlength="8">
        </div>

        <button type="submit" class="btn-install">🚀 Install Now</button>
      </form>

    <?php elseif ($step === 3): ?>
      <div class="success-icon">🎉</div>
      <h2 style="font-family:'Playfair Display',serif;color:#0f172a;text-align:center;margin-bottom:.5rem">Installation Complete!</h2>
      <p style="text-align:center;color:#64748b;margin-bottom:1.5rem">BISU Academic Planner has been successfully installed.</p>

      <div class="alert alert-success">✓ Database created and all tables installed successfully.</div>

      <ul class="checklist">
        <li>Database tables created (14 tables)</li>
        <li>Admin account configured</li>
        <li>Configuration file written</li>
        <li>Upload directories created</li>
        <li>Sample course data loaded</li>
      </ul>

      <div class="info-box" style="margin-top:1.5rem">
        <strong>🔒 Security Action Required</strong>
        Please delete the <code>setup.php</code> file from your server immediately to prevent unauthorized reinstallation.
      </div>

      <a href="frontend/pages/login.php" class="go-btn">Go to Login Page →</a>
    <?php endif; ?>

  </div>
</div>
</body>
</html>

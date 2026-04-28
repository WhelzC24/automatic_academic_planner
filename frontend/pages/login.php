<!DOCTYPE html>
<html lang="en">

<head>
  <?php $loginCssVersion = @filemtime(__DIR__ . '/../assets/css/pages/login.css') ?: time(); ?>
  <?php $loginJsVersion = @filemtime(__DIR__ . '/../assets/js/pages/login.js') ?: time(); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — BISU Academic Planner</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/pages/login.css?v=<?php echo $loginCssVersion; ?>">
</head>

<body>

  <!-- Left Hero Panel -->
  <div class="hero-panel">
    <div class="school-badge">
      <div class="badge-icon">
        <img src="../img/bisu_logo.png" alt="BISU Logo">
      </div>
      <div class="badge-text">
        <h1>Bohol Island State University</h1>
        <p>Calape Campus — Academic Management System</p>
      </div>
    </div>
    <div class="hero-headline">
      <h2>Your <span>Academic Life</span><br>Organized &amp; on Track</h2>
      <p>Automated daily planner with smart deadline tracking, task prioritization, and real-time notifications — built for BISU students and faculty.</p>
      <div class="features-list">
        <div class="feat-item"><i class="fas fa-calendar-check"></i> Automated daily task planner</div>
        <div class="feat-item"><i class="fas fa-bell"></i> Smart deadline reminders</div>
        <div class="feat-item"><i class="fas fa-upload"></i> Online assignment submission</div>
        <div class="feat-item"><i class="fas fa-chart-line"></i> Academic progress tracking</div>
        <div class="feat-item"><i class="fas fa-users"></i> Instructor–student connection</div>
      </div>
    </div>
  </div>

  <!-- Right Form Panel -->
  <div class="form-panel">
    <div class="form-container">

      <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('login')">Sign In</button>
        <button class="tab-btn" onclick="switchTab('register')">Register</button>
      </div>

      <!-- LOGIN PANEL -->
      <div id="panel-login" class="panel active">
        <h2 class="form-title">Welcome back</h2>
        <p class="form-subtitle">Sign in to your BISU Planner account</p>

        <div class="alert" id="login-alert"></div>

        <div class="form-group">
          <label>Username or Email</label>
          <div class="input-wrap">
            <i class="fas fa-user"></i>
            <input type="text" id="login-username" class="form-control" placeholder="Enter username or email">
          </div>
        </div>
        <div class="form-group">
          <label>Password</label>
          <div class="input-wrap">
            <i class="fas fa-lock"></i>
            <input type="password" id="login-password" class="form-control has-toggle" placeholder="Enter password">
            <button type="button" onclick="togglePassword('login-password', this)" style="position:absolute;right:1.25rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--slate);padding:0;width:1.6rem;height:1.6rem;display:inline-flex;align-items:center;justify-content:center" aria-label="Show password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        <button class="btn-submit" onclick="doLogin()">
          <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
        <p style="text-align:center;color:var(--slate);font-size:0.82rem;margin-top:1.25rem;">
          Don't have an account? <a href="#" onclick="switchTab('register')">Register here</a><br>
        </p>
      </div>

      <!-- REGISTER PANEL -->
      <div id="panel-register" class="panel">
        <h2 class="form-title">Create Account</h2>
        <p class="form-subtitle">Student self-registration</p>

        <div class="alert" id="register-alert"></div>

        <div class="form-row">
          <div class="form-group">
            <label>First Name</label>
            <div class="input-wrap">
              <i class="fas fa-user"></i>
              <input type="text" id="reg-first" class="form-control" placeholder="First Name">
            </div>
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <div class="input-wrap">
              <i class="fas fa-user"></i>
              <input type="text" id="reg-last" class="form-control" placeholder="Last Name">
            </div>
          </div>
        </div>
        <div class="form-group">
          <label>Username</label>
          <div class="input-wrap">
            <i class="fas fa-at"></i>
            <input type="text" id="reg-username" class="form-control" placeholder="Username (no spaces)">
          </div>
        </div>
        <div class="form-group">
          <label>Email Address</label>
          <div class="input-wrap">
            <i class="fas fa-envelope"></i>
            <input type="email" id="reg-email" class="form-control" placeholder="example@bisu.edu.ph">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Student No.</label>
            <div class="input-wrap">
              <i class="fas fa-id-card"></i>
              <input type="text" id="reg-sn" class="form-control" placeholder="ID Number">
            </div>
          </div>
          <div class="form-group">
            <label>Year Level</label>
            <div class="input-wrap">
              <i class="fas fa-graduation-cap"></i>
              <select id="reg-year" class="form-control">
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
              </select>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label>Program</label>
          <div class="input-wrap">
            <i class="fas fa-book"></i>
            <select id="reg-program" class="form-control">
              <option value="">Select program...</option>
              <option value="BSCS">Bachelor of Science in Computer Science (BSCS)</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Phone Number</label>
          <div class="input-wrap">
            <i class="fas fa-phone"></i>
            <input type="text" id="reg-phone" class="form-control" placeholder="09XXXXXXXXX">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Password</label>
            <div class="input-wrap">
              <i class="fas fa-lock"></i>
              <input type="password" id="reg-pass" class="form-control has-toggle" placeholder="Min. 8 chars">
              <button type="button" onclick="togglePassword('reg-pass', this)" style="position:absolute;right:1.25rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--slate);padding:0;width:1.6rem;height:1.6rem;display:inline-flex;align-items:center;justify-content:center" aria-label="Show password">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>
          <div class="form-group">
            <label>Confirm</label>
            <div class="input-wrap">
              <i class="fas fa-lock"></i>
              <input type="password" id="reg-pass2" class="form-control has-toggle" placeholder="Repeat password">
              <button type="button" onclick="togglePassword('reg-pass2', this)" style="position:absolute;right:1.25rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--slate);padding:0;width:1.6rem;height:1.6rem;display:inline-flex;align-items:center;justify-content:center" aria-label="Show password">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>
        </div>
        <button class="btn-submit" onclick="doRegister()">
          <i class="fas fa-user-plus"></i> Create Account
        </button>
      </div>

    </div>
  </div>

  <div class="pw-change-overlay" id="pw-change-overlay" aria-hidden="true">
    <div class="pw-change-modal" role="dialog" aria-modal="true" aria-labelledby="pw-change-title">
      <h3 id="pw-change-title">Change Password Required</h3>
      <p>Your password was reset by an administrator. Please set a new password to continue.</p>

      <div class="alert" id="pw-change-alert"></div>

      <div class="form-group">
        <label>New Password</label>
        <div class="input-wrap">
          <i class="fas fa-lock"></i>
          <input type="password" id="force-new-pass" class="form-control has-toggle" placeholder="Enter new password">
          <button type="button" onclick="togglePassword('force-new-pass', this)" style="position:absolute;right:1.25rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--slate);padding:0;width:1.6rem;height:1.6rem;display:inline-flex;align-items:center;justify-content:center" aria-label="Show password">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label>Confirm New Password</label>
        <div class="input-wrap">
          <i class="fas fa-lock"></i>
          <input type="password" id="force-new-pass2" class="form-control has-toggle" placeholder="Confirm new password">
          <button type="button" onclick="togglePassword('force-new-pass2', this)" style="position:absolute;right:1.25rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--slate);padding:0;width:1.6rem;height:1.6rem;display:inline-flex;align-items:center;justify-content:center" aria-label="Show password">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>

      <button class="btn-submit" id="force-pass-btn" onclick="submitForcedPasswordChange()">
        <i class="fas fa-save"></i> Update Password
      </button>
    </div>
  </div>

  <script src="../assets/js/pages/login.js?v=<?php echo $loginJsVersion; ?>"></script>
</body>

</html>
<?php
// ============================================================
// BISU Planner — Profile & Settings Page (All Roles)
// frontend/pages/profile.php
// ============================================================
require_once __DIR__ . '/../../backend/config/helpers.php';
requireAuth();
require_once __DIR__ . '/layout.php';

$role = $_SESSION['role'];
layout_header('My Profile', $role, [APP_URL . '/frontend/assets/css/pages/profile.css']);
?>
<div class="app-shell">
  <?php layout_sidebar($role, ''); ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h1>My Profile</h1>
        <p>Manage your account information and security settings</p>
      </div>
    </div>

    <div class="page-content">
      <div class="profile-layout">
        <div class="profile-main">

          <!-- Profile Tabs -->
          <div style="display:flex;gap:.5rem;margin-bottom:1.75rem;border-bottom:2px solid var(--border);padding-bottom:0">
            <button class="prof-tab active" onclick="showTab('info',this)" style="padding:.65rem 1.25rem;border:none;background:none;font-family:'DM Sans',sans-serif;font-size:.9rem;font-weight:600;cursor:pointer;color:var(--deep);border-bottom:2px solid var(--deep);margin-bottom:-2px">
              <i class="fas fa-user"></i> Account Info
            </button>
            <button class="prof-tab" onclick="showTab('security',this)" style="padding:.65rem 1.25rem;border:none;background:none;font-family:'DM Sans',sans-serif;font-size:.9rem;font-weight:600;cursor:pointer;color:var(--slate);border-bottom:2px solid transparent;margin-bottom:-2px">
              <i class="fas fa-lock"></i> Security
            </button>
          </div>

          <!-- ACCOUNT INFO TAB -->
          <div id="tab-info">
            <div style="display:grid;grid-template-columns:200px 1fr;gap:2rem;align-items:start">

              <!-- Avatar Card -->
              <div class="card" style="text-align:center;padding:1.5rem">
                <div id="avatar-circle" style="
            width:80px;height:80px;border-radius:50%;
            background:linear-gradient(135deg,var(--gold),#e8b832);
            display:flex;align-items:center;justify-content:center;
            font-size:2rem;font-weight:700;color:var(--navy);
            margin:0 auto 1rem;
          ">?</div>
                <div id="prof-fullname" style="font-weight:700;color:var(--navy);font-size:1rem">Loading...</div>
                <div id="prof-role-badge" style="margin-top:.4rem"></div>
                <div id="prof-joined" style="color:var(--slate);font-size:.75rem;margin-top:.4rem"></div>
              </div>

              <!-- Edit Form -->
              <div class="card">
                <div class="card-header">
                  <div class="card-title"><i class="fas fa-edit"></i> Edit Profile</div>
                </div>
                <div class="card-body">
                  <div class="alert" id="info-alert"></div>
                  <div class="form-row">
                    <div class="form-group">
                      <label>First Name *</label>
                      <input type="text" class="form-control" id="p-first">
                    </div>
                    <div class="form-group">
                      <label>Last Name *</label>
                      <input type="text" class="form-control" id="p-last">
                    </div>
                  </div>
                  <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" class="form-control" id="p-email">
                  </div>
                  <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" class="form-control" id="p-phone" placeholder="09XXXXXXXXX">
                  </div>

                  <!-- Student-specific fields -->
                  <div id="student-fields" style="display:none">
                    <div style="border-top:1px solid var(--border);margin:1.25rem 0;padding-top:1.25rem">
                      <div style="font-size:.8rem;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.06em;margin-bottom:1rem">Academic Information</div>
                    </div>
                    <div class="form-row">
                      <div class="form-group">
                        <label>Student Number</label>
                        <input type="text" class="form-control" id="p-sn" disabled style="background:var(--bg);cursor:not-allowed">
                      </div>
                      <div class="form-group">
                        <label>Year Level</label>
                        <select class="form-control" id="p-year">
                          <option value="1">1st Year</option>
                          <option value="2">2nd Year</option>
                          <option value="3">3rd Year</option>
                          <option value="4">4th Year</option>
                        </select>
                      </div>
                    </div>
                    <div class="form-group">
                      <label>Program / Course</label>
                      <input type="text" class="form-control" id="p-program" placeholder="BS Computer Science">
                    </div>
                  </div>

                  <!-- Instructor-specific fields -->
                  <div id="instructor-fields" style="display:none">
                    <div style="border-top:1px solid var(--border);margin:1.25rem 0;padding-top:1.25rem">
                      <div style="font-size:.8rem;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.06em;margin-bottom:1rem">Faculty Information</div>
                    </div>
                    <div class="form-row">
                      <div class="form-group">
                        <label>Department</label>
                        <input type="text" class="form-control" id="p-dept">
                      </div>
                      <div class="form-group">
                        <label>Designation</label>
                        <input type="text" class="form-control" id="p-desig" placeholder="Instructor I">
                      </div>
                    </div>
                    <div class="form-group">
                      <label>Office Location</label>
                      <input type="text" class="form-control" id="p-office" placeholder="Room 201, Building A">
                    </div>
                  </div>

                  <div style="display:flex;justify-content:flex-end;margin-top:.5rem">
                    <button class="btn btn-primary" onclick="saveProfile()">
                      <i class="fas fa-save"></i> Save Changes
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- SECURITY TAB -->
          <div id="tab-security" style="display:none">
            <div class="security-grid">
              <div class="card">
                <div class="card-header">
                  <div class="card-title"><i class="fas fa-key"></i> Change Password</div>
                </div>
                <div class="card-body">
                  <div class="alert" id="sec-alert"></div>
                  <div class="form-group">
                    <label>Current Password</label>
                    <div style="position:relative">
                      <input type="password" class="form-control" id="s-current" placeholder="Enter current password">
                      <button onclick="togglePw('s-current',this)" style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--slate)">
                        <i class="fas fa-eye"></i>
                      </button>
                    </div>
                  </div>
                  <div class="form-group">
                    <label>New Password</label>
                    <div style="position:relative">
                      <input type="password" class="form-control" id="s-new" placeholder="At least 8 characters">
                      <button onclick="togglePw('s-new',this)" style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--slate)">
                        <i class="fas fa-eye"></i>
                      </button>
                    </div>
                    <!-- Password strength bar -->
                    <div id="pw-strength-bar" style="margin-top:.5rem;height:4px;border-radius:2px;background:var(--border);overflow:hidden">
                      <div id="pw-strength-fill" style="height:100%;width:0%;background:var(--red);transition:width .3s,background .3s"></div>
                    </div>
                    <div id="pw-strength-label" style="font-size:.72rem;color:var(--slate);margin-top:.2rem"></div>
                  </div>
                  <div class="form-group">
                    <label>Confirm New Password</label>
                    <div style="position:relative">
                      <input type="password" class="form-control" id="s-confirm" placeholder="Repeat new password">
                      <button onclick="togglePw('s-confirm',this)" style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--slate)">
                        <i class="fas fa-eye"></i>
                      </button>
                    </div>
                  </div>
                  <button class="btn btn-primary" style="width:100%" onclick="changePassword()">
                    <i class="fas fa-key"></i> Update Password
                  </button>
                </div>
              </div>

              <!-- Security Info Card -->
              <div class="card">
                <div class="card-header">
                  <div class="card-title"><i class="fas fa-shield-alt"></i> Security Tips</div>
                </div>
                <div class="card-body">
                  <div style="display:flex;flex-direction:column;gap:.75rem;font-size:.88rem;color:var(--slate)">
                    <div style="display:flex;gap:.75rem;align-items:flex-start">
                      <i class="fas fa-check-circle" style="color:var(--green);margin-top:.15rem;flex-shrink:0"></i>
                      <span>Use a password with at least 8 characters, including uppercase, numbers, and symbols.</span>
                    </div>
                    <div style="display:flex;gap:.75rem;align-items:flex-start">
                      <i class="fas fa-check-circle" style="color:var(--green);margin-top:.15rem;flex-shrink:0"></i>
                      <span>Never share your login credentials with anyone, including classmates.</span>
                    </div>
                    <div style="display:flex;gap:.75rem;align-items:flex-start">
                      <i class="fas fa-check-circle" style="color:var(--green);margin-top:.15rem;flex-shrink:0"></i>
                      <span>Always sign out when using a shared or public computer.</span>
                    </div>
                    <div style="display:flex;gap:.75rem;align-items:flex-start">
                      <i class="fas fa-check-circle" style="color:var(--green);margin-top:.15rem;flex-shrink:0"></i>
                      <span>Change your password regularly, especially at the start of each semester.</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <aside class="profile-side">
          <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header">
              <div class="card-title"><i class="fas fa-id-badge"></i> Account Summary</div>
            </div>
            <div class="card-body">
              <div class="snapshot-row"><span>Role</span><strong id="side-role">—</strong></div>
              <div class="snapshot-row"><span>Username</span><strong><?= htmlspecialchars($_SESSION['username'] ?? '—') ?></strong></div>
              <div class="snapshot-row"><span>Email</span><strong id="side-email">—</strong></div>
              <div class="snapshot-row"><span>Joined</span><strong id="side-joined">—</strong></div>
            </div>
          </div>

          <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header">
              <div class="card-title"><i class="fas fa-shield-alt"></i> Security Checklist</div>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:.7rem;color:var(--slate);font-size:.86rem">
              <div><i class="fas fa-check-circle" style="color:var(--green);margin-right:.45rem"></i>Password has at least 8 characters</div>
              <div><i class="fas fa-check-circle" style="color:var(--green);margin-right:.45rem"></i>Never share credentials</div>
              <div><i class="fas fa-check-circle" style="color:var(--green);margin-right:.45rem"></i>Sign out on shared devices</div>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <div class="card-title"><i class="fas fa-bolt"></i> Quick Navigation</div>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:.6rem">
              <a class="btn btn-outline" href="<?= APP_URL ?>/frontend/pages/<?= htmlspecialchars($role) ?>/dashboard.php"><i class="fas fa-tachometer-alt"></i> Back to Dashboard</a>
              <?php if ($role === 'admin'): ?>
                <a class="btn btn-outline" href="<?= APP_URL ?>/frontend/pages/admin/users.php"><i class="fas fa-users"></i> Manage Users</a>
              <?php endif; ?>
            </div>
          </div>
        </aside>
      </div>
    </div><!-- /page-content -->
  </div><!-- /main-content -->
</div>

<script>
  window.PROFILE_ROLE = '<?= $role ?>';
</script>
<script src="<?= APP_URL ?>/frontend/assets/js/pages/profile.js"></script>
<?php layout_footer(); ?>
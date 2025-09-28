<?php
// Include the database connection and security functions
include './includes/dbconn.inc.php';
include './includes/security.inc.php';

// Initialize secure session
initializeSecureSession();

// Check if user is logged in
if (!isValidSession()) {
    header('Location: signin.php');
    exit();
}

// Get current user data
$user_id = $_SESSION['userId'];
$query = "SELECT * FROM users WHERE id_users = ?";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    logSecurityEvent('Prepared statement failed in profile.php: ' . mysqli_error($conn));
    die('Database preparation error');
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    logSecurityEvent('User not found in profile.php for ID: ' . $user_id);
    header('Location: signin.php');
    exit();
}

mysqli_stmt_close($stmt);

// Handle profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        logSecurityEvent('CSRF token validation failed for user: ' . $user['uid_users']);
        $error_message = 'Security validation failed. Please try again.';
    } else {
        $uid_users = sanitizeInput($_POST['uid_users'] ?? '');
        $email_users = sanitizeInput($_POST['email_users'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        $validation_errors = [];
        
        if (empty($uid_users)) {
            $validation_errors[] = 'Username is required';
        }
        
        if (empty($email_users) || !filter_var($email_users, FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = 'Valid email is required';
        }
        
        // Check if username is taken by another user
        $check_query = "SELECT id_users FROM users WHERE uid_users = ? AND id_users != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "si", $uid_users, $user_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $validation_errors[] = 'Username is already taken';
            }
            
            mysqli_stmt_close($check_stmt);
        }
        
        // Check if email is taken by another user
        $email_check_query = "SELECT id_users FROM users WHERE email_users = ? AND id_users != ?";
        $email_check_stmt = mysqli_prepare($conn, $email_check_query);
        
        if ($email_check_stmt) {
            mysqli_stmt_bind_param($email_check_stmt, "si", $email_users, $user_id);
            mysqli_stmt_execute($email_check_stmt);
            $email_check_result = mysqli_stmt_get_result($email_check_stmt);
            
            if (mysqli_num_rows($email_check_result) > 0) {
                $validation_errors[] = 'Email is already taken';
            }
            
            mysqli_stmt_close($email_check_stmt);
        }
        
        // Password change validation
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $validation_errors[] = 'Current password is required to change password';
            } elseif (!password_verify($current_password, $user['pwd_users'])) {
                $validation_errors[] = 'Current password is incorrect';
            } elseif (strlen($new_password) < 6) {
                $validation_errors[] = 'New password must be at least 6 characters';
            } elseif ($new_password !== $confirm_password) {
                $validation_errors[] = 'New passwords do not match';
            }
        }
        
        if (empty($validation_errors)) {
            // Update profile
            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET uid_users = ?, email_users = ?, pwd_users = ? WHERE id_users = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                
                if ($update_stmt) {
                    mysqli_stmt_bind_param($update_stmt, "sssi", $uid_users, $email_users, $hashed_password, $user_id);
                    
                    if (mysqli_stmt_execute($update_stmt)) {
                        $success_message = 'Profile and password updated successfully!';
                        $user['uid_users'] = $uid_users;
                        $user['email_users'] = $email_users;
                        $_SESSION['username'] = $uid_users; // Update session
                        logSecurityEvent('Profile and password updated for user: ' . $uid_users);
                    } else {
                        $error_message = 'Failed to update profile. Please try again.';
                        logSecurityEvent('Profile update failed for user: ' . $uid_users);
                    }
                    
                    mysqli_stmt_close($update_stmt);
                }
            } else {
                // Update without password change
                $update_query = "UPDATE users SET uid_users = ?, email_users = ? WHERE id_users = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                
                if ($update_stmt) {
                    mysqli_stmt_bind_param($update_stmt, "ssi", $uid_users, $email_users, $user_id);
                    
                    if (mysqli_stmt_execute($update_stmt)) {
                        $success_message = 'Profile updated successfully!';
                        $user['uid_users'] = $uid_users;
                        $user['email_users'] = $email_users;
                        $_SESSION['username'] = $uid_users; // Update session
                        logSecurityEvent('Profile updated for user: ' . $uid_users);
                    } else {
                        $error_message = 'Failed to update profile. Please try again.';
                        logSecurityEvent('Profile update failed for user: ' . $uid_users);
                    }
                    
                    mysqli_stmt_close($update_stmt);
                }
            }
        } else {
            $error_message = implode(', ', $validation_errors);
        }
    }
}

// Get user statistics (health records submitted)
$stats_query = "SELECT COUNT(*) as total_records FROM records WHERE email = ?";
$stats_stmt = mysqli_prepare($conn, $stats_query);
$total_records = 0;

if ($stats_stmt) {
    mysqli_stmt_bind_param($stats_stmt, "s", $user['email_users']);
    mysqli_stmt_execute($stats_stmt);
    $stats_result = mysqli_stmt_get_result($stats_stmt);
    $stats = mysqli_fetch_assoc($stats_result);
    $total_records = $stats['total_records'];
    mysqli_stmt_close($stats_stmt);
}

// Get recent activity
$recent_query = "SELECT id, full_name, temp, diagnosed, encountered, vaccinated FROM records WHERE email = ? ORDER BY id DESC LIMIT 5";
$recent_stmt = mysqli_prepare($conn, $recent_query);
$recent_records = [];

if ($recent_stmt) {
    mysqli_stmt_bind_param($recent_stmt, "s", $user['email_users']);
    mysqli_stmt_execute($recent_stmt);
    $recent_result = mysqli_stmt_get_result($recent_stmt);
    
    while ($row = mysqli_fetch_assoc($recent_result)) {
        $recent_records[] = $row;
    }
    
    mysqli_stmt_close($recent_stmt);
}

?>
<?php include "./header.php"; ?>
  <main>
    <div class="wrapper">
      <!-- Profile Header -->
      <div class="profile-header">
        <div class="profile-avatar">
          <i class="fa-solid fa-user-circle"></i>
        </div>
        <div class="profile-info">
          <h1 class="profile-title"><?php echo sanitizeOutput($user['uid_users']); ?></h1>
          <p class="profile-subtitle"><?php echo sanitizeOutput($user['email_users']); ?></p>
          <div class="profile-stats">
            <div class="stat-item">
              <i class="fa-solid fa-file-medical"></i>
              <span class="stat-value"><?php echo $total_records; ?></span>
              <span class="stat-label">Health Records</span>
            </div>
            <div class="stat-item">
              <i class="fa-solid fa-calendar"></i>
              <span class="stat-value">Member Since</span>
              <span class="stat-label">2023</span>
            </div>
          </div>
        </div>
        <div class="profile-actions">
          <a href="./dashboard_admin.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Dashboard
          </a>
        </div>
      </div>

      <?php if ($success_message): ?>
        <div class="alert alert-success">
          <i class="fa-solid fa-check-circle"></i>
          <?php echo sanitizeOutput($success_message); ?>
        </div>
      <?php endif; ?>

      <?php if ($error_message): ?>
        <div class="alert alert-error">
          <i class="fa-solid fa-exclamation-triangle"></i>
          <?php echo sanitizeOutput($error_message); ?>
        </div>
      <?php endif; ?>

      <div class="profile-content">
        <!-- Edit Profile Section -->
        <div class="profile-section">
          <div class="section-header">
            <h2 class="section-title">
              <i class="fa-solid fa-user-edit"></i>
              Edit Profile
            </h2>
          </div>
          
          <form method="POST" action="" class="profile-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
              <label for="uid_users" class="form-label">
                <i class="fa-solid fa-user"></i>
                Username
              </label>
              <input 
                type="text" 
                id="uid_users" 
                name="uid_users" 
                value="<?php echo sanitizeOutput($user['uid_users']); ?>" 
                class="form-input"
                required
              >
            </div>

            <div class="form-group">
              <label for="email_users" class="form-label">
                <i class="fa-solid fa-envelope"></i>
                Email Address
              </label>
              <input 
                type="email" 
                id="email_users" 
                name="email_users" 
                value="<?php echo sanitizeOutput($user['email_users']); ?>" 
                class="form-input"
                required
              >
            </div>

            <div class="password-section">
              <h3 class="password-title">
                <i class="fa-solid fa-lock"></i>
                Change Password (Optional)
              </h3>
              
              <div class="form-group">
                <label for="current_password" class="form-label">Current Password</label>
                <input 
                  type="password" 
                  id="current_password" 
                  name="current_password" 
                  class="form-input"
                  autocomplete="current-password"
                >
              </div>

              <div class="form-group">
                <label for="new_password" class="form-label">New Password</label>
                <input 
                  type="password" 
                  id="new_password" 
                  name="new_password" 
                  class="form-input"
                  autocomplete="new-password"
                >
                <small class="form-hint">Minimum 6 characters</small>
              </div>

              <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input 
                  type="password" 
                  id="confirm_password" 
                  name="confirm_password" 
                  class="form-input"
                  autocomplete="new-password"
                >
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" name="update_profile" class="btn btn-primary">
                <i class="fa-solid fa-save"></i>
                Update Profile
              </button>
            </div>
          </form>
        </div>

        <!-- Recent Activity Section -->
        <div class="profile-section">
          <div class="section-header">
            <h2 class="section-title">
              <i class="fa-solid fa-history"></i>
              Recent Activity
            </h2>
            <a href="./dashboard_admin.php" class="section-link">View All Records</a>
          </div>

          <?php if (empty($recent_records)): ?>
            <div class="empty-state">
              <i class="fa-solid fa-file-medical"></i>
              <h3>No Health Records Yet</h3>
              <p>You haven't submitted any health declarations yet.</p>
              <a href="./add.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                Add First Record
              </a>
            </div>
          <?php else: ?>
            <div class="activity-list">
              <?php foreach ($recent_records as $record): ?>
                <div class="activity-item">
                  <div class="activity-icon">
                    <i class="fa-solid fa-user-check"></i>
                  </div>
                  <div class="activity-content">
                    <h4 class="activity-title">Health Declaration - <?php echo sanitizeOutput($record['full_name']); ?></h4>
                    <div class="activity-details">
                      <span class="detail-item">
                        <i class="fa-solid fa-thermometer-half"></i>
                        <?php echo sanitizeOutput($record['temp']); ?>°C
                      </span>
                      <span class="status-badge <?php echo strtolower($record['diagnosed']); ?>">
                        Diagnosed: <?php echo sanitizeOutput($record['diagnosed']); ?>
                      </span>
                      <span class="status-badge <?php echo strtolower($record['vaccinated']); ?>">
                        Vaccinated: <?php echo sanitizeOutput($record['vaccinated']); ?>
                      </span>
                    </div>
                  </div>
                  <div class="activity-actions">
                    <a href="./update.php?id=<?php echo (int)$record['id']; ?>" class="btn btn-sm btn-outline" title="Edit">
                      <i class="fa-solid fa-edit"></i>
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Account Security Section -->
        <div class="profile-section">
          <div class="section-header">
            <h2 class="section-title">
              <i class="fa-solid fa-shield-alt"></i>
              Account Security
            </h2>
          </div>

          <div class="security-info">
            <div class="security-item">
              <div class="security-icon">
                <i class="fa-solid fa-key"></i>
              </div>
              <div class="security-content">
                <h4 class="security-title">Password Protection</h4>
                <p class="security-description">Your password is encrypted and stored securely. Consider updating it regularly.</p>
              </div>
              <div class="security-status">
                <span class="status-indicator secure">
                  <i class="fa-solid fa-check"></i> Secure
                </span>
              </div>
            </div>

            <div class="security-item">
              <div class="security-icon">
                <i class="fa-solid fa-user-shield"></i>
              </div>
              <div class="security-content">
                <h4 class="security-title">Account Status</h4>
                <p class="security-description">Your account is active and in good standing.</p>
              </div>
              <div class="security-status">
                <span class="status-indicator secure">
                  <i class="fa-solid fa-check"></i> Active
                </span>
              </div>
            </div>

            <div class="security-item">
              <div class="security-icon">
                <i class="fa-solid fa-database"></i>
              </div>
              <div class="security-content">
                <h4 class="security-title">Data Privacy</h4>
                <p class="security-description">Your health information is protected and used only for COVID-19 tracking purposes.</p>
              </div>
              <div class="security-status">
                <span class="status-indicator secure">
                  <i class="fa-solid fa-check"></i> Protected
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
      <div class="loading-spinner"></div>
    </div>
  </main>

  <script>
    // Form enhancement and validation
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.querySelector('.profile-form');
      const passwordFields = document.querySelectorAll('input[type="password"]');
      const loadingOverlay = document.getElementById('loadingOverlay');
      
      // Show loading on form submission
      if (form) {
        form.addEventListener('submit', function() {
          loadingOverlay.classList.add('active');
        });
      }
      
      // Password confirmation validation
      const newPassword = document.getElementById('new_password');
      const confirmPassword = document.getElementById('confirm_password');
      
      if (newPassword && confirmPassword) {
        confirmPassword.addEventListener('input', function() {
          if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
          } else {
            confirmPassword.setCustomValidity('');
          }
        });
        
        newPassword.addEventListener('input', function() {
          if (newPassword.value !== confirmPassword.value && confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
          } else {
            confirmPassword.setCustomValidity('');
          }
        });
      }
      
      // Auto-hide success/error messages after 5 seconds
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        setTimeout(() => {
          alert.style.opacity = '0';
          alert.style.transform = 'translateY(-10px)';
          setTimeout(() => alert.remove(), 300);
        }, 5000);
      });
    });
  </script>
</main>
</body>

</html>
<?php
// Clean up any open database connections
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>
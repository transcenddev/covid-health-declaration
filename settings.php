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
    logSecurityEvent('Prepared statement failed in settings.php: ' . mysqli_error($conn));
    die('Database preparation error');
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    logSecurityEvent('User not found in settings.php for ID: ' . $user_id);
    header('Location: signin.php');
    exit();
}

mysqli_stmt_close($stmt);

// Handle settings updates
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        logSecurityEvent('CSRF token validation failed for user: ' . $user['uid_users']);
        $error_message = 'Security validation failed. Please try again.';
    } else {
        // Handle different setting types
        if (isset($_POST['update_notifications'])) {
            // Notification settings (stored in session for demo)
            $_SESSION['settings'] = $_SESSION['settings'] ?? [];
            $_SESSION['settings']['email_notifications'] = isset($_POST['email_notifications']) ? 1 : 0;
            $_SESSION['settings']['sms_notifications'] = isset($_POST['sms_notifications']) ? 1 : 0;
            $_SESSION['settings']['browser_notifications'] = isset($_POST['browser_notifications']) ? 1 : 0;
            $_SESSION['settings']['newsletter'] = isset($_POST['newsletter']) ? 1 : 0;
            
            $success_message = 'Notification preferences updated successfully!';
            logSecurityEvent('Notification settings updated for user: ' . $user['uid_users']);
        }
        
        if (isset($_POST['update_privacy'])) {
            // Privacy settings (stored in session for demo)
            $_SESSION['settings'] = $_SESSION['settings'] ?? [];
            $_SESSION['settings']['data_sharing'] = sanitizeInput($_POST['data_sharing'] ?? 'minimal');
            $_SESSION['settings']['profile_visibility'] = sanitizeInput($_POST['profile_visibility'] ?? 'private');
            $_SESSION['settings']['analytics'] = isset($_POST['analytics']) ? 1 : 0;
            
            $success_message = 'Privacy settings updated successfully!';
            logSecurityEvent('Privacy settings updated for user: ' . $user['uid_users']);
        }
        
        if (isset($_POST['update_appearance'])) {
            // Appearance settings (stored in session for demo)
            $_SESSION['settings'] = $_SESSION['settings'] ?? [];
            $_SESSION['settings']['theme'] = sanitizeInput($_POST['theme'] ?? 'dark');
            $_SESSION['settings']['language'] = sanitizeInput($_POST['language'] ?? 'en');
            $_SESSION['settings']['timezone'] = sanitizeInput($_POST['timezone'] ?? 'UTC');
            $_SESSION['settings']['date_format'] = sanitizeInput($_POST['date_format'] ?? 'Y-m-d');
            
            $success_message = 'Appearance settings updated successfully!';
            logSecurityEvent('Appearance settings updated for user: ' . $user['uid_users']);
        }
        
        if (isset($_POST['update_security'])) {
            // Security settings
            $_SESSION['settings'] = $_SESSION['settings'] ?? [];
            $_SESSION['settings']['two_factor'] = isset($_POST['two_factor']) ? 1 : 0;
            $_SESSION['settings']['login_alerts'] = isset($_POST['login_alerts']) ? 1 : 0;
            $_SESSION['settings']['session_timeout'] = (int)($_POST['session_timeout'] ?? 30);
            
            $success_message = 'Security settings updated successfully!';
            logSecurityEvent('Security settings updated for user: ' . $user['uid_users']);
        }
        
        if (isset($_POST['export_data'])) {
            // Data export functionality
            $export_query = "SELECT * FROM records WHERE email = ?";
            $export_stmt = mysqli_prepare($conn, $export_query);
            
            if ($export_stmt) {
                mysqli_stmt_bind_param($export_stmt, "s", $user['email_users']);
                mysqli_stmt_execute($export_stmt);
                $export_result = mysqli_stmt_get_result($export_stmt);
                
                $data = [];
                while ($row = mysqli_fetch_assoc($export_result)) {
                    $data[] = $row;
                }
                
                // Set headers for JSON download
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="health_records_' . date('Y-m-d') . '.json"');
                echo json_encode($data, JSON_PRETTY_PRINT);
                
                logSecurityEvent('Data export requested by user: ' . $user['uid_users']);
                mysqli_stmt_close($export_stmt);
                exit();
            }
        }
        
        if (isset($_POST['delete_account'])) {
            // Account deletion confirmation
            $password = $_POST['confirm_password'] ?? '';
            
            if (empty($password)) {
                $error_message = 'Password confirmation is required to delete account.';
            } elseif (!password_verify($password, $user['pwd_users'])) {
                $error_message = 'Password is incorrect. Account deletion cancelled.';
            } else {
                // In a real application, you would:
                // 1. Delete all user records
                // 2. Delete user account
                // 3. Send confirmation email
                // For demo purposes, we'll just show a message
                $success_message = 'Account deletion request received. This feature is disabled in demo mode.';
                logSecurityEvent('Account deletion requested by user: ' . $user['uid_users']);
            }
        }
    }
}

// Get current settings with defaults
$settings = $_SESSION['settings'] ?? [];
$current_settings = [
    'email_notifications' => $settings['email_notifications'] ?? 1,
    'sms_notifications' => $settings['sms_notifications'] ?? 0,
    'browser_notifications' => $settings['browser_notifications'] ?? 1,
    'newsletter' => $settings['newsletter'] ?? 0,
    'data_sharing' => $settings['data_sharing'] ?? 'minimal',
    'profile_visibility' => $settings['profile_visibility'] ?? 'private',
    'analytics' => $settings['analytics'] ?? 0,
    'theme' => $settings['theme'] ?? 'dark',
    'language' => $settings['language'] ?? 'en',
    'timezone' => $settings['timezone'] ?? 'UTC',
    'date_format' => $settings['date_format'] ?? 'Y-m-d',
    'two_factor' => $settings['two_factor'] ?? 0,
    'login_alerts' => $settings['login_alerts'] ?? 1,
    'session_timeout' => $settings['session_timeout'] ?? 30,
];

// Get user statistics for data management
$stats_query = "SELECT COUNT(*) as total_records, MIN(id) as first_record_date, MAX(id) as last_record_date FROM records WHERE email = ?";
$stats_stmt = mysqli_prepare($conn, $stats_query);
$user_stats = ['total_records' => 0, 'first_record_date' => null, 'last_record_date' => null];

if ($stats_stmt) {
    mysqli_stmt_bind_param($stats_stmt, "s", $user['email_users']);
    mysqli_stmt_execute($stats_stmt);
    $stats_result = mysqli_stmt_get_result($stats_stmt);
    $user_stats = mysqli_fetch_assoc($stats_result);
    mysqli_stmt_close($stats_stmt);
}

?>
<?php include "./header.php"; ?>
  <main>
    <div class="wrapper">
      <!-- Settings Header -->
      <div class="settings-header">
        <div class="settings-nav">
          <a href="./profile.php" class="nav-back">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Profile
          </a>
        </div>
        <div class="settings-info">
          <h1 class="settings-title">
            <i class="fa-solid fa-cog"></i>
            Account Settings
          </h1>
          <p class="settings-subtitle">Manage your preferences and account configuration</p>
        </div>
        <div class="settings-actions">
          <a href="./dashboard_admin.php" class="btn btn-secondary">
            <i class="fa-solid fa-dashboard"></i>
            Dashboard
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

      <div class="settings-content">
        <!-- Navigation Tabs -->
        <div class="settings-tabs">
          <button class="tab-btn active" data-tab="notifications">
            <i class="fa-solid fa-bell"></i>
            Notifications
          </button>
          <button class="tab-btn" data-tab="privacy">
            <i class="fa-solid fa-shield-alt"></i>
            Privacy
          </button>
          <button class="tab-btn" data-tab="appearance">
            <i class="fa-solid fa-palette"></i>
            Appearance
          </button>
          <button class="tab-btn" data-tab="security">
            <i class="fa-solid fa-lock"></i>
            Security
          </button>
          <button class="tab-btn" data-tab="data">
            <i class="fa-solid fa-database"></i>
            Data
          </button>
        </div>

        <!-- Notifications Tab -->
        <div class="tab-content active" id="notifications">
          <div class="settings-section">
            <div class="section-header">
              <h2 class="section-title">
                <i class="fa-solid fa-bell"></i>
                Notification Preferences
              </h2>
              <p class="section-description">Choose how you'd like to be notified about important updates</p>
            </div>
            
            <form method="POST" action="" class="settings-form">
              <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
              
              <div class="setting-group">
                <div class="setting-item">
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">Email Notifications</h4>
                      <p class="setting-description">Receive updates and alerts via email</p>
                    </div>
                  </div>
                  <div class="setting-control">
                    <label class="toggle-switch">
                      <input type="checkbox" name="email_notifications" <?php echo $current_settings['email_notifications'] ? 'checked' : ''; ?>>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                </div>

                <div class="setting-item">
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">SMS Notifications</h4>
                      <p class="setting-description">Get text messages for critical alerts</p>
                    </div>
                  </div>
                  <div class="setting-control">
                    <label class="toggle-switch">
                      <input type="checkbox" name="sms_notifications" <?php echo $current_settings['sms_notifications'] ? 'checked' : ''; ?>>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                </div>

                <div class="setting-item">
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">Browser Notifications</h4>
                      <p class="setting-description">Show notifications in your web browser</p>
                    </div>
                  </div>
                  <div class="setting-control">
                    <label class="toggle-switch">
                      <input type="checkbox" name="browser_notifications" <?php echo $current_settings['browser_notifications'] ? 'checked' : ''; ?>>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                </div>

                <div class="setting-item">
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">Newsletter Subscription</h4>
                      <p class="setting-description">Receive health tips and system updates</p>
                    </div>
                  </div>
                  <div class="setting-control">
                    <label class="toggle-switch">
                      <input type="checkbox" name="newsletter" <?php echo $current_settings['newsletter'] ? 'checked' : ''; ?>>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" name="update_notifications" class="btn btn-primary">
                  <i class="fa-solid fa-save"></i>
                  Save Notification Settings
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Privacy Tab -->
        <div class="tab-content" id="privacy">
          <div class="settings-section">
            <div class="section-header">
              <h2 class="section-title">
                <i class="fa-solid fa-shield-alt"></i>
                Privacy & Data Protection
              </h2>
              <p class="section-description">Control how your data is used and shared</p>
            </div>
            
            <form method="POST" action="" class="settings-form">
              <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
              
              <div class="setting-group">
                <div class="setting-item">
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">Data Sharing</h4>
                      <p class="setting-description">Choose how your health data may be used for research</p>
                    </div>
                  </div>
                  <div class="setting-control">
                    <select name="data_sharing" class="form-select">
                      <option value="none" <?php echo $current_settings['data_sharing'] === 'none' ? 'selected' : ''; ?>>No sharing</option>
                      <option value="minimal" <?php echo $current_settings['data_sharing'] === 'minimal' ? 'selected' : ''; ?>>Minimal (anonymous)</option>
                      <option value="research" <?php echo $current_settings['data_sharing'] === 'research' ? 'selected' : ''; ?>>Research purposes</option>
                      <option value="full" <?php echo $current_settings['data_sharing'] === 'full' ? 'selected' : ''; ?>>Full sharing</option>
                    </select>
                  </div>
                </div>

                <div class="setting-item">
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">Profile Visibility</h4>
                      <p class="setting-description">Control who can see your profile information</p>
                    </div>
                  </div>
                  <div class="setting-control">
                    <select name="profile_visibility" class="form-select">
                      <option value="private" <?php echo $current_settings['profile_visibility'] === 'private' ? 'selected' : ''; ?>>Private</option>
                      <option value="contacts" <?php echo $current_settings['profile_visibility'] === 'contacts' ? 'selected' : ''; ?>>Contacts only</option>
                      <option value="public" <?php echo $current_settings['profile_visibility'] === 'public' ? 'selected' : ''; ?>>Public</option>
                    </select>
                  </div>
                </div>

                <div class="setting-item">
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">Analytics & Tracking</h4>
                      <p class="setting-description">Allow usage analytics to improve the service</p>
                    </div>
                  </div>
                  <div class="setting-control">
                    <label class="toggle-switch">
                      <input type="checkbox" name="analytics" <?php echo $current_settings['analytics'] ? 'checked' : ''; ?>>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" name="update_privacy" class="btn btn-primary">
                  <i class="fa-solid fa-save"></i>
                  Save Privacy Settings
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Appearance Tab -->
        <div class="tab-content" id="appearance">
          <div class="settings-section">
            <div class="section-header">
              <h2 class="section-title">
                <i class="fa-solid fa-palette"></i>
                Appearance & Localization
              </h2>
              <p class="section-description">Customize the look and feel of your interface</p>
            </div>
            
            <form method="POST" action="" class="settings-form">
              <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
              
              <div class="setting-group">
                <div class="setting-item">
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">Theme Preference</h4>
                      <p class="setting-description">Choose your preferred color scheme</p>
                    </div>
                  </div>
                  <div class="setting-control">
                    <div class="radio-group">
                      <label class="radio-option">
                        <input type="radio" name="theme" value="dark" <?php echo $current_settings['theme'] === 'dark' ? 'checked' : ''; ?>>
                        <span class="radio-indicator"></span>
                        <span class="radio-label">
                          <i class="fa-solid fa-moon"></i>
                          Dark Mode
                        </span>
                      </label>
                      <label class="radio-option">
                        <input type="radio" name="theme" value="light" <?php echo $current_settings['theme'] === 'light' ? 'checked' : ''; ?>>
                        <span class="radio-indicator"></span>
                        <span class="radio-label">
                          <i class="fa-solid fa-sun"></i>
                          Light Mode
                        </span>
                      </label>
                      <label class="radio-option">
                        <input type="radio" name="theme" value="auto" <?php echo $current_settings['theme'] === 'auto' ? 'checked' : ''; ?>>
                        <span class="radio-indicator"></span>
                        <span class="radio-label">
                          <i class="fa-solid fa-circle-half-stroke"></i>
                          System Default
                        </span>
                      </label>
                    </div>
                  </div>
                </div>

                <div class="setting-item">
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">Language</h4>
                      <p class="setting-description">Select your preferred language</p>
                    </div>
                  </div>
                  <div class="setting-control">
                    <select name="language" class="form-select">
                      <option value="en" <?php echo $current_settings['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                      <option value="es" <?php echo $current_settings['language'] === 'es' ? 'selected' : ''; ?>>Español</option>
                      <option value="fr" <?php echo $current_settings['language'] === 'fr' ? 'selected' : ''; ?>>Français</option>
                      <option value="de" <?php echo $current_settings['language'] === 'de' ? 'selected' : ''; ?>>Deutsch</option>
                      <option value="zh" <?php echo $current_settings['language'] === 'zh' ? 'selected' : ''; ?>>中文</option>
                    </select>
                  </div>
                </div>

                <div class="setting-item">
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">Timezone</h4>
                      <p class="setting-description">Set your local timezone for accurate timestamps</p>
                    </div>
                  </div>
                  <div class="setting-control">
                    <select name="timezone" class="form-select">
                      <option value="UTC" <?php echo $current_settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC (Coordinated Universal Time)</option>
                      <option value="America/New_York" <?php echo $current_settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time (US)</option>
                      <option value="America/Los_Angeles" <?php echo $current_settings['timezone'] === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time (US)</option>
                      <option value="Europe/London" <?php echo $current_settings['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>London (GMT)</option>
                      <option value="Asia/Manila" <?php echo $current_settings['timezone'] === 'Asia/Manila' ? 'selected' : ''; ?>>Manila (PST)</option>
                      <option value="Asia/Tokyo" <?php echo $current_settings['timezone'] === 'Asia/Tokyo' ? 'selected' : ''; ?>>Tokyo (JST)</option>
                    </select>
                  </div>
                </div>

                <div class="setting-item">
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">Date Format</h4>
                      <p class="setting-description">Choose how dates are displayed</p>
                    </div>
                  </div>
                  <div class="setting-control">
                    <select name="date_format" class="form-select">
                      <option value="Y-m-d" <?php echo $current_settings['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>2024-12-31 (ISO)</option>
                      <option value="m/d/Y" <?php echo $current_settings['date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>12/31/2024 (US)</option>
                      <option value="d/m/Y" <?php echo $current_settings['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>31/12/2024 (EU)</option>
                      <option value="F j, Y" <?php echo $current_settings['date_format'] === 'F j, Y' ? 'selected' : ''; ?>>December 31, 2024</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" name="update_appearance" class="btn btn-primary">
                  <i class="fa-solid fa-save"></i>
                  Save Appearance Settings
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Security Tab -->
        <div class="tab-content" id="security">
          <div class="settings-section">
            <div class="section-header">
              <h2 class="section-title">
                <i class="fa-solid fa-lock"></i>
                Security & Authentication
              </h2>
              <p class="section-description">Enhance your account security and login options</p>
            </div>
            
            <form method="POST" action="" class="settings-form">
              <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
              
              <div class="setting-group">
                <div class="setting-item">
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">Two-Factor Authentication</h4>
                      <p class="setting-description">Add an extra layer of security to your account</p>
                    </div>
                  </div>
                  <div class="setting-control">
                    <label class="toggle-switch">
                      <input type="checkbox" name="two_factor" <?php echo $current_settings['two_factor'] ? 'checked' : ''; ?>>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                </div>

                <div class="setting-item">
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">Login Alerts</h4>
                      <p class="setting-description">Get notified when someone logs into your account</p>
                    </div>
                  </div>
                  <div class="setting-control">
                    <label class="toggle-switch">
                      <input type="checkbox" name="login_alerts" <?php echo $current_settings['login_alerts'] ? 'checked' : ''; ?>>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                </div>

                <div class="setting-item">
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">Session Timeout</h4>
                      <p class="setting-description">Automatically log out after period of inactivity</p>
                    </div>
                  </div>
                  <div class="setting-control">
                    <select name="session_timeout" class="form-select">
                      <option value="15" <?php echo $current_settings['session_timeout'] === 15 ? 'selected' : ''; ?>>15 minutes</option>
                      <option value="30" <?php echo $current_settings['session_timeout'] === 30 ? 'selected' : ''; ?>>30 minutes</option>
                      <option value="60" <?php echo $current_settings['session_timeout'] === 60 ? 'selected' : ''; ?>>1 hour</option>
                      <option value="240" <?php echo $current_settings['session_timeout'] === 240 ? 'selected' : ''; ?>>4 hours</option>
                      <option value="0" <?php echo $current_settings['session_timeout'] === 0 ? 'selected' : ''; ?>>Never</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" name="update_security" class="btn btn-primary">
                  <i class="fa-solid fa-save"></i>
                  Save Security Settings
                </button>
              </div>
            </form>

            <div class="security-actions">
              <h3 class="actions-title">Quick Security Actions</h3>
              <div class="action-grid">
                <a href="./profile.php" class="action-card">
                  <div class="action-icon">
                    <i class="fa-solid fa-key"></i>
                  </div>
                  <div class="action-content">
                    <h4 class="action-title">Change Password</h4>
                    <p class="action-description">Update your account password</p>
                  </div>
                  <i class="fa-solid fa-chevron-right action-arrow"></i>
                </a>

                <button type="button" class="action-card" onclick="showActiveSessionsModal()">
                  <div class="action-icon">
                    <i class="fa-solid fa-desktop"></i>
                  </div>
                  <div class="action-content">
                    <h4 class="action-title">Active Sessions</h4>
                    <p class="action-description">View and manage login sessions</p>
                  </div>
                  <i class="fa-solid fa-chevron-right action-arrow"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Data Management Tab -->
        <div class="tab-content" id="data">
          <div class="settings-section">
            <div class="section-header">
              <h2 class="section-title">
                <i class="fa-solid fa-database"></i>
                Data Management
              </h2>
              <p class="section-description">Export, backup, or delete your account data</p>
            </div>
            
            <div class="data-overview">
              <h3 class="overview-title">Your Data Overview</h3>
              <div class="data-stats">
                <div class="data-stat">
                  <div class="stat-icon">
                    <i class="fa-solid fa-file-medical"></i>
                  </div>
                  <div class="stat-content">
                    <span class="stat-value"><?php echo $user_stats['total_records']; ?></span>
                    <span class="stat-label">Health Records</span>
                  </div>
                </div>
                <div class="data-stat">
                  <div class="stat-icon">
                    <i class="fa-solid fa-calendar"></i>
                  </div>
                  <div class="stat-content">
                    <span class="stat-value">Member</span>
                    <span class="stat-label">Since 2023</span>
                  </div>
                </div>
                <div class="data-stat">
                  <div class="stat-icon">
                    <i class="fa-solid fa-shield-check"></i>
                  </div>
                  <div class="stat-content">
                    <span class="stat-value">Encrypted</span>
                    <span class="stat-label">Data Status</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="data-actions">
              <h3 class="actions-title">Data Actions</h3>
              
              <form method="POST" action="" class="data-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="action-group">
                  <button type="submit" name="export_data" class="action-card export-card">
                    <div class="action-icon">
                      <i class="fa-solid fa-download"></i>
                    </div>
                    <div class="action-content">
                      <h4 class="action-title">Export Your Data</h4>
                      <p class="action-description">Download all your health records in JSON format</p>
                    </div>
                  </button>
                </div>
              </form>

              <!-- Danger Zone -->
              <div class="danger-zone">
                <h3 class="danger-title">
                  <i class="fa-solid fa-exclamation-triangle"></i>
                  Danger Zone
                </h3>
                <p class="danger-description">These actions are permanent and cannot be undone.</p>
                
                <form method="POST" action="" class="danger-form">
                  <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                  
                  <div class="danger-action">
                    <div class="danger-content">
                      <h4 class="danger-action-title">Delete Account</h4>
                      <p class="danger-action-description">Permanently delete your account and all associated data. This action cannot be undone.</p>
                      
                      <div class="confirm-group">
                        <input type="password" name="confirm_password" placeholder="Enter your password to confirm" class="form-input danger-input" required>
                        <button type="submit" name="delete_account" class="btn btn-danger">
                          <i class="fa-solid fa-trash"></i>
                          Delete Account
                        </button>
                      </div>
                    </div>
                  </div>
                </form>
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
    // Settings page interactivity
    document.addEventListener('DOMContentLoaded', function() {
      // Tab switching functionality
      const tabBtns = document.querySelectorAll('.tab-btn');
      const tabContents = document.querySelectorAll('.tab-content');
      const loadingOverlay = document.getElementById('loadingOverlay');

      tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          const targetTab = this.dataset.tab;
          
          // Remove active class from all tabs and contents
          tabBtns.forEach(tab => tab.classList.remove('active'));
          tabContents.forEach(content => content.classList.remove('active'));
          
          // Add active class to clicked tab and corresponding content
          this.classList.add('active');
          document.getElementById(targetTab).classList.add('active');
          
          // Store active tab in localStorage
          localStorage.setItem('activeSettingsTab', targetTab);
        });
      });

      // Restore active tab from localStorage
      const savedTab = localStorage.getItem('activeSettingsTab');
      if (savedTab) {
        const savedTabBtn = document.querySelector(`[data-tab="${savedTab}"]`);
        const savedTabContent = document.getElementById(savedTab);
        
        if (savedTabBtn && savedTabContent) {
          tabBtns.forEach(tab => tab.classList.remove('active'));
          tabContents.forEach(content => content.classList.remove('active'));
          
          savedTabBtn.classList.add('active');
          savedTabContent.classList.add('active');
        }
      }

      // Form submission loading states
      const forms = document.querySelectorAll('.settings-form, .data-form, .danger-form');
      forms.forEach(form => {
        form.addEventListener('submit', function() {
          loadingOverlay.classList.add('active');
        });
      });

      // Toggle switches animation
      const toggleSwitches = document.querySelectorAll('.toggle-switch input');
      toggleSwitches.forEach(toggle => {
        toggle.addEventListener('change', function() {
          // Add visual feedback for toggle changes
          this.parentElement.classList.add('toggled');
          setTimeout(() => {
            this.parentElement.classList.remove('toggled');
          }, 200);
        });
      });

      // Auto-hide success/error messages
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        setTimeout(() => {
          alert.style.opacity = '0';
          alert.style.transform = 'translateY(-10px)';
          setTimeout(() => alert.remove(), 300);
        }, 5000);
      });

      // Enhanced form validation
      const dangerForm = document.querySelector('.danger-form');
      if (dangerForm) {
        dangerForm.addEventListener('submit', function(e) {
          const confirmPassword = this.querySelector('input[name="confirm_password"]');
          if (!confirmPassword.value) {
            e.preventDefault();
            alert('Please enter your password to confirm account deletion.');
            confirmPassword.focus();
            return;
          }
          
          const confirmed = confirm('Are you absolutely sure you want to delete your account? This action cannot be undone and will permanently delete all your data.');
          if (!confirmed) {
            e.preventDefault();
            return;
          }
        });
      }
    });

    // Mock function for active sessions modal
    function showActiveSessionsModal() {
      alert('Active Sessions feature would show:\n\n• Current session (this device)\n• Login location and time\n• Device information\n• Option to log out other sessions\n\nThis is a demo feature.');
    }
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
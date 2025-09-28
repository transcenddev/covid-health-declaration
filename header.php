<?php
// Include security functions first
include_once './includes/security.inc.php';

// Include freemium system for guest usage tracking
include_once './includes/freemium.inc.php';

// Initialize secure session (this handles all session configuration and startup)
initializeSecureSession();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateCSRFToken();
}

// Check session validity for protected pages (admin-only pages)
$current_page = basename($_SERVER['PHP_SELF']);
$protected_pages = ['dashboard_admin.php', 'update.php']; // Remove add.php to allow guest access

if (in_array($current_page, $protected_pages) && !isValidSession()) {
    // Destroy invalid session
    session_unset();
    session_destroy();
    header('Location: signin.php?error=sessionexpired');
    exit();
}

// Guest usage tracking for navigation
$is_guest = !isset($_SESSION['userId']);
$guest_usage_stats = null;
$show_guest_upgrade = false;

if ($is_guest) {
    try {
        $client_ip = get_client_ip();
        $guest_usage_stats = get_usage_stats($client_ip);
        
        if ($guest_usage_stats && 
            isset($guest_usage_stats['current_usage']) && 
            isset($guest_usage_stats['daily_limit']) &&
            $guest_usage_stats['daily_limit'] > 0) {
            
            // Show upgrade prompt if usage is at 80% or above
            $usage_percentage = ($guest_usage_stats['current_usage'] / $guest_usage_stats['daily_limit']) * 100;
            $show_guest_upgrade = $usage_percentage >= 80;
        } else {
            // Reset variables if data is incomplete
            $guest_usage_stats = null;
            $show_guest_upgrade = false;
        }
    } catch (Exception $e) {
        // Silently handle any freemium errors in navigation
        error_log("Guest usage tracking error in header: " . $e->getMessage());
        $guest_usage_stats = null;
        $show_guest_upgrade = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="This is a Admin Dashboard About Covid19 Records." />
  <meta name="keywords" content="Dashboard, Covid19, HTML, CSS, JavaScript, Web Design" />
  <meta name="author" content="Reymar" />
  <title>COVID-19 Daily Health Declaration</title>
  <!-- Favico -->
  <link rel="icon" type="image/x-icon" href='./assets/images/virus-solid.svg'>
  <!-- CSS -->
  <link rel="stylesheet" href="./styles/styles.css" />
  <link rel="stylesheet" href="./styles/header.css?v=6">
  <?php if ($current_page === 'index.php'): ?>
  <link rel="stylesheet" href="./styles/index-minimal.css?v=1">
  <?php else: ?>
  <link rel="stylesheet" href="./styles/dasboard-admin.css?v=5" />
  <link rel="stylesheet" href="./styles/index.css?v=5">
  <link rel="stylesheet" href="./styles/add.css?v=5">
  <link rel="stylesheet" href="./styles/profile.css?v=1">
  <link rel="stylesheet" href="./styles/settings.css?v=1">
  <?php endif; ?>
  <!-- <link rel="stylesheet" href="./styles/freemium.css?v=1"> TEMPORARILY DISABLED -->
  <link rel="stylesheet" href="./styles/about.css?v=5">
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <!-- Icons -->
  <script src="https://kit.fontawesome.com/444873800b.js" crossorigin="anonymous"></script>
  <script src="./scripts/main.js" defer></script>

</head>

<body>
  <nav id="navbar" class="navbar">
    <div class="nav-container">
      <!-- Logo Section -->
      <div id="nav-logo-section" class="nav-section">
        <a href="./index.php" class="nav-logo" aria-label="COVID-19 Health Declaration Home">
          <i class="fa-solid fa-virus" aria-hidden="true"></i>
          <!-- <span class="nav-brand">COVID-19 Health</span> -->
        </a>
      </div>

      <!-- Desktop Navigation Links -->
      <div id="nav-link-section" class="nav-section">
        <a href="./index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
          <i class="fa-solid fa-house" aria-hidden="true"></i>
          <span>Home</span>
        </a>
        
        <?php if (isset($_SESSION['userId'])): ?>
          <a href="./dashboard_admin.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard_admin.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
            <span>Dashboard</span>
          </a>
          <a href="./add.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'add.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            <span>New Record</span>
          </a>
          <div class="nav-user-menu">
            <button class="nav-user-btn" id="userMenuBtn" aria-expanded="false" aria-haspopup="true">
              <i class="fa-solid fa-user-circle" aria-hidden="true"></i>
              <span><?php echo htmlspecialchars($_SESSION['userUid']); ?></span>
              <i class="fa-solid fa-chevron-down nav-dropdown-icon" aria-hidden="true"></i>
            </button>
            <div class="nav-user-dropdown" id="userDropdown">
              <a href="./profile.php" class="nav-dropdown-link">
                <i class="fa-solid fa-user" aria-hidden="true"></i>
                Profile
              </a>
              <a href="./settings.php" class="nav-dropdown-link">
                <i class="fa-solid fa-cog" aria-hidden="true"></i>
                Settings
              </a>
              <hr class="nav-dropdown-divider">
              <form method="post" action="./includes/logout.inc.php" class="nav-logout-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <button type="submit" class="nav-dropdown-link logout-btn">
                  <i class="fa-solid fa-sign-out-alt" aria-hidden="true"></i>
                  Logout
                </button>
              </form>
            </div>
          </div>
        <?php else: ?>
          <!-- No usage display for guests - clean navigation -->
          
          <a href="./about.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-info-circle" aria-hidden="true"></i>
            <span>About</span>
          </a>
          
          <?php if ($show_guest_upgrade): ?>
            <a href="./signup.php" class="nav-link nav-upgrade <?php echo basename($_SERVER['PHP_SELF']) == 'signup.php' ? 'active' : ''; ?>">
              <i class="fa-solid fa-crown" aria-hidden="true"></i>
              <span>Sign Up</span>
            </a>
          <?php endif; ?>
          
          <a href="./signin.php" class="nav-link nav-cta <?php echo basename($_SERVER['PHP_SELF']) == 'signin.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-sign-in-alt" aria-hidden="true"></i>
            <span>Sign In</span>
          </a>
        <?php endif; ?>
      </div>

      <!-- Mobile Menu Toggle -->
      <div class="nav-mobile-toggle">
        <button class="nav-hamburger" id="navToggle" aria-label="Toggle navigation menu" aria-expanded="false">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
        </button>
      </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div class="nav-mobile-menu" id="mobileMenu">
      <div class="nav-mobile-content">
        <a href="./index.php" class="nav-mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
          <i class="fa-solid fa-house" aria-hidden="true"></i>
          Home
        </a>
        
        <?php if (isset($_SESSION['userId'])): ?>
          <a href="./dashboard_admin.php" class="nav-mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard_admin.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
            Dashboard
          </a>
          <a href="./add.php" class="nav-mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'add.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            New Record
          </a>
          <div class="nav-mobile-user">
            <div class="nav-mobile-user-info">
              <i class="fa-solid fa-user-circle" aria-hidden="true"></i>
              <span><?php echo htmlspecialchars($_SESSION['userUid']); ?></span>
            </div>
            <a href="./profile.php" class="nav-mobile-link">
              <i class="fa-solid fa-user" aria-hidden="true"></i>
              Profile
            </a>
            <a href="./settings.php" class="nav-mobile-link">
              <i class="fa-solid fa-cog" aria-hidden="true"></i>
              Settings
            </a>
            <form method="post" action="./includes/logout.inc.php" class="nav-mobile-logout">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
              <button type="submit" class="nav-mobile-link logout-btn">
                <i class="fa-solid fa-sign-out-alt" aria-hidden="true"></i>
                Logout
              </button>
            </form>
          </div>
        <?php else: ?>
          <!-- Clean mobile navigation for guests -->
          
          <a href="./about.php" class="nav-mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-info-circle" aria-hidden="true"></i>
            About
          </a>
          
          <a href="./signin.php" class="nav-mobile-link nav-cta <?php echo basename($_SERVER['PHP_SELF']) == 'signin.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-sign-in-alt" aria-hidden="true"></i>
            Sign In
          </a>
        <?php endif; ?>
      </div>
    </div>
  </nav>
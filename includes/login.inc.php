<?php

if (isset($_POST['login-submit'])) {
  require './dbconn.inc.php';
  require './security.inc.php';

  // Initialize secure session
  initializeSecureSession();

  // Validate CSRF token
  if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    logSecurityEvent('CSRF token validation failed in login.inc.php');
    header("location: ../signin.php?error=csrftoken");
    exit();
  }

  // Rate limiting check
  if (!checkRateLimit('login', 5, 900)) { // 5 attempts per 15 minutes
    logSecurityEvent('Rate limit exceeded for login attempts');
    header("location: ../signin.php?error=ratelimit");
    exit();
  }

  $mailuid = sanitizeInput($_POST['mailuid']);
  $password = $_POST['pwd']; // Don't sanitize password

  if (empty($mailuid) || empty($password)) {
    header("location: ../signin.php?error=emptyfields");
    exit();
  } else {
    $sql = "SELECT * FROM users WHERE uid_users=? OR email_users=?;";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
      logSecurityEvent('Prepared statement failed in login.inc.php: ' . mysqli_error($conn));
      header("location: ../signin.php?error=sqlerror");
      exit();
    } else {

      mysqli_stmt_bind_param($stmt, "ss", $mailuid, $mailuid);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      if ($row = mysqli_fetch_assoc($result)) {
        $pwdCheck = password_verify($password, $row['pwd_users']);
        if ($pwdCheck == false) {
          logSecurityEvent('Failed login attempt for user: ' . $mailuid);
          header("Location: ../signin.php?error=wrongpwd");
          exit();
        } else if ($pwdCheck == true) {
          // Regenerate session ID after successful login
          session_regenerate_id(true);
          
          // Regenerate CSRF token
          regenerateCSRFToken();
          
          $_SESSION['userId'] = $row['id_users'];
          $_SESSION['userUid'] = $row['uid_users'];
          $_SESSION['last_activity'] = time();
          
          logSecurityEvent('Successful login for user: ' . $mailuid, 'INFO');
          header("Location: ../dashboard_admin.php");
          exit();
        } else {
          logSecurityEvent('Password verification error for user: ' . $mailuid);
          header("Location: ../signin.php?error=wrongpwd");
          exit();
        }
      } else {
        logSecurityEvent('Login attempt for non-existent user: ' . $mailuid);
        header("Location: ../signin.php?error=nousers");
        exit();
      }
    }
  }
} else {
  header("location: ../signin.php");
  exit();
}

<?php
// Include the database connection
include './includes/dbconn.inc.php';
include './includes/security.inc.php';

// Initialize secure session
initializeSecureSession();

// Check if user is logged in
if (!isValidSession()) {
    header('Location: signin.php');
    exit();
}

// Check if the request is POST (for CSRF protection)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logSecurityEvent('Invalid request method in delete.php: ' . $_SERVER['REQUEST_METHOD']);
    header('Location: dashboard_admin.php');
    exit();
}

// Check for CSRF protection
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    logSecurityEvent('CSRF token validation failed in delete.php');
    die('CSRF token validation failed');
}

// Check if the user ID is provided and validate
if (isset($_POST['id'])) {
  $user_id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
  
  if ($user_id === false || $user_id <= 0) {
    logSecurityEvent('Invalid user ID provided in delete.php: ' . $_POST['id']);
    header('Location: dashboard_admin.php');
    exit();
  }

  // Check if record exists before deletion
  $check_sql = "SELECT id FROM records WHERE id = ?";
  $check_stmt = mysqli_prepare($conn, $check_sql);
  
  if (!$check_stmt) {
    logSecurityEvent('Prepared statement failed in delete.php: ' . mysqli_error($conn));
    die('Database preparation error');
  }

  mysqli_stmt_bind_param($check_stmt, "i", $user_id);
  mysqli_stmt_execute($check_stmt);
  $check_result = mysqli_stmt_get_result($check_stmt);

  if (mysqli_num_rows($check_result) === 0) {
    mysqli_stmt_close($check_stmt);
    logSecurityEvent('Attempt to delete non-existent record ID: ' . $user_id);
    header('Location: dashboard_admin.php');
    exit();
  }

  mysqli_stmt_close($check_stmt);

  // Delete the user from the database using prepared statement
  $delete_sql = "DELETE FROM records WHERE id = ?";
  $delete_stmt = mysqli_prepare($conn, $delete_sql);

  if ($delete_stmt) {
    mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
      mysqli_stmt_close($delete_stmt);
      logSecurityEvent('Record deleted successfully. ID: ' . $user_id, 'INFO');
      header('Location: dashboard_admin.php');
      exit();
    } else {
      mysqli_stmt_close($delete_stmt);
      logSecurityEvent('Database error in delete.php: ' . mysqli_error($conn));
      die('Error deleting record');
    }
  } else {
    logSecurityEvent('Prepared statement failed in delete.php: ' . mysqli_error($conn));
    die('Database preparation error');
  }
} else {
  logSecurityEvent('User ID not provided in delete.php');
  header('Location: dashboard_admin.php');
  exit();
}

<?php
// Include the database connection
include './includes/dbconn.inc.php';

// Check if the user ID is provided in the URL
if (isset($_GET['id'])) {
  $user_id = $_GET['id'];

  // Delete the user from the database
  $delete_query = "DELETE FROM records WHERE id = '$user_id'";
  $delete_result = mysqli_query($conn, $delete_query);

  if ($delete_result) {
    header('Location: dashboard_admin.php'); // Redirect back to the admin dashboard after deletion
  } else {
    echo 'Error deleting user: ' . mysqli_error($conn);
  }
} else {
  echo 'User ID not provided.';
}

<?php

if (isset($_POST['login-submit'])) {
  require './dbconn.inc.php';

  $mailuid = $_POST['mailuid'];
  $password = $_POST['pwd'];

  if (empty($mailuid) || empty($password)) {
    header("location: ../index.php?error=emptyfields");
    exit();
  } else {
    $sql = "SELECT * FROM users WHERE uid_users=? OR email_users=?;";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
      header("location: ../index.php?error=sqlerror");
      exit();
    } else {

      mysqli_stmt_bind_param($stmt, "ss", $mailuid, $mailuid);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      if ($row = mysqli_fetch_assoc($result)) {
        $pwdCheck = password_verify($password, $row['pwd_users']);
        if ($pwdCheck == false) {
          header("Location: ../index.php?error=wrongpwd");
          exit();
        } else if ($pwdCheck == true) {
          session_start();
          $_SESSION['userId'] = $row['id_users'];
          $_SESSION['userUid'] = $row['uid_users'];
          header("Location: ../dashboard_admin.php");
          // header("Location: ../index.php?login=success");
          exit();
        } else {
          header("Location: ../index.php?error=wrongpwd");
          exit();
        }
      } else {
        header("Location: ../index.php?error=nousers");
        exit();
      }
    }
  }
} else {
  header("location: ../index.php");
  exit();
}

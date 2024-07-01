<?php

if (isset($_POST['signup-submit'])) {

  require './dbconn.inc.php';

  $username = $_POST['username'];
  $email = $_POST['email'];
  $password = $_POST['password'];
  $password_repeat = $_POST['password_repeat'];

  if (empty($username) || empty($email) || empty($password) || empty($password_repeat)) {
    header("location: ../signup.php?error=emptyfields&uid=" . $username . "&mail" . $email);
    exit();
  } else if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !preg_match("/^[a-zA-Z0-9]*$/", $username)) {
    header("location: ../signup.php?error=invalidmail&uid");
    exit();
  } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("location: ../signup.php?error=invalidmail&uid=" . $username);
    exit();
  } else if (!preg_match("/^[a-zA-Z0-9]*$/", $username)) {
    header("location: ../signup.php?error=invaliduid&mail=" . $email);
    exit();
  } else if ($password !== $password_repeat) {
    header("location: ../signup.php?error=passwordcheck&uid=" . $username . "&mail" . $email);
  } else {

    $sql = "SELECT uid_users FROM users WHERE uid_users=?";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
      header("location: ../signup.php?error=sqlerror");
      exit();
    } else {
      mysqli_stmt_bind_param($stmt, "s", $username);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_store_result($stmt);
      $resultCheck = mysqli_stmt_num_rows($stmt);
      if ($resultCheck > 0) {
        header("location: ../signup.php?error=usertaken&mail=" . $email);
        exit();
      } else {

        $sql = "INSERT INTO users (uid_users, email_users, pwd_users) VALUES (?, ?, ?)";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt, $sql)) {
          header("location: ../signup.php?error=sqlerror");
          exit();
        } else {
          $hashedPwd = password_hash($password, PASSWORD_BCRYPT);

          mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashedPwd);
          mysqli_stmt_execute($stmt);
          header("location: ../signup.php?signup=success");
          exit();
        }
      }
    }
  }

  mysqli_stmt_close($stmt);
  mysqli_close($conn);
} else {
  header("location: ../signup.php");
  exit();
}

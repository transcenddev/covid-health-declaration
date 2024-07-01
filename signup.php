<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register</title>
  <link rel="stylesheet" href="./styles/signup.css" />
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <!-- Icons -->
  <script src="https://kit.fontawesome.com/444873800b.js" crossorigin="anonymous"></script>
  <!-- Favico -->
  <link rel="icon" type="image/x-icon" href='./assets/images/virus-solid.svg'>
</head>

<body>
  <main id="signin">
    <div class="logo">
      <i class="fa-solid fa-virus"></i>
    </div>
    <?php
    if (isset($_GET['error'])) {
      if ($_GET['error'] == "emptyfields") {
        echo '<p class="signup-error">Fill all the fields!</p>';
      } else if ($_GET['error'] == "invaliduidemail") {
        echo '<p class="signup-error">Invalid username and email!</p>';
      } else if ($_GET['error'] == "invaliduid") {
        echo '<p class="signup-error">Invalid username!</p>';
      } else if ($_GET['error'] == "invalidmail") {
        echo '<p class="signup-error">Invalid email!</p>';
      } else if ($_GET['error'] == "passwordCheck") {
        echo '<p class="signup-error">Your passwords do not match!</p>';
      } else if ($_GET['error'] == "usertaken") {
        echo '<p class="signup-error">Username is already taken!</p>';
      }
    } else if (isset($_GET['signup']) && $_GET['signup'] == "success") {
      echo '<p class="signup-success">Signup successful!</p>';
    }

    ?>
    <form action="./includes/signup.inc.php" method="post">
      <div>
        <label for="username">Username</label>
        <input type="text" class="text-input" name="username" />
      </div>
      <div>
        <label for="email">Email</label>
        <input type="text" class="text-input" name="email" />
      </div>
      <div>
        <label for="password">Password</label>
        <input type="password" class="text-input" name="password" />
      </div>
      <div>
        <label for="password_repeat">Confirm Password</label>
        <input type="password" class="text-input" name="password_repeat" />
      </div>
      <button type="submit" class="primary-btn" name="signup-submit">
        Create an Account
      </button>
      <div class="links">
        <a href="./signin.php">Already have an account?</a>
      </div>
    </form>
  </main>
</body>

</html>
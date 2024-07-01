<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./styles/signin.css" />
  <title>Sign in</title>
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
  <main>
    <div id="wrapper">
      <div id="left">
        <div id="signin">
          <div class="logo">
            <i class="fa-solid fa-virus"></i>
            <!-- <h2>Login</h2> -->
          </div>
          <form action="./includes/login.inc.php" method="post">
            <div>
              <label>Username or Email</label>
              <input type="text" class="text-input" name="mailuid" />
            </div>
            <div>
              <label>Password</label>
              <input type="password" class="text-input" name="pwd" />
            </div>
            <button type="submit" class="primary-btn" name="login-submit">
              Sign in</button>
          </form>
          <div class="links">
            <a href="#">Forgot Password?</a>
            <a href="#">Sign in with company or school</a>
          </div>
          <div class="or">
            <hr class="bar" />
            <span>OR</span>
            <hr class="bar" />
          </div>
          <a href="./signup.php" class="secondary-btn">Create an account</a>
        </div>
        <footer id="main-footer">
          <p>Copyright &copy; 2023 Reymar, All Rights Reserved</p>
          <div>
            <a href="#">Terms of Use</a> | <a href="#">Privacy Policy</a>
          </div>
        </footer>
      </div>
      <div id="right">
        <div id="showcase">
          <div class="showcase-content">
            <h1 class="showcase-text">
              <strong>COVID19 Records.</strong>
            </h1>
            <!-- <a href="#" class="secondary-btn">learn more</a> -->
          </div>
        </div>
      </div>
    </div>
  </main>
</body>

</html>
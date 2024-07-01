<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="This is a Admin Dashboard About Covid19 Records." />
  <meta name="keywords" content="Dashboard, Covid19, HTML, CSS, JavaScript, Web Design" />
  <meta name="author" content="Reymar" />
  <title>Kornbip19</title>
  <!-- Favico -->
  <link rel="icon" type="image/x-icon" href='./assets/images/virus-solid.svg'>
  <!-- CSS -->
  <link rel="stylesheet" href="./styles/styles.css" />
  <link rel="stylesheet" href="./styles/header.css">
  <link rel="stylesheet" href="./styles/dasboard-admin.css" />
  <link rel="stylesheet" href="./styles/index.css">
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
    <div id="nav-logo-section" class="nav-section">
      <a href="#"><i class="fa-solid fa-virus"></i></a>
    </div>
    <div id="nav-link-section" class="nav-section">
      <a href="./index.php">HOME</a>
      <a href="https://r3kashi.github.io/portfolio/">ABOUT</a>
      <?php
      if (isset($_SESSION['userId'])) {
        echo '<a href="./includes/logout.inc.php">LOGOUT</a>';
      } else {
        echo '<a href="./signin.php">LOGIN</a>';
      }

      ?>

    </div>
    <!-- <div id="nav-social-section" class="nav-section">
      <a href="https://www.youtube.com/channel/UChXTp71pjigJLbScNF9YO1A"><i class="fa-brands fa-youtube"></i></a>
      <a href="#"><i class="fa-brands fa-twitter"></i></a>
      <a href="https://www.linkedin.com/in/reymar-mirante-44b2b41b9/"><i class="fa-brands fa-linkedin-in"></i></a>
      <a href="https://github.com/r3kashi"><i class="fa-brands fa-github"></i></a>
    </div> -->
  </nav>
<?php

// Include the database connection
include './includes/dbconn.inc.php';

// Process form submission
if (isset($_POST['submit'])) {
  // Retrieve form data
  $email = $_POST['email'];
  $full_name = $_POST['full_name'];
  $gender = $_POST['gender'];
  $age = $_POST['age'];
  $temp = $_POST['temp'];
  $diagnosed = $_POST['diagnosed'];
  $encounter = $_POST['encounter'];
  $vaccinated = $_POST['vaccinated'];
  $nationality = $_POST['nationality'];

  // Insert data into the table
  $sql = "INSERT INTO records (email, full_name, gender, age, temp, diagnosed, encountered, vaccinated, nationality)
  VALUES ('$email', '$full_name', '$gender', $age, $temp, '$diagnosed', '$encounter', '$vaccinated', '$nationality')";
  $result = mysqli_query($conn, $sql);

  if ($result) {
    // echo '<div class="alert alert-success" role="alert"> Successfully Added. </div>';
    header('location: dashboard_admin.php');
  } else {
    die(mysqli_error($conn));
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
  <link rel="stylesheet" href="./styles/add.css">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous" defer></script>
  <!-- Icon -->
  <script src="https://kit.fontawesome.com/444873800b.js" crossorigin="anonymous"></script>
  <!-- Favico -->
  <link rel="icon" type="image/x-icon" href='./assets/images/virus-solid.svg'>
</head>

<body>
  <main class="wrapper-form">
    <h2 class="add-title"><i class="fa-regular fa-address-book"></i>Add</h2>
    <form method="post" action="">
      <div class="mb-3">
        <label for="InputEmail" class="form-label">Email address</label>
        <input type="email" class="form-control" id="InputEmail" name="email" aria-describedby="emailHelp">
        <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
      </div>
      <div class="mb-3">
        <label for="InputFullName" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="InputFullName" name="full_name">
      </div>
      <div class="mb-3">
        <label for="InputGender" class="form-label">Gender</label>
        <select class="form-select" id="InputGender" name="gender">
          <option value="Male">Male</option>
          <option value="Female">Female</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="InputAge" class="form-label">Age</label>
        <input type="number" class="form-control" id="InputAge" name="age">
      </div>
      <div class="mb-3">
        <label for="InputTemp" class="form-label">Body Temperature (°C)</label>
        <input type="number" step="0.01" class="form-control" id="InputTemp" name="temp">
      </div>
      <div class="mb-3">
        <label class="form-label">COVID-19 Diagnosed</label>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="diagnosed" id="DiagnosedYes" value="Yes">
          <label class="form-check-label" for="DiagnosedYes">
            Yes
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="diagnosed" id="DiagnosedNo" value="No">
          <label class="form-check-label" for="DiagnosedNo">
            No
          </label>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">COVID-19 Encounter</label>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="encounter" id="EncounterYes" value="Yes">
          <label class="form-check-label" for="EncounterYes">
            Yes
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="encounter" id="EncounterNo" value="No">
          <label class="form-check-label" for="EncounterNo">
            No
          </label>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Vaccinated</label>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="vaccinated" id="VaccinatedYes" value="Yes">
          <label class="form-check-label" for="VaccinatedYes">
            Yes
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="vaccinated" id="VaccinatedNo" value="No">
          <label class="form-check-label" for="VaccinatedNo">
            No
          </label>
        </div>
      </div>
      <div class="mb-3">
        <label for="InputNationality" class="form-label">Nationality</label>
        <input type="text" class="form-control" id="InputNationality" name="nationality">
      </div>
      <button type="submit" class="btn btn-primary" name="submit">Submit</button>
    </form>
  </main>

</body>

</html>
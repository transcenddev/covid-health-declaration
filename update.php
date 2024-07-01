<?php
// Include the database connection
include './includes/dbconn.inc.php';

// Get user ID from the URL
$id = $_GET['id'];

$sql = "SELECT * FROM records WHERE id=$id";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$email = $row['email'];
$full_name = $row['full_name'];
$gender = $row['gender'];
$age = $row['age'];
$temp = $row['temp'];
$diagnosed = $row['diagnosed'];
$encounter = $row['encountered'];
$vaccinated = $row['vaccinated'];
$nationality = $row['nationality'];

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

  // Update data in the table
  $sql = "UPDATE records SET email='$email', full_name='$full_name', gender='$gender', age='$age', temp='$temp', diagnosed='$diagnosed', encountered='$encounter', vaccinated='$vaccinated', nationality='$nationality' WHERE id='$id'";
  $result = mysqli_query($conn, $sql);

  if ($result) {
    header('Location: dashboard_admin.php'); // Redirect back to the admin dashboard after update
  } else {
    die(mysqli_error($conn));
  }
}

// Fetch existing user data
$fetch_query = "SELECT * FROM records WHERE id = '$id'";
$fetch_result = mysqli_query($conn, $fetch_query);

// Check if user exists
if ($fetch_result && mysqli_num_rows($fetch_result) > 0) {
  $user_data = mysqli_fetch_assoc($fetch_result);
  $email = $user_data['email'];
  $full_name = $user_data['full_name'];
  $gender = $user_data['gender'];
  $age = $user_data['age'];
  $temp = $user_data['temp'];
  $diagnosed = $user_data['diagnosed'];
  $encounter = $user_data['encountered'];
  $vaccinated = $user_data['vaccinated'];
  $nationality = $user_data['nationality'];
} else {
  echo 'User not found.';
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
  <link rel="stylesheet" href="./styles/update.css">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous" defer></script>
  <!-- Icon -->
  <script src="https://kit.fontawesome.com/444873800b.js" crossorigin="anonymous"></script>
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <!-- Favico -->
  <link rel="icon" type="image/x-icon" href='./assets/images/virus-solid.svg'>
</head>

<body>

  <main class="wrapper-form">
    <h2 class="update-title"><i class="fa-regular fa-pen-to-square"></i>Update</h2>
    <form method="post" action="">
      <div class="mb-3">
        <label for="InputEmail" class="form-label">Email address</label>
        <input type="email" class="form-control" id="InputEmail" name="email" aria-describedby="emailHelp" value="<?php echo $email ?>">
        <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
      </div>
      <div class="mb-3">
        <label for="InputFullName" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="InputFullName" name="full_name" value="<?php echo $full_name ?>">
      </div>
      <div class="mb-3">
        <label for="InputGender" class="form-label">Gender</label>
        <select class="form-select" id="InputGender" name="gender">
          <option value="Male" <?php if ($gender === 'Male') echo 'selected' ?>>Male</option>
          <option value="Female" <?php if ($gender === 'Female') echo 'selected' ?>>Female</option>
          <option value="Other" <?php if ($gender === 'Other') echo 'selected' ?>>Other</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="InputAge" class="form-label">Age</label>
        <input type="number" class="form-control" id="InputAge" name="age" value="<?php echo $age ?>">
      </div>
      <div class="mb-3">
        <label for="InputTemp" class="form-label">Body Temperature (°C)</label>
        <input type="number" step="0.01" class="form-control" id="InputTemp" name="temp" value="<?php echo $temp ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">COVID-19 Diagnosed</label>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="diagnosed" id="DiagnosedYes" value="Yes" <?php if ($diagnosed === 'YES') echo 'checked' ?>>
          <label class="form-check-label" for="DiagnosedYes">
            Yes
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="diagnosed" id="DiagnosedNo" value="No" <?php if ($diagnosed === 'NO') echo 'checked' ?>>
          <label class="form-check-label" for="DiagnosedNo">
            No
          </label>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">COVID-19 Encounter</label>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="encounter" id="EncounterYes" value="Yes" <?php if ($encounter === 'YES') echo 'checked' ?>>
          <label class="form-check-label" for="EncounterYes">
            Yes
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="encounter" id="EncounterNo" value="No" <?php if ($encounter === 'NO') echo 'checked' ?>>
          <label class="form-check-label" for="EncounterNo">
            No
          </label>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Vaccinated</label>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="vaccinated" id="VaccinatedYes" value="Yes" <?php if ($vaccinated === 'YES') echo 'checked' ?>>
          <label class="form-check-label" for="VaccinatedYes">
            Yes
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="vaccinated" id="VaccinatedNo" value="No" <?php if ($vaccinated === 'NO') echo 'checked' ?>>
          <label class="form-check-label" for="VaccinatedNo">
            No
          </label>
        </div>
      </div>
      <div class="mb-3">
        <label for="InputNationality" class="form-label">Nationality</label>
        <input type="text" class="form-control" id="InputNationality" name="nationality" value="<?php echo $nationality ?>">
      </div>
      <button type="submit" class="btn btn-primary" name="submit">Submit</button>
    </form>
  </main>

</body>

</html>
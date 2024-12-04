<?php include('config.php'); ?>
<?php include('includes/header.php'); ?>

<main>
    <section class="form-section">
        <h1>Register</h1>
        <p>Create your account to join COSN.</p>
        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <label for="reference_email">Reference Senior Email:</label>
                <input type="email" id="reference_email" name="reference_email" required>
            </div>
            <button type="submit" name="register">Register</button>
        </form>
    </section>
</main>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $reference_email = $conn->real_escape_string($_POST['reference_email']);

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "<p style='color: red; text-align: center;'>Passwords do not match!</p>";
    } else {
        // Check if the reference email belongs to an active senior member
        $reference_query = "SELECT * FROM Members WHERE Email = '$reference_email' AND Privilege = 'Senior' AND Status = 'Active'";
        $reference_result = $conn->query($reference_query);

        if ($reference_result->num_rows === 0) {
            echo "<p style='color: red; text-align: center;'>The reference email is not valid or does not belong to an active Senior member.</p>";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert the new user into the database
            $query = "INSERT INTO Members (Username, Password, Email, FirstName, LastName, Privilege, Status, CreationDate) 
                      VALUES ('$username', '$hashed_password', '$email', '$first_name', '$last_name', 'Junior', 'Active', NOW())";

            if ($conn->query($query)) {
                echo "<p style='color: green; text-align: center;'>Registration successful! <a href='login.php'>Login here</a>.</p>";
            } else {
                echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
            }
        }
    }
}
?>

<?php include('includes/footer.php'); ?>

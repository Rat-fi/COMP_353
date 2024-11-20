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

    if ($password !== $confirm_password) {
        echo "<p style='color: red;'>Passwords do not match!</p>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $query = "INSERT INTO Members (Username, Password, Email, FirstName, LastName, Privilege, Status, CreationDate) 
                  VALUES ('$username', '$hashed_password', '$email', '$first_name', '$last_name', 'Junior', 'Active', NOW())";

        if ($conn->query($query)) {
            echo "<p style='color: green;'>Registration successful! <a href='login.php'>Login here</a>.</p>";
        } else {
            echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
        }
    }
}
?>

<?php include('includes/footer.php'); ?>

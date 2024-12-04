<?php
session_start(); // Start session before output
include('config.php');

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_message = ""; // Initialize error message

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM Members WHERE Username = '$username'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check if user is inactive or suspended BEFORE verifying the password
        if ($user['Status'] === 'Inactive') {
            $error_message = "Your account is inactive. Please contact support.";
        } elseif ($user['Status'] === 'Suspended') {
            $error_message = "Your account is suspended. Please contact admin.";
        } else {
            // Only verify the password if the account is active
            if (password_verify($password, $user['Password'])) {
                $_SESSION['user_id'] = $user['MemberID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['privilege'] = $user['Privilege'];

                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Invalid password.";
            }
        }
    } else {
        $error_message = "No account found with that username.";
    }
}
?>

<?php include('includes/header.php'); ?>

<main>
    <section class="form-section">
        <h1>Login</h1>
        <p>Enter your credentials to access COSN.</p>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login">Login</button>
        </form>

        <!-- Display error message -->
        <?php if (!empty($error_message)): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>
    </section>
</main>

<?php include('includes/footer.php'); ?>

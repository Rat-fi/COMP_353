<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('config.php');

// Fetch current user information
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM Members WHERE MemberID = $user_id";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "<p>User not found.</p>";
    exit();
}

// Update profile on form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $address = $conn->real_escape_string($_POST['address']);
    $internal_pseudonym = $conn->real_escape_string($_POST['internal_pseudonym']);
    $date_of_birth = $conn->real_escape_string($_POST['date_of_birth']);

    // Update user profile
    $update_query = "UPDATE Members SET 
            Username = '$username', 
            Email = '$email', 
            FirstName = '$first_name', 
            LastName = '$last_name', 
            Address = '$address', 
            InternalPseudonym = '$internal_pseudonym', 
            DateOfBirth = '$date_of_birth'
            WHERE MemberID = $user_id";

    if ($conn->query($update_query)) {
        $success_message = "Profile updated successfully.";
        // Refresh user data
        $result = $conn->query($query);
        $user = $result->fetch_assoc();
    } else {
        $error_message = "Error updating profile: " . $conn->error;
    }
}

include('includes/header.php');
?>

<main>
    <section class="edit-profile">
        <div class="back-button-container">
            <a href="dashboard.php" class="link-button"><arrow></arrow> Back</a>
        </div>
        <h1>Edit Your Profile</h1>

        <?php if (isset($success_message)): ?>
            <p class="success"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <!-- Read-only Information -->
        <div class="read-only-info">
            <div class="info-item">
                <strong>Member ID:</strong>
                <br>
                <?php echo htmlspecialchars($user['MemberID']); ?>
            </div>
            <div class="info-item">
                <strong>Status:</strong>
                <br>
                <?php echo htmlspecialchars($user['Status']); ?>
            </div>
            <div class="info-item">
                <strong>Privilege:</strong>
                <br>
                <?php echo htmlspecialchars($user['Privilege']); ?>
            </div>
            <div class="info-item">
                <strong>Last Login:</strong>
                <br>
                <?php echo htmlspecialchars($user['LastLogin'] ?? 'Never Logged In'); ?>
            </div>
            <div class="info-item">
                <strong>Warnings Count:</strong>
                <br>
                <?php echo htmlspecialchars($user['WarningsCount']); ?>
            </div>
        </div>

        <!-- Editable Profile Form -->
        <form action="edit_profile.php" method="POST" class="grid-form">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['Username'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['Email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['FirstName'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['LastName'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="date_of_birth">Date of Birth:</label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user['DateOfBirth'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="internal_pseudonym">Internal Pseudonym:</label>
                <input type="text" id="internal_pseudonym" name="internal_pseudonym" value="<?php echo htmlspecialchars($user['InternalPseudonym'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea id="address" name="address"><?php echo htmlspecialchars($user['Address'] ?? ''); ?></textarea>
            </div>
            <button type="submit" name="update_profile" class="button full-width">Update Profile</button>
        </form>
    </section>
</main>

<?php include('includes/footer.php'); ?>
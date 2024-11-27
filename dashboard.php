<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('config.php');
include('includes/header.php');

// Fetch current user data
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM Members WHERE MemberID = $user_id";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Set FirstName and LastName in the session if not already set
    if (!isset($_SESSION['FirstName'])) {
        $_SESSION['FirstName'] = $user['FirstName'];
    }
    if (!isset($_SESSION['LastName'])) {
        $_SESSION['LastName'] = $user['LastName'];
    }
} else {
    echo "<p>User not found.</p>";
    exit();
}

// Session timeout logic
if (!isset($_SESSION['LAST_ACTIVITY'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
} elseif (time() - $_SESSION['LAST_ACTIVITY'] > 1800) { // 30 minutes
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();
?>

<main>
    <section class="dashboard">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr;"> 
            <div></div>
            <h1 style="justify-self: center;">
                Welcome,
                <?php echo htmlspecialchars($_SESSION['FirstName'] ?? 'User'); ?>
                <?php echo htmlspecialchars($_SESSION['LastName'] ?? ''); ?>!
            </h1>
            <div style="justify-self: flex-end;">
                <a href="edit_profile.php" class="link-button">Edit Profile</a>
                <p>Your privilege level: <strong><?php echo htmlspecialchars($_SESSION['privilege']); ?></strong></p>
            </div>
        </div>

        <section style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
            <section style="padding: 0.5rem; background: #f4f4f4; border: 1px solid #ddd; border-radius: 5px; text-align: center;">
                <p>Latest Messages</p>
                <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">

                </div>
            </section>
            <section style="padding: 0.5rem; background: #f4f4f4; border: 1px solid #ddd; border-radius: 5px; text-align: center;">
                <p>Latest Posts</p>
                <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">

                </div>
            </section>
            <section style="padding: 0.5rem; background: #f4f4f4; border: 1px solid #ddd; border-radius: 5px; text-align: center;">
                <p>Latest Messages</p>
                <div style="display: grid; grid-template-columns: 1fr; gap: 1rem; min-height: 200px;">

                </div>
                <p>Latest Friend Requests</p>
                <div style="display: grid; grid-template-columns: 1fr; gap: 1rem; min-height: 200px;">

                </div>
            </section>

        </section>
    </section>
</main>

<?php include('includes/footer.php'); ?>
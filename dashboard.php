<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('config.php');
include('includes/header.php');

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM Members WHERE MemberID = $user_id";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "<p>User not found.</p>";
    exit();
}

if (!isset($_SESSION['LAST_ACTIVITY'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
} elseif (time() - $_SESSION['LAST_ACTIVITY'] > 1800) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();
?>

<main>
    <section class="dashboard">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>Your privilege level: <strong><?php echo htmlspecialchars($_SESSION['privilege']); ?></strong></p>

        <h2>Your Profile</h2>
        <ul>
            <li><strong>Email:</strong> <?php echo htmlspecialchars($user['Email']); ?></li>
            <li><strong>Status:</strong> <?php echo htmlspecialchars($user['Status']); ?></li>
            <li><strong>Joined on:</strong> <?php echo htmlspecialchars($user['CreationDate']); ?></li>
        </ul>

        <h2>Your Actions</h2>
        <a href="edit_profile.php" class="btn">Edit Profile</a>
        <a href="logout.php" class="btn">Logout</a>
    </section>
</main>

<?php include('includes/footer.php'); ?>

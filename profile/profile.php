<?php
session_start();
include('../config.php');

// Get the MemberID from the URL
if (!isset($_GET['member_id']) || !is_numeric($_GET['member_id'])) {
    echo "Invalid Member ID.";
    exit();
}

$member_id = intval($_GET['member_id']);

// Fetch user information
$query = "SELECT * FROM Members WHERE MemberID = $member_id";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "<p>Profile not found.</p>";
    exit();
}

include('../includes/header.php');
?>

<main>
    <section class="profile">
        <div class="back-button-container" style="margin-bottom: 1rem;">
            <a href="../dashboard.php" class="link-button" style="padding: 0.5rem 1rem; text-decoration: none; background-color: #212e54; color: white; border-radius: 5px;">
                &larr; Back to Dashboard
            </a>
        </div>
        <h1 style="text-align: center; margin-bottom: 2rem;">
            <?php echo htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']); ?>'s Profile
        </h1>

        <!-- Profile Information Section -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; background: #f9f9f9; padding: 1rem; border: 1px solid #ddd; border-radius: 5px;">
            <div class="info-item">
                <strong>Member ID:</strong>
                <p><?php echo htmlspecialchars($user['MemberID']); ?></p>
            </div>
            <div class="info-item">
                <strong>Username:</strong>
                <p><?php echo htmlspecialchars($user['Username']); ?></p>
            </div>
            <div class="info-item">
                <strong>Email:</strong>
                <p><?php echo htmlspecialchars($user['Email']); ?></p>
            </div>
            <div class="info-item">
                <strong>Date of Birth:</strong>
                <p><?php echo htmlspecialchars($user['DateOfBirth'] ?? 'Not Provided'); ?></p>
            </div>
            <div class="info-item">
                <strong>Internal Pseudonym:</strong>
                <p><?php echo htmlspecialchars($user['InternalPseudonym'] ?? 'Not Provided'); ?></p>
            </div>
            <div class="info-item">
                <strong>Address:</strong>
                <p><?php echo nl2br(htmlspecialchars($user['Address'] ?? 'Not Provided')); ?></p>
            </div>
            <div class="info-item">
                <strong>Status:</strong>
                <p><?php echo htmlspecialchars($user['Status']); ?></p>
            </div>
            <div class="info-item">
                <strong>Privilege:</strong>
                <p><?php echo htmlspecialchars($user['Privilege']); ?></p>
            </div>
            <div class="info-item">
                <strong>Last Login:</strong>
                <p><?php echo htmlspecialchars($user['LastLogin'] ?? 'Never Logged In'); ?></p>
            </div>
            <div class="info-item">
                <strong>Warnings Count:</strong>
                <p><?php echo htmlspecialchars($user['WarningsCount']); ?></p>
            </div>
            <div class="info-item">
                <strong>Profile Created:</strong>
                <p><?php echo htmlspecialchars($user['CreationDate']); ?></p>
            </div>
        </div>
    </section>
</main>

<?php include('../includes/footer.php'); ?>

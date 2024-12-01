<?php
session_start();
include('config.php');

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle the connect request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connect_member_id'])) {
    $member2_id = (int)$_POST['connect_member_id'];

    // Insert the new connection
    $stmt = $conn->prepare("INSERT INTO Connections (MemberID1, MemberID2, Status, Relation) VALUES (?, ?, 'Requested', 'Friend')");
    $stmt->bind_param("ii", $user_id, $member2_id);
    if ($stmt->execute()) {
        echo "<p style='color:green;'>Connection request sent successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error sending connection request. Please try again.</p>";
    }
    $stmt->close();
}

// Fetch non-connected members
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = $search_query ? "AND (FirstName LIKE '%$search_query%' OR LastName LIKE '%$search_query%' OR Username LIKE '%$search_query%')" : '';

$query = "
    SELECT MemberID, FirstName, LastName, Username
    FROM Members
    WHERE MemberID != $user_id
    AND MemberID NOT IN (
        SELECT MemberID2 FROM Connections WHERE MemberID1 = $user_id
        UNION
        SELECT MemberID1 FROM Connections WHERE MemberID2 = $user_id
    )
    $search_condition
";
$result = $conn->query($query);
?>

<?php include('includes/header.php'); ?>

<main>
    <section class="form-section">
        <h1>Add a Connection</h1>

        <!-- Search form -->
        <form method="GET" action="add_Connection.php" style="margin-bottom: 20px;">
            <input type="text" name="search" placeholder="Search members..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit">Search</button>
        </form>

        <!-- Display non-connected members -->
        <?php if ($result->num_rows > 0): ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div style="display: flex; justify-content: space-between; padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f4f4f4;">
                        <div>
                            <h2 style="margin: 0; font-size: 1.2em; font-weight: bold; color: black;"><?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?></h2>
                            <p style="margin: 0; color: gray;"><?php echo htmlspecialchars($row['Username']); ?></p>
                        </div>
                        <form method="POST" action="add_Connection.php">
                            <input type="hidden" name="connect_member_id" value="<?php echo $row['MemberID']; ?>">
                            <button type="submit" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                Connect
                            </button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No members found who are not connected to you.</p>
        <?php endif; ?>
    </section>
</main>

<?php include('includes/footer.php'); ?>


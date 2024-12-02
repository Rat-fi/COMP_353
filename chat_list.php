<?php
session_start();
include('config.php');

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch connections of the logged-in user (excluding the logged-in user from being shown as a connection)
$query = "
    SELECT 
        c.ConnectionID, 
        m.MemberID, 
        m.FirstName, 
        m.LastName, 
        m.Username, 
        MAX(msg.Timestamp) AS LastMessageTime,
        SUM(CASE WHEN msg.ReceiverID = $user_id AND msg.ReadStatus = 'Unread' THEN 1 ELSE 0 END) AS UnreadCount
    FROM Connections c
    JOIN Members m ON (m.MemberID = c.MemberID1 OR m.MemberID = c.MemberID2)
    LEFT JOIN Messages msg ON ((msg.SenderID = c.MemberID1 AND msg.ReceiverID = c.MemberID2) 
                          OR (msg.SenderID = c.MemberID2 AND msg.ReceiverID = c.MemberID1))
    WHERE (c.MemberID1 = $user_id OR c.MemberID2 = $user_id)
      AND c.Status = 'Confirmed' 
      AND m.MemberID != $user_id  -- Exclude the logged-in user
    GROUP BY c.ConnectionID, m.MemberID, m.FirstName, m.LastName, m.Username
    ORDER BY LastMessageTime DESC
";
$result = $conn->query($query);
?>

<?php include('includes/header.php'); ?>

<main>
    <h1>Chat</h1>
    <section class="connections-list">
        <?php while ($connection = $result->fetch_assoc()): ?>
            <div class="chat-card <?php echo ($connection['UnreadCount'] > 0) ? 'unread' : ''; ?>" 
                 onclick="window.location.href='chat.php?member_id=<?php echo $connection['MemberID']; ?>'">
                <p><?php echo htmlspecialchars($connection['FirstName'] . ' ' . $connection['LastName']); ?></p>
                <?php if ($connection['UnreadCount'] > 0): ?>
                    <span class="unread-badge"><?php echo $connection['UnreadCount']; ?> unread</span>
                <?php endif; ?>
                <p>Last message: 
                    <?php 
                    if (!empty($connection['LastMessageTime'])) {
                        echo date("F j, Y, g:i a", strtotime($connection['LastMessageTime']));
                    } else {
                        echo "No messages yet";
                    }
                    ?>
                </p>
            </div>
        <?php endwhile; ?>
    </section>
</main>

<?php include('includes/footer.php'); ?>

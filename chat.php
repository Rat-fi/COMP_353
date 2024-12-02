<?php
session_start();
include('config.php');

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the member_id from the query string
$other_member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;

// Fetch messages between the logged-in user and the selected member
$query = "
    SELECT * FROM Messages 
    WHERE (SenderID = $user_id AND ReceiverID = $other_member_id) 
       OR (SenderID = $other_member_id AND ReceiverID = $user_id)
    ORDER BY Timestamp ASC
";
$result = $conn->query($query);
?>

<?php include('includes/header.php'); ?>

<main>
    <h1>Chat with <?php echo htmlspecialchars($other_member_id); ?></h1>

    <section class="chat-container">
        <div class="messages">
            <?php while ($message = $result->fetch_assoc()): ?>
                <div class="message <?php echo ($message['SenderID'] == $user_id) ? 'sent' : 'received'; ?>">
                    <p><?php echo htmlspecialchars($message['Content']); ?></p>
                    <span class="timestamp"><?php echo date("F j, Y, g:i a", strtotime($message['Timestamp'])); ?></span>
                </div>
            <?php endwhile; ?>
        </div>

        <form action="send_message.php" method="POST" class="message-form">
            <input type="hidden" name="receiver_id" value="<?php echo $other_member_id; ?>">
            <textarea name="content" placeholder="Type your message..." required></textarea>
            <button type="submit" name="send_message">Send</button>
        </form>
    </section>
</main>

<?php include('includes/footer.php'); ?>

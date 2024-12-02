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

// Fetch the name of the other member
$query = "SELECT FirstName, LastName FROM Members WHERE MemberID = $other_member_id";
$result = $conn->query($query);
$other_member = $result->fetch_assoc();

// Mark unread messages from this member as read
$query_update_read_status = "
    UPDATE Messages
    SET ReadStatus = 'Read'
    WHERE ReceiverID = $user_id 
      AND SenderID = $other_member_id 
      AND ReadStatus = 'Unread'
";
$conn->query($query_update_read_status);

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['content'])) {
    $content = mysqli_real_escape_string($conn, $_POST['content']);

    // Insert the message into the database
    $query_insert_message = "
        INSERT INTO Messages (SenderID, ReceiverID, Content) 
        VALUES ($user_id, $other_member_id, '$content')
    ";
    $conn->query($query_insert_message);

    // Redirect back to the chat page to display the new message
    header("Location: chat.php?member_id=$other_member_id");
    exit();
}

// Fetch messages between the logged-in user and the selected member
$query_messages = "
    SELECT * FROM Messages 
    WHERE (SenderID = $user_id AND ReceiverID = $other_member_id) 
       OR (SenderID = $other_member_id AND ReceiverID = $user_id)
    ORDER BY Timestamp ASC
";
$result_messages = $conn->query($query_messages);
?>

<?php include('includes/header.php'); ?>

<main>
    <h1>Chat with <?php echo htmlspecialchars($other_member['FirstName'] . ' ' . $other_member['LastName']); ?></h1>

    <section class="chat-container">
        <div class="messages">
            <?php while ($message = $result_messages->fetch_assoc()): ?>
                <div class="message <?php echo ($message['SenderID'] == $user_id) ? 'sent' : 'received'; ?>">
                    <p><?php echo htmlspecialchars($message['Content']); ?></p>
                    <span class="timestamp"><?php echo date("F j, Y, g:i a", strtotime($message['Timestamp'])); ?></span>
                </div>
            <?php endwhile; ?>
        </div>

        <form action="chat.php?member_id=<?php echo $other_member_id; ?>" method="POST" class="message-form">
            <textarea name="content" placeholder="Type your message..." required></textarea>
            <button type="submit">Send</button>
        </form>
    </section>
</main>

<?php include('includes/footer.php'); ?>

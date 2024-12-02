<?php
session_start();
include('config.php');

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$content = isset($_POST['content']) ? $_POST['content'] : '';

// Insert the message into the database
$query = "
    INSERT INTO Messages (SenderID, ReceiverID, Content) 
    VALUES ($user_id, $receiver_id, '$content')
";
$conn->query($query);

// Redirect back to the chat page
header("Location: chat.php?member_id=$receiver_id");
exit();

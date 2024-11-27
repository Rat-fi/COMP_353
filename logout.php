<?php
session_start();

if (isset($_SESSION['user_id'])) {
    include('config.php');

    $user_id = $_SESSION['user_id'];
    $current_time = date('Y-m-d H:i:s');

    // Update the LastLogin field for the user
    $query = "UPDATE Members SET LastLogin = '$current_time' WHERE MemberID = $user_id";
    if (!$conn->query($query)) {
        error_log("Failed to update LastLogin for user ID $user_id: " . $conn->error);
    }
}

// Destroy the session and redirect to login
session_unset();
session_destroy();
header("Location: login.php");
exit();
?>
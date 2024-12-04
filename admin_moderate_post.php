<?php
session_start();
include('config.php');

if ($_SESSION['privilege'] !== 'Administrator') {
    echo "unauthorized";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = $_POST['id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE Posts SET ModerationStatus = 'Approved' WHERE PostID = ?");
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE Posts SET ModerationStatus = 'Rejected' WHERE PostID = ?");
    } else {
        echo "invalid_action";
        exit;
    }

    $stmt->bind_param("i", $postId);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    $conn->close();
}
?>

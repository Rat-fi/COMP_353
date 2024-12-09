<?php
session_start();
include('../config.php');

// Check if the user is an Administrator
if ($_SESSION['privilege'] !== 'Administrator') {
    echo "unauthorized";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = $_POST['id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        // Approve post
        $stmt = $conn->prepare("UPDATE Posts SET ModerationStatus = 'Approved' WHERE PostID = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $stmt->close();
        echo "success";
        exit;
    } elseif ($action === 'reject') {
        // Reject post
        $stmt = $conn->prepare("UPDATE Posts SET ModerationStatus = 'Rejected' WHERE PostID = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $stmt->close();

        // Fetch the AuthorID from the rejected post
        $stmt = $conn->prepare("SELECT AuthorID FROM Posts WHERE PostID = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        $post = $result->fetch_assoc();
        $authorId = $post['AuthorID'];
        $stmt->close();

        // Start a transaction
        $conn->begin_transaction();

        // Increment the warning count for the member
        $stmt = $conn->prepare("UPDATE Members SET WarningsCount = WarningsCount + 1 WHERE MemberID = ?");
        $stmt->bind_param("i", $authorId);
        $stmt->execute();
        $stmt->close();

        // Check the updated warnings count
        $stmt = $conn->prepare("SELECT WarningsCount FROM Members WHERE MemberID = ?");
        $stmt->bind_param("i", $authorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $member = $result->fetch_assoc();
        $warningsCount = $member['WarningsCount'];
        $stmt->close();

        if ($warningsCount >= 4) {
            // Reset warnings count and suspend the member
            $stmt = $conn->prepare("UPDATE Members SET WarningsCount = 0, Status = 'Suspended' WHERE MemberID = ?");
            $stmt->bind_param("i", $authorId);
            $stmt->execute();
            $stmt->close();
        }

        // Commit the transaction
        $conn->commit();
        echo "success";
        exit;
    } else {
        echo "invalid_action";
        exit;
    }
}

// Close the connection
$conn->close();
?>

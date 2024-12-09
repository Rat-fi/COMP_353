<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['id'];

    include('../config.php');

    $stmt = $conn->prepare("DELETE FROM Members WHERE MemberID = ?");
    $stmt->bind_param("i", $member_id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

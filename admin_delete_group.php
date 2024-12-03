<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = $_POST['id'];

    include('config.php');

    $stmt = $conn->prepare("DELETE FROM groups WHERE id = ?");
    $stmt->bind_param("i", $group_id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    $conn->close();
}
?>

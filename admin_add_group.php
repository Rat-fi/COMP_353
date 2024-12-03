<?php
session_start();
if (!isset($_SESSION['privilege']) || $_SESSION['privilege'] !== 'Administrator') {
    echo "<div style='display: flex; justify-content: center; align-items: center; height: 100vh;'>
            <h1>Access Denied Page For Non Admins.</h1>
          </div>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = $_POST['group_name'];
    
    include('config.php');

    $stmt = $conn->prepare("INSERT INTO groups (group_name) VALUES (?)");
    $stmt->bind_param("s", $group_name);
    if ($stmt->execute()) {
        header("Location: admin.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Group</title>
</head>
<body>
    <div class="container mt-5">
        <h2>Add Group</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="group_name">Group Name</label>
                <input type="text" class="form-control" id="group_name" name="group_name" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Group</button>
        </form>
    </div>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['privilege']) || $_SESSION['privilege'] !== 'Administrator') {
    echo "<div style='display: flex; justify-content: center; align-items: center; height: 100vh;'>
            <h1>Access Denied Page For Non Admins.</h1>
          </div>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $privilege = $_POST['privilege'];

    include('config.php');
    
    $stmt = $conn->prepare("INSERT INTO members (full_name, username, privilege) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $full_name, $username, $privilege);
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
    <title>Add Member</title>
</head>
<body>
    <div class="container mt-5">
        <h2>Add Member</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="privilege">Privilege</label>
                <select class="form-control" id="privilege" name="privilege">
                    <option value="Member">Member</option>
                    <option value="Administrator">Administrator</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Add Member</button>
        </form>
    </div>
</body>
</html>

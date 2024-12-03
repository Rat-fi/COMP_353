<?php
session_start();
include('config.php');

if (!isset($_SESSION['privilege']) || $_SESSION['privilege'] !== 'Administrator') {
    echo "<div style='display: flex; justify-content: center; align-items: center; height: 100vh;'>
            <h1>Access Denied Page For Non Admins.</h1>
          </div>";
    exit;
}

$group_id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = $_POST['group_name'];

    $stmt = $conn->prepare("UPDATE groups SET group_name=? WHERE id=?");
    $stmt->bind_param("si", $group_name, $group_id);
    if ($stmt->execute()) {
        header("Location: admin.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$result = $conn->query("SELECT * FROM groups WHERE id = $group_id");
$group = $result->fetch_assoc();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Group</title>
</head>
<body>
    <div class="container mt-5">
        <h2>Edit Group</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="group_name">Group Name</label>
                <input type="text" class="form-control" id="group_name" name="group_name" value="<?php echo $group['group_name']; ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Group</button>
        </form>
    </div>
</body>
</html>

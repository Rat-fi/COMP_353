<?php
session_start();
include('config.php');

// Check if the user is an Administrator
if (!isset($_SESSION['privilege']) || $_SESSION['privilege'] !== 'Administrator') {
    echo "<div style='display: flex; justify-content: center; align-items: center; height: 100vh;'>
            <h1>Access Denied Page For Non Admins.</h1>
          </div>";
    exit;
}

// Get the Group ID from the query string
$group_id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = $_POST['group_name'];

    // Update group details
    $stmt = $conn->prepare("UPDATE UserGroups SET GroupName=? WHERE GroupID=?");
    $stmt->bind_param("si", $group_name, $group_id);
    if ($stmt->execute()) {
        header("Location: admin.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch the existing group details
$stmt = $conn->prepare("SELECT * FROM UserGroups WHERE GroupID = ?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();
$group = $result->fetch_assoc();
$stmt->close();
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
                <input type="text" class="form-control" id="group_name" name="group_name" value="<?php echo htmlspecialchars($group['GroupName'], ENT_QUOTES); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Group</button>
        </form>
    </div>
</body>
</html>

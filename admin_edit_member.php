<?php
session_start();
include('config.php');

if (!isset($_SESSION['privilege']) || $_SESSION['privilege'] !== 'Administrator') {
    echo "<div style='display: flex; justify-content: center; align-items: center; height: 100vh;'>
            <h1>Access Denied Page For Non Admins.</h1>
          </div>";
    exit;
}

$member_id = $_GET['id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $privilege = $_POST['privilege'];

    $stmt = $conn->prepare("UPDATE members SET full_name=?, username=?, privilege=? WHERE id=?");
    $stmt->bind_param("sssi", $full_name, $username, $privilege, $member_id);
    if ($stmt->execute()) {
        header("Location: admin.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$result = $conn->query("SELECT * FROM members WHERE id = $member_id");
$member = $result->fetch_assoc();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Member</title>
</head>
<body>
    <div class="container mt-5">
        <h2>Edit Member</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $member['full_name']; ?>" required>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo $member['username']; ?>" required>
            </div>
            <div class="form-group">
                <label for="privilege">Privilege</label>
                <select class="form-control" id="privilege" name="privilege">
                    <option value="Member" <?php if ($member['privilege'] == 'Member') echo 'selected'; ?>>Member</option>
                    <option value="Administrator" <?php if ($member['privilege'] == 'Administrator') echo 'selected'; ?>>Administrator</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update Member</button>
        </form>
    </div>
</body>
</html>

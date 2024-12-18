<?php
session_start();
include('../config.php');

if (!isset($_SESSION['privilege']) || $_SESSION['privilege'] !== 'Administrator') {
    echo "<div style='display: flex; justify-content: center; align-items: center; height: 100vh;'>
            <h1>Access Denied Page For Non Admins.</h1>
          </div>";
    exit;
}

$member_id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $privilege = $_POST['privilege'];
    $status = $_POST['status']; // Get the status from the form

    // Update query to reflect the new schema
    $stmt = $conn->prepare("UPDATE Members SET FirstName=?, LastName=?, Username=?, Privilege=?, Status=? WHERE MemberID=?");
    $stmt->bind_param("sssssi", $first_name, $last_name, $username, $privilege, $status, $member_id);
    
    if ($stmt->execute()) {
        header("Location: admin.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch member details from the Members table
$result = $conn->query("SELECT * FROM Members WHERE MemberID = $member_id");
$member = $result->fetch_assoc();
$conn->close();
?>

<?php include('../includes/header.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Member</title>
    <style>
        body {
            text-align: center;
        }
        .form-group {
            margin-top: 10px;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
            text-align: left;
        }
        button {
            align-self: end;
        }
        input, select {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Edit Member</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($member['FirstName']); ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($member['LastName']); ?>" required>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($member['Username']); ?>" required>
            </div>
            <div class="form-group">
                <label for="privilege">Privilege</label>
                <select class="form-control" id="privilege" name="privilege">
                    <option value="Junior" <?php if ($member['Privilege'] == 'Junior') echo 'selected'; ?>>Junior</option>
                    <option value="Senior" <?php if ($member['Privilege'] == 'Senior') echo 'selected'; ?>>Senior</option>
                    <option value="Administrator" <?php if ($member['Privilege'] == 'Administrator') echo 'selected'; ?>>Administrator</option>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control" id="status" name="status">
                    <option value="Active" <?php if ($member['Status'] == 'Active') echo 'selected'; ?>>Active</option>
                    <option value="Inactive" <?php if ($member['Status'] == 'Inactive') echo 'selected'; ?>>Inactive</option>
                    <option value="Suspended" <?php if ($member['Status'] == 'Suspended') echo 'selected'; ?>>Suspended</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update Member</button>
        </form>
    </div>
</body>
</html>

<?php include('../includes/footer.php'); ?>

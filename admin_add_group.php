<?php
session_start();
if (!isset($_SESSION['privilege']) || $_SESSION['privilege'] !== 'Administrator') {
    echo "<div style='display: flex; justify-content: center; align-items: center; height: 100vh;'>
            <h1>Access Denied Page For Non Admins.</h1>
          </div>";
    exit;
}

include('config.php');

$stmt = $conn->prepare("SELECT MemberID, CONCAT(FirstName, ' ', LastName, ' (', Username, ')') AS MemberInfo FROM Members WHERE Status = 'Active'");
$stmt->execute();
$result = $stmt->get_result();
$members = $result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = $_POST['group_name'];
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $owner_id = $_POST['owner_id']; // Get the selected owner from the form

    $stmt = $conn->prepare("INSERT INTO UserGroups (GroupName, Description, OwnerID) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $group_name, $description, $owner_id);

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
<?php include('includes/header.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Group</title>
    <style>
        body{
            text-align: center;
        }
        .form-group{
            margin-top:10px;
            margin-bottom: 20px;
        }

        form{
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        button{
            align-self: end;
        }
        input{
            margin-top:10px;
        }
        textarea{
            margin-top:10px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Add Group</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="group_name">Group Name</label>
                <input type="text" class="form-control" id="group_name" name="group_name" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description"></textarea>
            </div>
            <div class="form-group">
                <label for="owner_id">Select Group Owner</label>
                <select class="form-control" id="owner_id" name="owner_id" required>
                    <option value="">Select a Member</option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?= $member['MemberID']; ?>">
                            <?= htmlspecialchars($member['MemberInfo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Add Group</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.4.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
<?php include('includes/footer.php'); ?>


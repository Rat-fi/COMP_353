<?php
session_start();
include('../config.php');

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
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $new_owner_id = $_POST['owner_id'];

    // Start transaction
    $conn->begin_transaction();
    try {
        // Fetch the current owner ID
        $stmt = $conn->prepare("SELECT OwnerID FROM UserGroups WHERE GroupID = ?");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_group = $result->fetch_assoc();
        $old_owner_id = $current_group['OwnerID'];
        $stmt->close();

        // Update group details
        $stmt = $conn->prepare("UPDATE UserGroups SET GroupName=?, Description=?, OwnerID=? WHERE GroupID=?");
        $stmt->bind_param("ssii", $group_name, $description, $new_owner_id, $group_id);
        if (!$stmt->execute()) {
            throw new Exception("Error updating UserGroups: " . $stmt->error);
        }
        $stmt->close();

        // Update the role of the old owner in GroupMembers
        $stmt = $conn->prepare("UPDATE GroupMembers SET Role='Member' WHERE GroupID=? AND MemberID=?");
        $stmt->bind_param("ii", $group_id, $old_owner_id);
        if (!$stmt->execute()) {
            throw new Exception("Error updating old owner's role: " . $stmt->error);
        }
        $stmt->close();

        // Check if the new owner exists in the GroupMembers table
        $stmt = $conn->prepare("SELECT GroupMemberID FROM GroupMembers WHERE GroupID=? AND MemberID=?");
        $stmt->bind_param("ii", $group_id, $new_owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            // Update the role of the new owner to 'Owner'
            $stmt = $conn->prepare("UPDATE GroupMembers SET Role='Owner' WHERE GroupID=? AND MemberID=?");
            $stmt->bind_param("ii", $group_id, $new_owner_id);
            if (!$stmt->execute()) {
                throw new Exception("Error updating new owner's role: " . $stmt->error);
            }
        } else {
            // Add the new owner to the GroupMembers table
            $stmt = $conn->prepare("INSERT INTO GroupMembers (GroupID, MemberID, Role, Status) VALUES (?, ?, 'Owner', 'Approved')");
            $stmt->bind_param("ii", $group_id, $new_owner_id);
            if (!$stmt->execute()) {
                throw new Exception("Error inserting new owner into GroupMembers: " . $stmt->error);
            }
        }
        $stmt->close();

        // Commit the transaction
        $conn->commit();

        // Redirect back to the admin page
        header("Location: admin.php");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        echo "Transaction failed: " . $e->getMessage();
    }
}

// Fetch the existing group details
$stmt = $conn->prepare("SELECT * FROM UserGroups WHERE GroupID = ?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();
$group = $result->fetch_assoc();
$stmt->close();

// Fetch active members for the owner selection dropdown
$stmt = $conn->prepare("SELECT MemberID, CONCAT(FirstName, ' ', LastName, ' (', Username, ')') AS MemberInfo FROM Members WHERE Status = 'Active'");
$stmt->execute();
$result = $stmt->get_result();
$members = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<?php include('../includes/header.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Group</title>
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
        input, textarea, select {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Edit Group</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="group_name">Group Name</label>
                <input type="text" class="form-control" id="group_name" name="group_name" value="<?php echo htmlspecialchars($group['GroupName'], ENT_QUOTES); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description"><?php echo htmlspecialchars($group['Description'], ENT_QUOTES); ?></textarea>
            </div>
            <div class="form-group">
                <label for="owner_id">Select Group Owner</label>
                <select class="form-control" id="owner_id" name="owner_id" required>
                    <option value="">Select a Member</option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?= $member['MemberID']; ?>" <?= $member['MemberID'] == $group['OwnerID'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($member['MemberInfo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update Group</button>
        </form>
    </div>
</body>
</html>
<?php include('../includes/footer.php'); ?>

<?php
session_start();
include('config.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user privilege
$query = "SELECT Privilege FROM Members WHERE MemberID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$privilege = $result->fetch_assoc()['Privilege'] ?? null;

// Restrict access to Seniors and Administrators
if ($privilege !== 'Senior' && $privilege !== 'Administrator') {
    echo "Access denied. You do not have permission to create groups.";
    exit();
}

// Fetch list of members to populate the owner dropdown if the user is an admin
$members_result = null;
if ($privilege === 'Administrator') {
    $query = "SELECT MemberID, CONCAT(FirstName, ' ', LastName) AS FullName FROM Members ORDER BY FirstName ASC";
    $members_result = $conn->query($query);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = trim($_POST['group_name']);
    $description = trim($_POST['description']);
    $owner_id = $privilege === 'Administrator' ? intval($_POST['owner_id']) : $user_id;

    // Validate inputs
    if (empty($group_name)) {
        $message = "Group name cannot be empty.";
    } elseif (strlen($group_name) > 100) {
        $message = "Group name cannot exceed 100 characters.";
    } elseif ($owner_id <= 0) {
        $message = "Invalid owner selected.";
    } else {
        // Start a transaction to ensure both inserts succeed
        $conn->begin_transaction();
        try {
            // Insert group into UserGroups
            $query = "INSERT INTO UserGroups (GroupName, Description, OwnerID) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssi", $group_name, $description, $owner_id);

            if ($stmt->execute()) {
                $group_id = $stmt->insert_id; // Get the ID of the newly created group

                // Insert owner into GroupMembers
                $query = "
                    INSERT INTO GroupMembers (GroupID, MemberID, Role, Status) 
                    VALUES (?, ?, 'Owner', 'Approved')";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $group_id, $owner_id);
                $stmt->execute();

                // Commit the transaction
                $conn->commit();
                $message = "Group successfully created!";
            } else {
                throw new Exception("Error creating group: " . $conn->error);
            }
        } catch (Exception $e) {
            // Rollback the transaction on error
            $conn->rollback();
            $message = $e->getMessage();
        }
    }
}

include('includes/header.php');
?>

<main style="padding: 1rem; max-width: 900px; margin: auto;">
    <h1>Create Group</h1>
    <?php if (isset($message)): ?>
        <p style="background: #f4f4f4; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px; color: <?php echo strpos($message, 'successfully') !== false ? 'green' : 'red'; ?>;">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <form action="create_group.php" method="POST" style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 1rem;">
        <div style="margin-bottom: 1rem;">
            <label for="group_name" style="display: block; font-weight: bold; margin-bottom: 0.5rem;">Group Name:</label>
            <input type="text" id="group_name" name="group_name" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;" maxlength="100" required>
        </div>
        <div style="margin-bottom: 1rem;">
            <label for="description" style="display: block; font-weight: bold; margin-bottom: 0.5rem;">Description:</label>
            <textarea id="description" name="description" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px; resize: vertical;" rows="5"></textarea>
        </div>
        <?php if ($privilege === 'Administrator'): ?>
            <div style="margin-bottom: 1rem;">
                <label for="owner_id" style="display: block; font-weight: bold; margin-bottom: 0.5rem;">Select Group Owner:</label>
                <select id="owner_id" name="owner_id" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;" required>
                    <option value="" disabled selected>Select a member</option>
                    <?php while ($member = $members_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($member['MemberID']); ?>">
                            <?php echo htmlspecialchars($member['FullName']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        <?php else: ?>
            <input type="hidden" name="owner_id" value="<?php echo htmlspecialchars($user_id); ?>">
        <?php endif; ?>
        <button type="submit" style="padding: 0.5rem 1rem; font-size: 1rem; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Create Group
        </button>
    </form>
</main>

<?php include('includes/footer.php'); ?>

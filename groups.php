<?php
session_start();
include('config.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle join group request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['group_id'])) {
    $group_id = intval($_POST['group_id']);

    // Check if the user already requested or is in the group
    $check_query = "
        SELECT * FROM GroupMembers 
        WHERE GroupID = ? AND MemberID = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
    $check_result = $stmt->get_result();

    if ($check_result->num_rows > 0) {
        $message = "You are already a member or have a pending request for this group.";
    } else {
        // Add a request to join the group
        $insert_query = "
            INSERT INTO GroupMembers (GroupID, MemberID, Status) 
            VALUES (?, ?, 'Pending')";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ii", $group_id, $user_id);

        if ($stmt->execute()) {
            $message = "Your request to join the group has been submitted.";
        } else {
            $message = "Error submitting join request: " . $conn->error;
        }
    }
}

// Fetch all groups and the user's memberships
$query = "
    SELECT 
        UserGroups.GroupID, 
        UserGroups.GroupName, 
        UserGroups.Description, 
        Members.FirstName AS OwnerFirstName, 
        Members.LastName AS OwnerLastName, 
        (CASE 
            WHEN UserGroups.OwnerID = ? THEN 'Owner'
            WHEN GroupMembers.Status = 'Approved' THEN 'Member'
            WHEN GroupMembers.Status = 'Pending' THEN 'Pending'
            ELSE NULL 
         END) AS MembershipStatus
    FROM 
        UserGroups
    LEFT JOIN 
        GroupMembers ON UserGroups.GroupID = GroupMembers.GroupID AND GroupMembers.MemberID = ?
    INNER JOIN 
        Members ON UserGroups.OwnerID = Members.MemberID
    ORDER BY 
        UserGroups.GroupName ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

include('includes/header.php');
?>

<main style="padding: 1rem; max-width: 900px; margin: auto;">
    <h1>All Groups</h1>

    <?php if (isset($message)): ?>
        <p style="background: #f4f4f4; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px; color: green;">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <div style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 1rem;">
        <?php if ($result->num_rows > 0): ?>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php while ($group = $result->fetch_assoc()): ?>
                    <li style="border-bottom: 1px solid #ddd; padding: 1rem; display: flex; justify-content: space-between; align-items: center; <?php if ($group['MembershipStatus'] === 'Owner' || $group['MembershipStatus'] === 'Member'): ?>cursor: pointer; transition: background-color 0.3s, box-shadow 0.3s;<?php endif; ?>"
                        <?php if ($group['MembershipStatus'] === 'Owner' || $group['MembershipStatus'] === 'Member'): ?>onmouseover="this.style.backgroundColor='#f9f9f9'; this.style.boxShadow='0 2px 5px rgba(0, 0, 0, 0.1)';" onmouseout="this.style.backgroundColor=''; this.style.boxShadow='';" <?php endif; ?>>
                        <?php if ($group['MembershipStatus'] === 'Owner' || $group['MembershipStatus'] === 'Member'): ?>
                            <a href="group.php?group_id=<?php echo htmlspecialchars($group['GroupID']); ?>" style="text-decoration: none; color: inherit; flex-grow: 1;">
                                <div>
                                    <h3 style="margin: 0;"><?php echo htmlspecialchars($group['GroupName']); ?></h3>
                                    <p style="margin: 0.5rem 0;"><?php echo htmlspecialchars($group['Description']); ?></p>
                                    <p style="color: grey; font-size: 0.85rem;">Owner: <?php echo htmlspecialchars($group['OwnerFirstName'] . ' ' . $group['OwnerLastName']); ?></p>
                                </div>
                            </a>
                        <?php else: ?>
                            <div>
                                <h3 style="margin: 0;"><?php echo htmlspecialchars($group['GroupName']); ?></h3>
                                <p style="margin: 0.5rem 0;"><?php echo htmlspecialchars($group['Description']); ?></p>
                                <p style="color: grey; font-size: 0.85rem;">Owner: <?php echo htmlspecialchars($group['OwnerFirstName'] . ' ' . $group['OwnerLastName']); ?></p>
                            </div>
                        <?php endif; ?>

                        <div>
                            <?php if ($group['MembershipStatus'] === 'Owner'): ?>
                                <p style="color: #28a745; margin: 0;">You are the owner.</p>
                            <?php elseif ($group['MembershipStatus'] === 'Member'): ?>
                                <p style="color: #28a745; margin: 0;">You are a member.</p>
                            <?php elseif ($group['MembershipStatus'] === 'Pending'): ?>
                                <p style="color: #ffc107; margin: 0;">Join request pending.</p>
                            <?php else: ?>
                                <button onclick="requestToJoin(<?php echo htmlspecialchars($group['GroupID']); ?>)" style="padding: 0.5rem; background: #007BFF; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                    Request to Join
                                </button>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No groups available at the moment.</p>
        <?php endif; ?>
    </div>

</main>

<script>
    function requestToJoin(groupID) {
        const formData = new FormData();
        formData.append('group_id', groupID);

        fetch('groups.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => window.location.reload())
            .catch(err => console.error('Error:', err));
    }
</script>

<?php include('includes/footer.php'); ?>
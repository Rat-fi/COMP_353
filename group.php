<?php
session_start();
include('config.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the GroupID from the URL
if (!isset($_GET['group_id']) || !is_numeric($_GET['group_id'])) {
    echo "Invalid Group ID.";
    exit();
}

$group_id = intval($_GET['group_id']);

// Fetch group details
$query = "
    SELECT 
        UserGroups.GroupName, 
        UserGroups.Description, 
        Members.FirstName AS OwnerFirstName, 
        Members.LastName AS OwnerLastName, 
        UserGroups.OwnerID 
    FROM 
        UserGroups
    INNER JOIN 
        Members ON UserGroups.OwnerID = Members.MemberID
    WHERE 
        UserGroups.GroupID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$group_result = $stmt->get_result();

if ($group_result->num_rows === 0) {
    echo "Group not found.";
    exit();
}

$group = $group_result->fetch_assoc();

// Check if the user is the owner or admin
$is_owner = $group['OwnerID'] == $user_id;

// Process member actions (accept/reject/remove)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['member_id'])) {
        $member_id = intval($_POST['member_id']);
        $action = $_POST['action'];

        if ($action === 'accept' && $is_owner) {
            $update_query = "UPDATE GroupMembers SET Status = 'Approved' WHERE MemberID = ? AND GroupID = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ii", $member_id, $group_id);
            $stmt->execute();
        } elseif ($action === 'reject' && $is_owner) {
            $delete_query = "DELETE FROM GroupMembers WHERE MemberID = ? AND GroupID = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("ii", $member_id, $group_id);
            $stmt->execute();
        } elseif ($action === 'remove' && $is_owner) {
            $delete_query = "DELETE FROM GroupMembers WHERE MemberID = ? AND GroupID = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("ii", $member_id, $group_id);
            $stmt->execute();
        }
    }
}

// Fetch latest posts in the group
$query = "
    SELECT 
        Posts.PostID, 
        Posts.ContentType, 
        Posts.ContentText, 
        Posts.ContentLink, 
        Posts.CreationDate, 
        Members.FirstName, 
        Members.LastName 
    FROM 
        Posts
    INNER JOIN 
        Members ON Posts.AuthorID = Members.MemberID
    WHERE 
        Posts.GroupID = ? AND Posts.ModerationStatus = 'Approved'
    ORDER BY 
        Posts.CreationDate DESC 
    LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$posts_result = $stmt->get_result();

// Fetch group members
$query = "
    SELECT 
        Members.MemberID,
        Members.FirstName, 
        Members.LastName, 
        Members.Username, 
        GroupMembers.Role, 
        GroupMembers.Status 
    FROM 
        GroupMembers
    INNER JOIN 
        Members ON GroupMembers.MemberID = Members.MemberID
    WHERE 
        GroupMembers.GroupID = ?
    ORDER BY 
        GroupMembers.Status ASC, GroupMembers.Role ASC, Members.FirstName ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members_result = $stmt->get_result();

include('includes/header.php');
?>

<main style="padding: 1rem; max-width: 1200px; margin: auto;">
    <!-- Group Details -->
    <section style="background: #f4f4f4; border: 1px solid #ddd; border-radius: 5px; padding: 1rem; margin-bottom: 2rem;">
        <h1><?php echo htmlspecialchars($group['GroupName']); ?></h1>
        <p><strong>Owner:</strong> <?php echo htmlspecialchars($group['OwnerFirstName'] . ' ' . $group['OwnerLastName']); ?></p>
        <p><?php echo htmlspecialchars($group['Description']); ?></p>
    </section>

    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem;">
        <!-- Latest Posts -->
        <section style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 1rem;">
            <div style="display: flex; justify-content: space-between;">
                <h2>Latest Posts</h2>
                <button onclick="window.location.href='create_post.php?group_id=<?php echo $group_id; ?>'" style="margin-bottom: 1rem; padding: 0.5rem; background: #212e54; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    Create Post
                </button>
            </div>
            <?php if ($posts_result->num_rows > 0): ?>
                <div style="margin-top: 1rem;">
                    <?php while ($post = $posts_result->fetch_assoc()): ?>
                        <a href="post.php?post_id=<?php echo htmlspecialchars($post['PostID']); ?>" style="text-decoration: none; color: inherit;">
                            <div style="padding: 0.5rem; border-bottom: 1px solid #ddd; cursor: pointer;">
                                <p><strong><?php echo htmlspecialchars($post['FirstName'] . ' ' . $post['LastName']); ?></strong>
                                    <span style="color: grey; font-size: 0.85rem;">(<?php echo htmlspecialchars($post['CreationDate']); ?>)</span>
                                </p>
                                <p><?php echo htmlspecialchars($post['ContentText']); ?></p>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No posts available in this group.</p>
            <?php endif; ?>
        </section>

        <!-- Members List -->
        <section style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 1rem;">
            <h2>Members</h2>
            <?php if ($members_result->num_rows > 0): ?>
                <ul style="list-style-type: none; padding: 0;">
                    <?php while ($member = $members_result->fetch_assoc()): ?>
                        <li style="margin-bottom: 1rem; padding: 0.5rem; border-bottom: 1px solid #ddd;">
                            <strong><?php echo htmlspecialchars($member['FirstName'] . ' ' . $member['LastName']); ?></strong>
                            <span style="color: grey;">(<?php echo htmlspecialchars($member['Username']); ?>)</span>
                            <br>
                            <span style="font-size: 0.85rem; color: #777;">
                                <?php echo htmlspecialchars($member['Role']); ?> - <?php echo htmlspecialchars($member['Status']); ?>
                            </span>
                            <?php if ($is_owner): ?>
                                <div style="margin-left: 1rem;">
                                    <?php if ($member['Status'] === 'Pending'): ?>
                                        <button onclick="handleMemberAction(<?php echo $member['MemberID']; ?>, 'accept')" style="color: green; border: none; background: none; cursor: pointer;">Accept</button>
                                        <button onclick="handleMemberAction(<?php echo $member['MemberID']; ?>, 'reject')" style="color: red; border: none; background: none; cursor: pointer;">Reject</button>
                                    <?php elseif ($member['Status'] === 'Approved'): ?>
                                        <button onclick="handleMemberAction(<?php echo $member['MemberID']; ?>, 'remove')" style="color: red; border: none; background: none; cursor: pointer;">Remove</button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No members in this group.</p>
            <?php endif; ?>
        </section>

        <!-- Events Section -->
        <section style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 1rem;">
            <h2>Upcoming Events</h2>
            <button onclick="window.location.href='create_event.php?group_id=<?php echo $group_id; ?>'" style="margin-bottom: 1rem; padding: 0.5rem; background: #212e54; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Create Event
            </button>
            <?php
            // Fetch upcoming events for the group
            $query = "
                SELECT 
                    EventID, 
                    EventName, 
                    Description, 
                    EventDate, 
                    Location, 
                    Status 
                FROM 
                    Events 
                WHERE 
                    GroupID = ? AND Status = 'Scheduled'
                ORDER BY 
                    EventDate ASC";
            $stmt = $conn->prepare($query);

            if ($stmt) {
                $stmt->bind_param("i", $group_id);
                $stmt->execute();
                $events_result = $stmt->get_result();
            } else {
                die("Error preparing events query: " . $conn->error);
            }
            if ($events_result->num_rows > 0): ?>
                <ul style="list-style-type: none; padding: 0;">
                    <?php while ($event = $events_result->fetch_assoc()): ?>
                        <a href="event.php?event_id=<?php echo htmlspecialchars($event['EventID']); ?>" style="text-decoration: none; color: inherit;">
                            <li style="margin-bottom: 1rem; padding: 0.5rem; border-bottom: 1px solid #ddd; cursor: pointer;">
                                <strong><?php echo htmlspecialchars($event['EventName']); ?></strong>
                                <p style="margin: 0.5rem 0;"><?php echo htmlspecialchars($event['Description']); ?></p>
                                <p style="font-size: 0.85rem; color: grey;">
                                    <strong>Date:</strong> <?php echo htmlspecialchars($event['EventDate']); ?><br>
                                    <strong>Location:</strong> <?php echo htmlspecialchars($event['Location']); ?>
                                </p>
                            </li>
                        </a>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No upcoming events scheduled for this group.</p>
            <?php endif; ?>
        </section>
    </div>
</main>

<script>
    function handleMemberAction(memberId, action) {
        const formData = new FormData();
        formData.append('member_id', memberId);
        formData.append('action', action);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        }).then(response => response.text())
          .then(() => {
              location.reload(); // Reload the page to reflect the changes
          });
    }
</script>

<?php include('includes/footer.php'); ?>
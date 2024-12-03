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
        Members.LastName AS OwnerLastName 
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
        Members.FirstName, 
        Members.LastName, 
        Members.Username, 
        GroupMembers.Role 
    FROM 
        GroupMembers
    INNER JOIN 
        Members ON GroupMembers.MemberID = Members.MemberID
    WHERE 
        GroupMembers.GroupID = ?
    ORDER BY 
        GroupMembers.Role ASC, Members.FirstName ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members_result = $stmt->get_result();

include('includes/header.php');
?>

<main style="padding: 1rem; max-width: 900px; margin: auto;">
    <!-- Group Details -->
    <section style="background: #f4f4f4; border: 1px solid #ddd; border-radius: 5px; padding: 1rem; margin-bottom: 2rem;">
        <h1><?php echo htmlspecialchars($group['GroupName']); ?></h1>
        <p><strong>Owner:</strong> <?php echo htmlspecialchars($group['OwnerFirstName'] . ' ' . $group['OwnerLastName']); ?></p>
        <p><?php echo htmlspecialchars($group['Description']); ?></p>
    </section>

    <div style="display: flex; gap: 2rem;">
        <!-- Latest Posts -->
        <section style="flex: 1; background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 1rem;">
            <h2>Latest Posts</h2>
            <?php if ($posts_result->num_rows > 0): ?>
                <div style="margin-top: 1rem;">
                    <?php while ($post = $posts_result->fetch_assoc()): ?>
                        <div style="padding: 0.5rem; border-bottom: 1px solid #ddd;">
                            <p><strong><?php echo htmlspecialchars($post['FirstName'] . ' ' . $post['LastName']); ?></strong>
                                <span style="color: grey; font-size: 0.85rem;">(<?php echo htmlspecialchars($post['CreationDate']); ?>)</span>
                            </p>
                            <p><?php echo htmlspecialchars($post['ContentText']); ?></p>
                            <?php if (!empty($post['ContentLink']) && $post['ContentType'] === 'Image'): ?>
                                <img src="<?php echo htmlspecialchars($post['ContentLink']); ?>" alt="Post Image" style="max-width: 100%; border-radius: 5px;">
                            <?php elseif (!empty($post['ContentLink']) && $post['ContentType'] === 'Video'): ?>
                                <video controls style="max-width: 100%; border-radius: 5px;">
                                    <source src="<?php echo htmlspecialchars($post['ContentLink']); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No posts available in this group.</p>
            <?php endif; ?>
        </section>

        <!-- Members List -->
        <section style="flex: 1; background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 1rem;">
            <h2>Members</h2>
            <?php if ($members_result->num_rows > 0): ?>
                <ul style="list-style-type: none; padding: 0;">
                    <?php while ($member = $members_result->fetch_assoc()): ?>
                        <li style="margin-bottom: 1rem; padding: 0.5rem; border-bottom: 1px solid #ddd;">
                            <strong><?php echo htmlspecialchars($member['FirstName'] . ' ' . $member['LastName']); ?></strong>
                            <span style="color: grey;">(<?php echo htmlspecialchars($member['Username']); ?>)</span>
                            <br>
                            <span style="font-size: 0.85rem; color: #777;"><?php echo htmlspecialchars($member['Role']); ?></span>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No members in this group.</p>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include('includes/footer.php'); ?>

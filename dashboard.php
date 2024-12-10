<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('config.php');
include('includes/header.php');

// Fetch current user data
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM Members WHERE MemberID = $user_id";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Set FirstName and LastName in the session if not already set
    if (!isset($_SESSION['FirstName'])) {
        $_SESSION['FirstName'] = $user['FirstName'];
    }
    if (!isset($_SESSION['LastName'])) {
        $_SESSION['LastName'] = $user['LastName'];
    }
} else {
    echo "<p>User not found.</p>";
    exit();
}

// Session timeout logic
if (!isset($_SESSION['LAST_ACTIVITY'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
} elseif (time() - $_SESSION['LAST_ACTIVITY'] > 1800) { // 30 minutes
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();
?>

<main>
    <section class="dashboard">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr;">
            <div></div>
            <h1 style="justify-self: center;">
                Welcome,
                <?php echo htmlspecialchars($_SESSION['FirstName'] ?? 'User'); ?>
                <?php echo htmlspecialchars($_SESSION['LastName'] ?? ''); ?>!
            </h1>
            <div style="justify-self: flex-end;">
                <a href="profile/edit_profile.php" class="link-button">Edit Profile</a>
                <p>Your privilege level: <strong><?php echo htmlspecialchars($_SESSION['privilege']); ?></strong></p>
            </div>
        </div>

        <section style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
            <!-- Latest Messages -->
            <section style="padding: 0.5rem; background: #f4f4f4; border: 1px solid #ddd; border-radius: 5px; text-align: center;">
                <div style="display: flex; position: relative; justify-content: center; align-items: center;">
                    <p>Latest Messages</p>
                    <a href="./chat/chat_list.php" style="position: absolute; right: 0; align-content: center;" class="link-button">messages</a>
                </div>
                <div style="height: 500px; overflow-y: auto; display: flex; flex-direction: column; align-items: flex-start; padding: 0.5rem; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
                    <?php
                    // Fetch the latest messages for the current user
                    $query = "
                        SELECT 
                            Messages.Content, 
                            Messages.Timestamp, 
                            Members.Username AS SenderName,
                            Members.MemberID AS SenderID
                        FROM 
                            Messages 
                        INNER JOIN 
                            Members ON Messages.SenderID = Members.MemberID 
                        WHERE 
                            Messages.ReceiverID = $user_id 
                        ORDER BY 
                            Messages.Timestamp DESC 
                        LIMIT 5";
                    $result = $conn->query($query);

                    if ($result->num_rows > 0) {
                        while ($message = $result->fetch_assoc()) {
                            echo "<a href='./chat/chat.php?member_id=" . htmlspecialchars($message['SenderID']) . "' 
                                style='text-decoration: none; color: inherit; width: 100%;'>
                                <div style='padding: 0.3rem; background: #f9f9f9; border: 1px solid #ddd; border-radius: 3px; font-size: 0.85rem; margin-bottom: 0.3rem;'>
                                    <p><strong>" . htmlspecialchars($message['SenderName']) . ":</strong> " . htmlspecialchars($message['Content']) . "</p>
                                    <p style='font-size: 0.75rem; color: #777;'>"
                                . htmlspecialchars($message['Timestamp']) . "</p>
                                </div>
                            </a>";
                        }
                    } else {
                        echo "<p style='font-size: 0.85rem;'>No new messages.</p>";
                    }
                    ?>
                </div>
            </section>

            <!-- Latest Posts -->
            <section style="padding: 0.5rem; background: #f4f4f4; border: 1px solid #ddd; border-radius: 5px; text-align: center;">
                <p>Latest Posts</p>
                <div style="height: 500px; overflow-y: auto; display: flex; flex-direction: column; align-items: flex-start; padding: 0.5rem; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
                    <?php
                    // Fetch the latest posts visible to the user
                    $query = "
                        SELECT 
                            Posts.PostID, 
                            Posts.ContentType, 
                            Posts.ContentText, 
                            Posts.ContentLink, 
                            Posts.CreationDate, 
                            Posts.Visibility, 
                            Members.FirstName, 
                            Members.LastName, 
                            UserGroups.GroupName 
                        FROM 
                            Posts
                        LEFT JOIN 
                            UserGroups ON Posts.GroupID = UserGroups.GroupID
                        INNER JOIN 
                            Members ON Posts.AuthorID = Members.MemberID
                        LEFT JOIN 
                            GroupMembers ON Posts.GroupID = GroupMembers.GroupID
                        WHERE 
                            (Posts.Visibility = 'Public' AND Posts.ModerationStatus = 'Approved')
                            OR 
                            (Posts.Visibility = 'Group' AND GroupMembers.MemberID = $user_id AND Posts.ModerationStatus = 'Approved')
                        GROUP BY 
                            Posts.PostID
                        ORDER BY 
                            Posts.CreationDate DESC 
                        LIMIT 5";
                    $result = $conn->query($query);

                    if ($result->num_rows > 0) {
                        while ($post = $result->fetch_assoc()) {
                            echo "<a href='posts/post.php?post_id=" . htmlspecialchars($post['PostID']) . "' style='text-decoration: none; color: inherit; width: 100%;'>
                                <div style='background: #f9f9f9; border: 1px solid #ddd; border-radius: 3px; font-size: 0.85rem; margin-bottom: 0.3rem; width: 99%;'>
                                    <p><strong>" . htmlspecialchars($post['FirstName']) . " " . htmlspecialchars($post['LastName']) . "</strong> 
                                    <span style='font-size: 0.75rem; color: #777;'>(" . htmlspecialchars($post['Visibility']) .
                                (!empty($post['GroupName']) ? " in " . htmlspecialchars($post['GroupName']) : "") . ")</span></p>
                                    <p>" . htmlspecialchars($post['ContentText']) . "</p>";

                            // Display content link for images/videos
                            if (!empty($post['ContentLink']) && in_array($post['ContentType'], ['Image', 'Video'])) {
                                $mediaTag = $post['ContentType'] === 'Image'
                                    ? "<img src='" . htmlspecialchars($post['ContentLink']) . "' alt='Post Image' style='max-width: 100%; border-radius: 5px;'/>"
                                    : "<video controls style='max-width: 100%; border-radius: 5px;'><source src='" . htmlspecialchars($post['ContentLink']) . "' type='video/mp4'>Your browser does not support the video tag.</video>";
                                echo $mediaTag;
                            }

                            echo "<p style='font-size: 0.75rem; color: #777;'>Posted on: " . htmlspecialchars($post['CreationDate']) . "</p>
                                </div>
                            </a>";
                        }
                    } else {
                        echo "<p style='font-size: 0.85rem;'>No posts available.</p>";
                    }
                    ?>
                </div>
            </section>

            <!-- Groups Section -->
            <section style="padding: 0.5rem; background: #f4f4f4; border: 1px solid #ddd; border-radius: 5px; text-align: center;">
                <div style="display: flex; position: relative; justify-content: center;  align-items: center;">
                    <p>My Groups</p>
                    <a href="groups/groups.php" style="position: absolute; right: 0; align-content: center;" class="link-button">manage</a>
                </div>
                <div style="height: 200px; overflow-y: auto; display: flex; flex-direction: column; align-items: flex-start; padding: 0.5rem; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
                    <?php
                    // Fetch groups the user is part of
                    $query = "
                        SELECT 
                            UserGroups.GroupID, 
                            UserGroups.GroupName, 
                            UserGroups.Description, 
                            GroupMembers.Role 
                        FROM 
                            UserGroups
                        INNER JOIN 
                            GroupMembers ON UserGroups.GroupID = GroupMembers.GroupID
                        WHERE 
                            GroupMembers.MemberID = $user_id AND GroupMembers.Status = 'Approved'
                        ORDER BY 
                            GroupMembers.Role ASC, UserGroups.GroupName ASC";
                    $result = $conn->query($query);

                    if ($result->num_rows > 0) {
                        while ($group = $result->fetch_assoc()) {
                            echo "<a href='groups/group.php?group_id=" . htmlspecialchars($group['GroupID']) . "' style='text-decoration: none; color: inherit; width: 100%;'>
                                <div style='padding: 0.3rem; background: #f9f9f9; border: 1px solid #ddd; border-radius: 3px; font-size: 0.85rem; margin-bottom: 0.3rem;'>
                                    <p><strong>" . htmlspecialchars($group['GroupName']) . "</strong> 
                                    <span style='font-size: 0.75rem; color: #777;'>(" . htmlspecialchars($group['Role']) . ")</span></p>
                                    <p style='font-size: 0.75rem;'>" . htmlspecialchars($group['Description']) . "</p>
                                </div>
                            </a>";
                        }
                    } else {
                        echo "<p style='font-size: 0.85rem;'>No groups found.</p>";
                    }
                    ?>
                </div>
                <div style="display: flex; position: relative; padding-top: 0.5rem; justify-content: center; align-items: center;">
                    <p>Latest Friend Requests</p>
                    <a href="./connections/connections.php" style="position: absolute; right: 0; align-content: center;" class="link-button">manage</a>
                </div>
                <div style="height: 200px; overflow-y: auto; display: flex; flex-direction: column; align-items: flex-start; padding: 0.5rem; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
                    <?php
                    // Fetch latest friend requests for the current user
                    $query = "
                        SELECT 
                            Members.MemberID, 
                            Members.Username, 
                            Members.FirstName, 
                            Members.LastName 
                        FROM 
                            Connections 
                        INNER JOIN 
                            Members ON Connections.MemberID1 = Members.MemberID 
                        WHERE 
                            Connections.MemberID2 = $user_id 
                            AND Connections.Status = 'Requested'
                        ORDER BY 
                            Connections.RequestDate DESC
                        LIMIT 10";
                    $result = $conn->query($query);

                    if ($result->num_rows > 0) {
                        while ($request = $result->fetch_assoc()) {
                            echo "<a href='./connections/connections.php' 
                                style='text-decoration: none; color: inherit; width: 100%;'>
                                <div style='padding: 0.3rem; background: #f9f9f9; border: 1px solid #ddd; border-radius: 3px; font-size: 0.85rem; margin-bottom: 0.3rem;'>
                                    <p><strong>" . htmlspecialchars($request['FirstName']) . " " . htmlspecialchars($request['LastName']) . "</strong> - " . htmlspecialchars($request['Username']) . "</p>
                                </div>
                            </a>";
                        }
                    } else {
                        echo "<p style='font-size: 0.85rem;'>No new friend requests.</p>";
                    }
                    ?>
                </div>
            </section>
        </section>
    </section>
</main>

<?php include('includes/footer.php'); ?>
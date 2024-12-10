<?php
session_start();
include('../config.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
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
$query = "SELECT GroupName FROM UserGroups WHERE GroupID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$group_result = $stmt->get_result();

if ($group_result->num_rows === 0) {
    echo "Group not found.";
    exit();
}

$group = $group_result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content_type = $_POST['content_type'];
    $content_text = $conn->real_escape_string($_POST['content_text']);
    $content_link = !empty($_POST['content_link']) ? $conn->real_escape_string($_POST['content_link']) : null;
    $visibility = isset($_POST['visibility']) ? $_POST['visibility'] : 'Public';

    // Validation
    if (empty($content_text)) {
        $error_message = "Post content cannot be empty.";
    } else {
        // Insert the post into the database
        $query = "
            INSERT INTO Posts (AuthorID, GroupID, ContentType, ContentText, ContentLink, Visibility, ModerationStatus) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iissss", $user_id, $group_id, $content_type, $content_text, $content_link, $visibility);

        if ($stmt->execute()) {
            $success_message = "Post created successfully and is awaiting moderation.";
        } else {
            $error_message = "Error creating post: " . $conn->error;
        }
    }
}

include('../includes/header.php');
?>

<main style="padding: 1rem; max-width: 900px; margin: auto;">
    <h1>Create a Post in <?php echo htmlspecialchars($group['GroupName']); ?></h1>

    <?php if (isset($success_message)): ?>
        <p style="background: #d4edda; padding: 0.5rem; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724;">
            <?php echo htmlspecialchars($success_message); ?>
        </p>
    <?php elseif (isset($error_message)): ?>
        <p style="background: #f8d7da; padding: 0.5rem; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">
            <?php echo htmlspecialchars($error_message); ?>
        </p>
    <?php endif; ?>

    <form action="create_post.php?group_id=<?php echo $group_id; ?>" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
        <div>
            <label for="content_type" style="display: block; margin-bottom: 0.5rem;">Content Type</label>
            <select id="content_type" name="content_type" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px; width: 100%;">
                <option value="Text">Text</option>
                <option value="Image">Image</option>
                <option value="Video">Video</option>
            </select>
        </div>

        <div>
            <label for="content_text" style="display: block; margin-bottom: 0.5rem;">Content Text</label>
            <textarea id="content_text" name="content_text" rows="5" placeholder="Write your post here..." style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px; width: 100%;"></textarea>
        </div>

        <div>
            <label for="content_link" style="display: block; margin-bottom: 0.5rem;">Content Link (Optional, for Images or Videos)</label>
            <input type="url" id="content_link" name="content_link" placeholder="Enter a link for your content (optional)" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px; width: 100%;">
        </div>

        <div>
            <label for="visibility" style="display: block; margin-bottom: 0.5rem;">Visibility</label>
            <select id="visibility" name="visibility" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px; width: 100%;">
                <option value="Public">Public</option>
                <option value="Group">Group Only</option>
            </select>
        </div>

        <button type="submit" style="padding: 0.5rem; background: #007BFF; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Submit Post
        </button>
    </form>
</main>

<?php include('../includes/footer.php'); ?>

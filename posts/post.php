<?php
session_start();
include('../config.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the PostID from the URL
if (!isset($_GET['post_id']) || !is_numeric($_GET['post_id'])) {
    echo "Invalid Post ID.";
    exit();
}

$post_id = intval($_GET['post_id']);

// Fetch post details
$query = "
    SELECT 
        Posts.ContentType, 
        Posts.ContentText, 
        Posts.ContentLink, 
        Posts.CreationDate, 
        Posts.Visibility, 
        Posts.GroupID, 
        Members.FirstName, 
        Members.LastName 
    FROM 
        Posts
    INNER JOIN 
        Members ON Posts.AuthorID = Members.MemberID
    WHERE 
        Posts.PostID = ?
        AND (Posts.Visibility = 'Public' OR Posts.GroupID IN (
            SELECT GroupID FROM GroupMembers WHERE MemberID = ?
        ))
        AND Posts.ModerationStatus = 'Approved'
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$post_result = $stmt->get_result();

if ($post_result->num_rows === 0) {
    echo "Post not found or you do not have permission to view it.";
    exit();
}

$post = $post_result->fetch_assoc();

// Fetch comments for the post
$query = "
    SELECT 
        Comments.Content, 
        Comments.CreationDate, 
        Members.FirstName, 
        Members.LastName 
    FROM 
        Comments
    INNER JOIN 
        Members ON Comments.AuthorID = Members.MemberID
    WHERE 
        Comments.PostID = ?
    ORDER BY 
        Comments.CreationDate ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$comments_result = $stmt->get_result();

include('../includes/header.php');
?>

<main style="padding: 1rem; max-width: 800px; margin: auto;">
    <!-- Post Details -->
    <section style="background: #f4f4f4; border: 1px solid #ddd; border-radius: 5px; padding: 1rem; margin-bottom: 2rem;">
        <h2>Post by <?php echo htmlspecialchars($post['FirstName'] . ' ' . $post['LastName']); ?></h2>
        <p><strong>Posted on:</strong> <?php echo htmlspecialchars($post['CreationDate']); ?></p>
        <div style="margin: 1rem 0;">
            <?php
            echo htmlspecialchars($post['ContentText']);
            if ($post['ContentType'] === 'Image' && $post['ContentLink']) {
                echo "<img src='" . htmlspecialchars($post['ContentLink']) . "' alt='Post Image' style='max-width: 100%; margin-top: 1rem;'/>";
            } elseif ($post['ContentType'] === 'Video' && $post['ContentLink']) {
                echo "<video controls style='max-width: 100%; margin-top: 1rem;'><source src='" . htmlspecialchars($post['ContentLink']) . "' type='video/mp4'>Your browser does not support the video tag.</video>";
            }
            ?>
        </div>
    </section>

    <!-- Comments Section -->
    <section style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 1rem;">
        <h3>Comments</h3>
        <?php if ($comments_result->num_rows > 0): ?>
            <div style="margin-top: 1rem;">
                <?php while ($comment = $comments_result->fetch_assoc()): ?>
                    <div style="padding: 0.5rem; border-bottom: 1px solid #ddd;">
                        <p>
                            <strong><?php echo htmlspecialchars($comment['FirstName'] . ' ' . $comment['LastName']); ?></strong>
                            <span style="color: grey; font-size: 0.85rem;">(<?php echo htmlspecialchars($comment['CreationDate']); ?>)</span>
                        </p>
                        <p><?php echo htmlspecialchars($comment['Content']); ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No comments yet. Be the first to comment!</p>
        <?php endif; ?>

        <!-- Add Comment Form -->
        <form action="post.php?post_id=<?php echo $post_id; ?>" method="POST" style="margin-top: 1rem;">
            <textarea name="comment" placeholder="Write your comment here..." required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;"></textarea>
            <button type="submit" style="margin-top: 0.5rem; padding: 0.5rem 1rem; background: #007BFF; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Post Comment
            </button>
        </form>
    </section>
</main>

<?php include('../includes/footer.php'); ?>

<?php
// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment_content = trim($_POST['comment']);
    if (!empty($comment_content)) {
        $query = "INSERT INTO Comments (PostID, AuthorID, Content) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iis", $post_id, $user_id, $comment_content);
        if ($stmt->execute()) {
            header("Location: post.php?post_id=$post_id");
            exit();
        } else {
            echo "<script>alert('Error posting comment.');</script>";
        }
    }
}
?>

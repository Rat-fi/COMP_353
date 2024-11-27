<?php
session_start();

include('config.php');
include('includes/header.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<main>
    <div class="form-container">
        <h1>Create a New Post</h1>
        <form method="POST" enctype="multipart/form-data">
            <!-- Text content -->
            <label for="content_text">Post Text</label>
            <textarea name="content_text" id="content_text" rows="5" required></textarea>

            <!-- Multimedia upload -->
            <label for="content_file">Upload Multimedia</label>
            <input type="file" name="content_file" id="content_file" accept="image/*,video/*">

            <!-- Multimedia URL -->
            <label for="content_url">Or Enter Multimedia URL</label>
            <input type="url" name="content_url" id="content_url" placeholder="https://example.com">

            <!-- Submit button -->
            <button type="submit" name="post">Create Post</button>
        </form>
    </div>
</main>


<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post'])) {
    $authorID = $user_id;
    $contentType = 'Text';
    $contentLink = null;
    $contentText = $conn->real_escape_string($_POST['content_text']);
    $creationDate = date('Y-m-d H:i:s');

    if (!empty($_FILES['content_file']['name'])) {
        $uploadDir = 'uploads/';
        $uploadFile = $uploadDir . basename($_FILES['content_file']['name']);
        if (move_uploaded_file($_FILES['content_file']['tmp_name'], $uploadFile)) {
            $contentLink = $uploadFile;
            $contentType = 'Image';
        } else {
            echo "<p>Error uploading file.</p>";
        }
    } elseif (!empty($_POST['content_url'])) {
        $contentLink = $_POST['content_url'];
        $contentType = 'Video';
    }

    // Insert into the database
    $stmt = $conn->prepare("INSERT INTO Posts (AuthorID, ContentType, ContentText, ContentLink, CreationDate) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $authorID, $contentType, $contentText, $contentLink, $creationDate);

    if ($stmt->execute()) {
        echo "<p>Post created successfully!</p>";
    } else {
        echo "<p>Error creating post: " . $stmt->error . "</p>";
    }
}
?>

<?php include('includes/footer.php'); ?>
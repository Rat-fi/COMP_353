<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('config.php');
include('includes/header.php');

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM Posts WHERE MemberID = $user_id";

// Example data for the post
$post = [
    'username' => 'JohnDoe123',
    'created_at' => '2024-11-26 10:30:00',
    'text' => 'This is an example post! Check out this cool picture below.',
    'image' => 'https://via.placeholder.com/600x300', // Replace with your image URL
    'comments' => [
        ['username' => 'JaneDoe', 'text' => 'Awesome post!'],
        ['username' => 'User456', 'text' => 'Love this!'],
        ['username' => 'CoolGuy', 'text' => 'Amazing!'],
        ['username' => 'AnotherUser', 'text' => 'Great work!'],
    ],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Example</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f9;
        }
        .post {
            border: 1px solid #ccc;
            border-radius: 8px;
            background: white;
            padding: 15px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .username {
            font-weight: bold;
            color: #333;
        }
        .dropdown {
            position: relative;
        }
        .dropdown-button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border: 1px solid #ccc;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            list-style: none;
            padding: 5px 0;
            z-index: 1000;
        }
        .dropdown-menu li {
            padding: 5px 15px;
            cursor: pointer;
        }
        .dropdown-menu li:hover {
            background: #f0f0f0;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        .post-body {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #f9f9f9;
        }
        .post-image {
            max-width: 100%;
            border-radius: 4px;
            margin-top: 10px;
        }
        .post-date {
            font-size: 0.85em;
            color: gray;
            margin-top: 5px;
        }
        .comments {
            margin-top: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            max-height: 150px; /* Limit the height */
            overflow-y: auto; /* Enable scrolling */
            background: #fff;
        }
        .comments h4 {
            margin: 0;
            padding: 10px;
            border-bottom: 1px solid #eee;
            background: #f9f9f9;
        }
        .comment {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .comment:last-child {
            border-bottom: none;
        }
        .comment-username {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="post">
        <!-- Post Header -->
        <div class="post-header">
            <div class="username"><?= htmlspecialchars($post['username']) ?></div>
            <div class="dropdown">
                <button class="dropdown-button">⋮</button>
                <ul class="dropdown-menu">
                    <li>Edit Post</li>
                    <li>Delete Post</li>
                    <li>Report</li>
                </ul>
            </div>
        </div>
        <!-- Post Body -->
        <div class="post-body">
            <p><?= nl2br(htmlspecialchars($post['text'])) ?></p>
            <?php if (!empty($post['image'])): ?>
                <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post Image" class="post-image">
            <?php endif; ?>
        </div>
        <!-- Post Date -->
        <div class="post-date">Posted on <?= date('F j, Y, g:i a', strtotime($post['created_at'])) ?></div>
        <!-- Comments Section -->
        <div class="comments">
            <h4>Comments:</h4>
            <?php foreach ($post['comments'] as $comment): ?>
                <div class="comment">
                    <span class="comment-username"><?= htmlspecialchars($comment['username']) ?>:</span>
                    <span><?= htmlspecialchars($comment['text']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>

<?php
session_start();

include('../config.php');

// Check if the user has Administrator privilege
if (!isset($_SESSION['privilege']) || $_SESSION['privilege'] !== 'Administrator') {
    echo "<div style='display: flex; justify-content: center; align-items: center; height: 100vh;'>
            <h1>Access Denied Page For Non Admins.</h1>
          </div>";
    exit;
}

// Fetch members and groups from the database
$members_result = $conn->query("SELECT * FROM Members");
$groups_result = $conn->query("SELECT * FROM UserGroups");

// Fetch pending posts with joins for required fields
$pending_posts_query = "
    SELECT 
        Posts.PostID, 
        Members.Username AS AuthorUsername,
        Posts.Visibility,
        IF(Posts.Visibility = 'Group', UserGroups.GroupName, NULL) AS GroupName,
        Posts.ContentText, 
        Posts.ContentType,
        Posts.ContentLink, 
        Posts.CreationDate 
    FROM 
        Posts 
    LEFT JOIN 
        Members ON Posts.AuthorID = Members.MemberID 
    LEFT JOIN 
        UserGroups ON Posts.GroupID = UserGroups.GroupID 
    WHERE 
        Posts.ModerationStatus = 'Pending' 
    ORDER BY 
        Posts.CreationDate ASC";
$pending_posts_result = $conn->query($pending_posts_query);

$conn->close();
?>

<?php include('../includes/header.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page</title>
    <style>
        /* Existing styles here */
        /* Additional styling for Pending Posts cards */
        .btn-success {
            background-color: #5cb85c;
            color: white;
        }
         /* Basic styling for the page */
         body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        /* Tab container */
        .tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        /* Tab content */
        .tab-content {
            display: none;
            padding: 20px;
            border: 1px solid #ccc;
            background-color: #fff;
            border-top: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Card style */
        .card {
            display: flex;
            justify-content: space-between;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .card h5 {
            margin: 0;
            font-size: 18px;
        }

        .card p {
            margin: 5px 0;
        }

        .card .btn {
            margin-top: 10px;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 5px;
        }

        .btn-warning {
            background-color: #f0ad4e;
            color: white;
        }

        .btn-danger {
            background-color: #d9534f;
            color: white;
        }

        /* Search input */
        .form-control {
            padding: 10px;
            margin-top: 10px;
            width: 100%;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Admin Page</h2>
        <!-- Tab links -->
        <div class="tabs">
            <div class="tab-button active" data-target="#members">Members</div>
            <div class="tab-button" data-target="#groups">Groups</div>
            <div class="tab-button" data-target="#pending-posts">Pending Posts</div>
        </div>

        <!-- Tab content for Members -->
        <div class="tab-content active" id="members">
            <button onclick="window.location.href='admin_add_member.php'" class="btn btn-primary">Add Member</button>
            <input type="text" id="search-member" placeholder="Search Members" class="form-control">
            <div id="members-list">
                <?php while ($member = $members_result->fetch_assoc()): ?>
                    <div class="card" id="member-<?php echo $member['MemberID']; ?>">
                        <div>
                            <h5><?php echo $member['FirstName'] . ' ' . $member['LastName']; ?></h5>
                            <p class="text-muted"><?php echo $member['Username']; ?></p>
                        </div>
                        <div class="text-right">
                            <a href="admin_edit_member.php?id=<?php echo $member['MemberID']; ?>" class="btn btn-warning">Edit</a>
                            <button class="btn btn-danger delete-member" data-id="<?php echo $member['MemberID']; ?>">Delete</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Tab content for Groups -->
        <div class="tab-content" id="groups">
            <button onclick="window.location.href='../groups/create_group.php'" class="btn btn-primary">Add Group</button>
            <input type="text" id="search-group" placeholder="Search Groups" class="form-control">
            <div id="groups-list">
                <?php while ($group = $groups_result->fetch_assoc()): ?>
                    <div class="card" id="group-<?php echo $group['GroupID']; ?>">
                        <div>
                            <h5><?php echo $group['GroupName']; ?></h5>
                        </div>
                        <div class="text-right">
                            <a href="admin_edit_group.php?id=<?php echo $group['GroupID']; ?>" class="btn btn-warning">Edit</a>
                            <button class="btn btn-danger delete-group" data-id="<?php echo $group['GroupID']; ?>">Delete</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

      <!-- Tab content for Pending Posts -->
<div class="tab-content" id="pending-posts">
    <div id="pending-posts-list">
        <?php while ($post = $pending_posts_result->fetch_assoc()): ?>
            <div class="card" id="post-<?php echo $post['PostID']; ?>">
                <div>
                    <h5>Author: <?php echo htmlspecialchars($post['AuthorUsername']); ?></h5>
                    <p><strong>Visibility: </strong>
                        <?php echo htmlspecialchars($post['Visibility']); ?>
                        <?php if ($post['Visibility'] === 'Group'): ?>
                            (<?php echo htmlspecialchars($post['GroupName']); ?>)
                        <?php endif; ?>
                    </p>
                    <p><strong>Content:</strong> <?php echo nl2br(htmlspecialchars($post['ContentText'])); ?></p>
                    
                    <div style="margin: 1rem 0;">
                        <?php
                        if ($post['ContentType'] === 'Image' && !empty($post['ContentLink'])) {
                            echo "<p><strong>Media:</strong></p>";
                            echo "<img src='" . htmlspecialchars($post['ContentLink']) . "' alt='Post Image' style='max-width: 300px; margin-top: 1rem; border-radius: 5px;'/>";
                        } elseif ($post['ContentType'] === 'Video' && !empty($post['ContentLink'])) {
                            echo "<p><strong>Media:</strong></p>";
                            echo "<video controls style='max-width: 100%; margin-top: 1rem; border-radius: 5px;'><source src='" . htmlspecialchars($post['ContentLink']) . "' type='video/mp4'>Your browser does not support the video tag.</video>";
                        }
                        ?>
                    </div>
                    
                    <p><strong>Created On:</strong> <?php echo htmlspecialchars($post['CreationDate']); ?></p>
                </div>
                <div class="text-right">
                    <button class="btn btn-success approve-post" data-id="<?php echo $post['PostID']; ?>">Approve</button>
                    <button class="btn btn-danger reject-post" data-id="<?php echo $post['PostID']; ?>">Reject</button>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

</div>

    <script>
// Tab functionality
const tabs = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        // Tab switching logic
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and content
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                // Add active class to the clicked tab and corresponding content
                tab.classList.add('active');
                const target = document.querySelector(tab.getAttribute('data-target'));
                target.classList.add('active');
            });
        });

        // Ajax for deleting members
        document.querySelectorAll(".delete-member").forEach(button => {
            button.addEventListener('click', function() {
                var memberId = this.getAttribute("data-id");
                if (confirm("Are you sure you want to delete this member?")) {
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "admin_delete_member.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onload = function() {
                        if (xhr.responseText === "success") {
                            document.getElementById("member-" + memberId).style.display = "none";
                        } else {
                            alert("Error deleting member.");
                        }
                    };
                    xhr.send("id=" + memberId);
                }
            });
        });

        // Ajax for deleting groups
        document.querySelectorAll(".delete-group").forEach(button => {
            button.addEventListener('click', function() {
                var groupId = this.getAttribute("data-id");
                if (confirm("Are you sure you want to delete this group?")) {
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "admin_delete_group.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onload = function() {
                        if (xhr.responseText === "success") {
                            document.getElementById("group-" + groupId).style.display = "none";
                        } else {
                            alert("Error deleting group.");
                        }
                    };
                    xhr.send("id=" + groupId);
                }
            });
        });

        // Search functionality for members
        document.getElementById("search-member").addEventListener("input", function() {
            var searchTerm = this.value.toLowerCase();
            document.querySelectorAll(".card", "#members-list").forEach(card => {
                var fullName = card.querySelector("h5").textContent.toLowerCase();
                var username = card.querySelector(".text-muted").textContent.toLowerCase();
                if (fullName.indexOf(searchTerm) === -1 && username.indexOf(searchTerm) === -1) {
                    card.style.display = 'none';
                } else {
                    card.style.display = 'flex';
                }
            });
        });

        // Search functionality for groups
        document.getElementById("search-group").addEventListener("input", function() {
            var searchTerm = this.value.toLowerCase();
            document.querySelectorAll(".card", "#groups-list").forEach(card => {
                var groupName = card.querySelector("h5").textContent.toLowerCase();
                if (groupName.indexOf(searchTerm) === -1) {
                    card.style.display = 'none';
                } else {
                    card.style.display = 'flex';
                }
            });
        });
        
        // AJAX for approving posts
        document.querySelectorAll(".approve-post").forEach(button => {
            button.addEventListener('click', function() {
                var postId = this.getAttribute("data-id");
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "admin_moderate_post.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function() {
                    if (xhr.responseText === "success") {
                        document.getElementById("post-" + postId).style.display = "none";
                    } else {
                        alert("Error approving post.");
                    }
                };
                xhr.send("id=" + postId + "&action=approve");
            });
        });

        // AJAX for rejecting posts
        document.querySelectorAll(".reject-post").forEach(button => {
            button.addEventListener('click', function() {
                var postId = this.getAttribute("data-id");
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "admin_moderate_post.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function() {
                    if (xhr.responseText === "success") {
                        document.getElementById("post-" + postId).style.display = "none";
                    } else {
                        alert("Error rejecting post.");
                    }
                };
                xhr.send("id=" + postId + "&action=reject");
            });
        });
    </script>
</body>
</html>

<?php include('../includes/footer.php'); ?>

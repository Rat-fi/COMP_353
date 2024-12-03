<?php
session_start();

include('config.php');

if (!isset($_SESSION['privilege']) || $_SESSION['privilege'] !== 'Administrator') {
    echo "<div style='display: flex; justify-content: center; align-items: center; height: 100vh;'>
            <h1>Access Denied Page For Non Admins.</h1>
          </div>";
    exit;
}

$members_result = $conn->query("SELECT * FROM members");
$groups_result = $conn->query("SELECT * FROM groups");
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h2>Admin Page</h2>
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" id="members-tab" data-bs-toggle="tab" href="#members">Members</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="groups-tab" data-bs-toggle="tab" href="#groups">Groups</a>
            </li>
        </ul>
        <div class="tab-content mt-3">
            <!-- Members Tab -->
            <div class="tab-pane fade show active" id="members">
                <button onclick="window.location.href='admin_add_member.php'" class="btn btn-primary">Add Member</button>
                <input type="text" id="search-member" placeholder="Search Members" class="form-control mt-3">
                <div id="members-list" class="mt-3">
                    <?php while ($member = $members_result->fetch_assoc()): ?>
                        <div class="card mb-3" id="member-<?php echo $member['id']; ?>">
                            <div class="card-body d-flex">
                                <div class="w-50">
                                    <h5><?php echo $member['full_name']; ?></h5>
                                    <p class="text-muted"><?php echo $member['username']; ?></p>
                                </div>
                                <div class="w-25">
                                    <p class="text-muted">Member Id: <?php echo $member['id']; ?></p>
                                </div>
                                <div class="w-25 text-right">
                                    <a href="admin_edit_member.php?id=<?php echo $member['id']; ?>" class="btn btn-warning">Edit</a>
                                    <button class="btn btn-danger delete-member" data-id="<?php echo $member['id']; ?>">Delete</button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <!-- Groups Tab -->
            <div class="tab-pane fade" id="groups">
                <button onclick="window.location.href='admin_add_group.php'" class="btn btn-primary">Add Group</button>
                <input type="text" id="search-group" placeholder="Search Groups" class="form-control mt-3">
                <div id="groups-list" class="mt-3">
                    <?php while ($group = $groups_result->fetch_assoc()): ?>
                        <div class="card mb-3" id="group-<?php echo $group['id']; ?>">
                            <div class="card-body d-flex">
                                <div class="w-50">
                                    <h5><?php echo $group['group_name']; ?></h5>
                                </div>
                                <div class="w-25">
                                    <p class="text-muted">Group Id: <?php echo $group['id']; ?></p>
                                </div>
                                <div class="w-25 text-right">
                                    <a href="admin_edit_group.php?id=<?php echo $group['id']; ?>" class="btn btn-warning">Edit</a>
                                    <button class="btn btn-danger delete-group" data-id="<?php echo $group['id']; ?>">Delete</button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Ajax for deleting members
        $(".delete-member").click(function() {
            var memberId = $(this).data("id");
            if (confirm("Are you sure you want to delete this member?")) {
                $.ajax({
                    type: "POST",
                    url: "admin_delete_member.php",
                    data: { id: memberId },
                    success: function(response) {
                        if (response === "success") {
                            $("#member-" + memberId).fadeOut();
                        } else {
                            alert("Error deleting member.");
                        }
                    }
                });
            }
        });

        // Ajax for deleting groups
        $(".delete-group").click(function() {
            var groupId = $(this).data("id");
            if (confirm("Are you sure you want to delete this group?")) {
                $.ajax({
                    type: "POST",
                    url: "admin_delete_group.php",
                    data: { id: groupId },
                    success: function(response) {
                        if (response === "success") {
                            $("#group-" + groupId).fadeOut();
                        } else {
                            alert("Error deleting group.");
                        }
                    }
                });
            }
        });
        
        // Optional: Implement search functionality for members and groups
        $("#search-member").on("input", function() {
            var searchTerm = $(this).val().toLowerCase();
            $(".card", "#members-list").each(function() {
                var fullName = $(this).find("h5").text().toLowerCase();
                var username = $(this).find(".text-muted").first().text().toLowerCase();
                if (fullName.indexOf(searchTerm) === -1 && username.indexOf(searchTerm) === -1) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        });

        $("#search-group").on("input", function() {
            var searchTerm = $(this).val().toLowerCase();
            $(".card", "#groups-list").each(function() {
                var groupName = $(this).find("h5").text().toLowerCase();
                if (groupName.indexOf(searchTerm) === -1) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        });
    </script>
</body>
</html>

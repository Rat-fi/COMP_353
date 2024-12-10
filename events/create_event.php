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

// Check if the user is a member of the group
$query = "
    SELECT 
        GroupMembers.Role 
    FROM 
        GroupMembers 
    WHERE 
        GroupID = ? AND MemberID = ? AND Status = 'Approved'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$membership_result = $stmt->get_result();

if ($membership_result->num_rows === 0) {
    echo "You are not a member of this group.";
    exit();
}

$membership = $membership_result->fetch_assoc();

include('../includes/header.php');

// Handle event creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_name = $_POST['event_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $location = $_POST['location'] ?? '';

    if (!empty($event_name) && !empty($event_date)) {
        $query = "
            INSERT INTO Events (GroupID, OrganizerID, EventName, Description, EventDate, Location, Status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Scheduled')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iissss", $group_id, $user_id, $event_name, $description, $event_date, $location);

        if ($stmt->execute()) {
            echo "<script>alert('Event created successfully.'); window.location.href = '../groups/group.php?group_id=$group_id';</script>";
            exit();
        } else {
            echo "<script>alert('Error creating event. Please try again.');</script>";
        }
    } else {
        echo "<script>alert('Event name and date are required.');</script>";
    }
}
?>

<main style="padding: 1rem; max-width: 600px; margin: auto;">
    <section style="background: #f4f4f4; border: 1px solid #ddd; border-radius: 5px; padding: 1rem;">
        <h1>Create Event</h1>
        <form action="create_event.php?group_id=<?php echo $group_id; ?>" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
            <label for="event_name">Event Name</label>
            <input type="text" id="event_name" name="event_name" placeholder="Enter event name" required style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">

            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="Enter event description (optional)" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;"></textarea>

            <label for="event_date">Event Date</label>
            <input type="datetime-local" id="event_date" name="event_date" required style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">

            <label for="location">Location</label>
            <textarea id="location" name="location" placeholder="Enter event location (optional)" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;"></textarea>

            <button type="submit" style="padding: 0.5rem; background: #007BFF; color: white; border: none; border-radius: 5px; cursor: pointer;">Create Event</button>
        </form>
    </section>
</main>

<?php include('../includes/footer.php'); ?>

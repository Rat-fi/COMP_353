<?php
session_start();
include('../config.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the EventID from the URL
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    echo "Invalid Event ID.";
    exit();
}

$event_id = intval($_GET['event_id']);

// Fetch event details
$query = "
    SELECT 
        Events.EventName, 
        Events.Description, 
        Events.EventDate, 
        Events.Location, 
        Events.Status, 
        Events.OrganizerID, 
        Members.FirstName AS OrganizerFirstName, 
        Members.LastName AS OrganizerLastName 
    FROM 
        Events
    INNER JOIN 
        Members ON Events.OrganizerID = Members.MemberID
    WHERE 
        Events.EventID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event_result = $stmt->get_result();

if ($event_result->num_rows === 0) {
    echo "Event not found.";
    exit();
}

$event = $event_result->fetch_assoc();

// Fetch participants
$query = "
    SELECT 
        Members.FirstName, 
        Members.LastName, 
        Members.Username 
    FROM 
        EventVotes
    INNER JOIN 
        Members ON EventVotes.MemberID = Members.MemberID
    WHERE 
        EventVotes.EventID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$participants_result = $stmt->get_result();

include('../includes/header.php');
?>

<main style="padding: 1rem; max-width: 900px; margin: auto;">
    <!-- Event Details -->
    <section style="background: #f4f4f4; border: 1px solid #ddd; border-radius: 5px; padding: 1rem; margin-bottom: 2rem;">
        <h1><?php echo htmlspecialchars($event['EventName']); ?></h1>
        <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['OrganizerFirstName'] . ' ' . $event['OrganizerLastName']); ?></p>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($event['Description']); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($event['EventDate']); ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($event['Location']); ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($event['Status']); ?></p>
        <?php if ($user_id == $event['OrganizerID']): ?>
            <!-- Organizer Controls -->
            <button onclick="document.getElementById('editEventModal').style.display = 'block';" style="margin-top: 1rem; padding: 0.5rem; background: #007BFF; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Edit Event Details
            </button>
        <?php endif; ?>
    </section>

    <!-- Organizer Edit Modal -->
    <div id="editEventModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border: 1px solid #ddd; border-radius: 5px; padding: 1rem; width: 90%; max-width: 500px; z-index: 1000;">
        <h2>Edit Event Details</h2>
        <form action="event.php?event_id=<?php echo $event_id; ?>" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
            <label for="new_status">Status</label>
            <select id="new_status" name="new_status" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                <option value="Scheduled" <?php echo $event['Status'] === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                <option value="Completed" <?php echo $event['Status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="Cancelled" <?php echo $event['Status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>

            <label for="new_date">Date</label>
            <input type="datetime-local" id="new_date" name="new_date" value="<?php echo date('Y-m-d\TH:i', strtotime($event['EventDate'])); ?>" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">

            <label for="new_location">Location</label>
            <textarea id="new_location" name="new_location" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;"><?php echo htmlspecialchars($event['Location']); ?></textarea>

            <button type="submit" style="padding: 0.5rem; background: #007BFF; color: white; border: none; border-radius: 5px; cursor: pointer;">Save Changes</button>
            <button type="button" onclick="document.getElementById('editEventModal').style.display = 'none';" style="padding: 0.5rem; background: #007BFF; color: #000; border: 1px solid #ddd; border-radius: 5px; cursor: pointer;">Cancel</button>
        </form>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Participants and Votes -->
        <section style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 1rem;">
            <h2>Participants and Votes</h2>
            <?php if ($participants_result->num_rows > 0): ?>
                <ul style="list-style-type: none; padding: 0;">
                    <?php
                    // Fetch votes and display them alongside participants
                    $query = "
                        SELECT 
                            Members.FirstName, 
                            Members.LastName, 
                            Members.Username, 
                            EventVotes.SuggestedDate, 
                            EventVotes.SuggestedLocation 
                        FROM 
                            EventVotes
                        INNER JOIN 
                            Members ON EventVotes.MemberID = Members.MemberID
                        WHERE 
                            EventVotes.EventID = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $event_id);
                    $stmt->execute();
                    $votes_result = $stmt->get_result();

                    if ($votes_result->num_rows > 0) {
                        while ($vote = $votes_result->fetch_assoc()): ?>
                            <li style="margin-bottom: 1rem; padding: 0.5rem; border-bottom: 1px solid #ddd;">
                                <strong><?php echo htmlspecialchars($vote['FirstName'] . ' ' . $vote['LastName']); ?></strong>
                                <span style="color: grey;">(<?php echo htmlspecialchars($vote['Username']); ?>)</span>
                                <p style="margin: 0.5rem 0;">
                                    <strong>Suggested Date:</strong> <?php echo htmlspecialchars($vote['SuggestedDate'] ?? 'Not provided'); ?><br>
                                    <strong>Suggested Location:</strong> <?php echo htmlspecialchars($vote['SuggestedLocation'] ?? 'Not provided'); ?>
                                </p>
                            </li>
                    <?php endwhile;
                    } else {
                        echo "<p>No votes submitted yet.</p>";
                    }
                    ?>
                </ul>
            <?php else: ?>
                <p>No participants yet.</p>
            <?php endif; ?>
        </section>

        <!-- Voting Section -->
        <section style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 1rem;">
            <h2>Vote</h2>
            <?php
            // Check if user has already voted
            $query = "SELECT * FROM EventVotes WHERE EventID = ? AND MemberID = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $event_id, $user_id);
            $stmt->execute();
            $vote_result = $stmt->get_result();
            $user_voted = $vote_result->num_rows > 0;

            if ($user_voted): ?>
                <p>You have already voted for this event.</p>
            <?php else: ?>
                <form action="event.php?event_id=<?php echo $event_id; ?>" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                    <label for="suggested_date">Suggested Date</label>
                    <input type="datetime-local" id="suggested_date" name="suggested_date" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">

                    <label for="suggested_location">Suggested Location</label>
                    <textarea id="suggested_location" name="suggested_location" placeholder="Enter suggested location" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;"></textarea>

                    <button type="submit" style="padding: 0.5rem; background: #007BFF; color: white; border: none; border-radius: 5px; cursor: pointer;">Submit Vote</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include('../includes/footer.php'); ?>

<?php

// Handle organizer edits
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    $new_status = $_POST['new_status'];
    $new_date = $_POST['new_date'];
    $new_location = $_POST['new_location'];

    if ($user_id == $event['OrganizerID']) {
        $query = "UPDATE Events SET Status = ?, EventDate = ?, Location = ? WHERE EventID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $new_status, $new_date, $new_location, $event_id);

        if ($stmt->execute()) {
            echo "<script>alert('Event details updated successfully.'); window.location.href = 'event.php?event_id=$event_id';</script>";
        } else {
            echo "<script>alert('Error updating event details. Please try again.');</script>";
        }
    } else {
        echo "<script>alert('You are not authorized to edit this event.');</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['suggested_date']) && isset($_POST['suggested_location'])) {
    $suggested_date = $_POST['suggested_date'];
    $suggested_location = $_POST['suggested_location'];

    // Check if the user has already voted for this event
    $query = "SELECT * FROM EventVotes WHERE EventID = ? AND MemberID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $event_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('You have already voted for this event.');</script>";
    } else {
        // Insert the user's vote
        $query = "INSERT INTO EventVotes (EventID, MemberID, SuggestedDate, SuggestedLocation) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiss", $event_id, $user_id, $suggested_date, $suggested_location);

        if ($stmt->execute()) {
            echo "<script>alert('Your vote has been submitted successfully.'); window.location.href = 'event.php?event_id=$event_id';</script>";
        } else {
            echo "<script>alert('Error submitting your vote. Please try again.');</script>";
        }
    }
}
?>


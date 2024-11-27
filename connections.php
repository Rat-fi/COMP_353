<?php
session_start();
include('config.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle AJAX requests for canceling or accepting a connection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['connection_id'])) {
    $connection_id = $_POST['connection_id'];

    if (isset($_POST['cancel_connection'])) {
        // Cancel the connection
        $query = "
            DELETE FROM Connections
            WHERE ConnectionID = ? 
            AND (MemberID1 = ? OR MemberID2 = ?)
        ";

        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("iii", $connection_id, $user_id, $user_id);
            if ($stmt->execute()) {
                echo "success";  // Return success message
            } else {
                echo "error";    // Return error message
            }
            $stmt->close();
        } else {
            echo "error";      // Return error if query preparation fails
        }
    } elseif (isset($_POST['accept_connection'])) {
        // Accept the connection
        $query = "
            UPDATE Connections
            SET Status = 'Confirmed'
            WHERE ConnectionID = ?
            AND MemberID2 = ?
            AND Status = 'Requested'
        ";

        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("ii", $connection_id, $user_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo "success";  // Return success message
            } else {
                echo "error";    // Return error message
            }
            $stmt->close();
        } else {
            echo "error";      // Return error if query preparation fails
        }
    }
    exit(); // End the script after handling the AJAX request
}

// Fetch connections
$query = "
    SELECT 
        c.Relation, 
        m.FirstName, 
        m.LastName, 
        m.Username, 
        c.ConnectionID, 
        c.Status,
        c.MemberID1,
        c.MemberID2
    FROM Connections c
    JOIN Members m ON (m.MemberID = IF(c.MemberID1 = $user_id, c.MemberID2, c.MemberID1))
    WHERE (c.MemberID1 = $user_id OR c.MemberID2 = $user_id)
    ORDER BY c.Relation, m.FirstName
";

$result = $conn->query($query);
$connections = [
    'Friend' => ['Requested' => [], 'Confirmed' => []], 
    'Family' => ['Requested' => [], 'Confirmed' => []], 
    'Colleague' => ['Requested' => [], 'Confirmed' => []]
];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $connections[$row['Relation']][$row['Status']][] = $row;
    }
}

include('includes/header.php');
?>

<main>
    <section class="connections-section">
        <h1>Your Connections</h1>

        <div class="tabs">
            <button class="tab-button active" onclick="showTab('friends')">Friends</button>
            <button class="tab-button" onclick="showTab('family')">Family</button>
            <button class="tab-button" onclick="showTab('colleagues')">Colleagues</button>
            <button class="add-connection-btn" onclick="window.location.href='add_connection.php'">Add Connection</button>
        </div>

        <div id="friends" class="tab-content active">
            <h2>Requested</h2>
            <?php displayConnections($connections['Friend']['Requested'], 'Requested'); ?>

            <h2>Friends</h2>
            <?php displayConnections($connections['Friend']['Confirmed'], 'Friends'); ?>
        </div>

        <div id="family" class="tab-content">
            <h2>Requested</h2>
            <?php displayConnections($connections['Family']['Requested'], 'Requested'); ?>

            <h2>Family</h2>
            <?php displayConnections($connections['Family']['Confirmed'], 'Family'); ?>
        </div>

        <div id="colleagues" class="tab-content">
            <h2>Requested</h2>
            <?php displayConnections($connections['Colleague']['Requested'], 'Requested'); ?>

            <h2>Colleagues</h2>
            <?php displayConnections($connections['Colleague']['Confirmed'], 'Colleagues'); ?>
        </div>
    </section>
</main>

<?php include('includes/footer.php'); ?>

<script>
function showTab(tabName) {
    var tabs = document.getElementsByClassName("tab-content");
    var buttons = document.getElementsByClassName("tab-button");

    for (var i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove("active");
        buttons[i].classList.remove("active");
    }
    document.getElementById(tabName).classList.add("active");
    event.target.classList.add("active");
}

function cancelConnection(connectionID) {
    if (confirm("Are you sure you want to cancel this connection?")) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "connections.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                if (xhr.responseText === "success") {
                    alert("Connection cancelled successfully");
                    location.reload();
                } else {
                    alert("Error: Unable to cancel the connection.");
                }
            }
        };
        xhr.send("cancel_connection=true&connection_id=" + connectionID);
    }
}

function acceptConnection(connectionID) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "connections.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            if (xhr.responseText === "success") {
                alert("Connection accepted successfully");
                location.reload();
            } else {
                alert("Error: Unable to accept the connection.");
            }
        }
    };
    xhr.send("accept_connection=true&connection_id=" + connectionID);
}

function changeConnection() {
    // Empty function for now (Placeholder)
}
</script>

<?php
// Function to display connection cards
function displayConnections($connections, $type) {
    if (empty($connections)) {
        echo "<p>No $type connections found.</p>";
        return;
    }

    foreach ($connections as $connection) {
        echo '
            <div class="connection-card">
                <div class="connection-info">
                    <strong>' . htmlspecialchars($connection['FirstName']) . ' ' . htmlspecialchars($connection['LastName']) . '</strong><br>
                    <span style="color: grey;">' . htmlspecialchars($connection['Username']) . '</span>
                </div>
                <div class="connection-action">';
        if ($type === 'Requested') {
            echo '<button class="cancel-connection-btn" onclick="cancelConnection(' . $connection['ConnectionID'] . ')">Cancel</button>';
            if ($connection['MemberID2'] == $_SESSION['user_id']) {
                echo '<button class="accept-connection-btn" style="background-color: green;" onclick="acceptConnection(' . $connection['ConnectionID'] . ')">Accept</button>';
            }
        } else {
            echo '<button class="change-connection-btn" onclick="changeConnection()">Change</button>';
        }
        echo '</div></div>';
    }
}
?>

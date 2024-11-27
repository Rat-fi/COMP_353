<?php
session_start();
include('config.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle AJAX requests for canceling, accepting, or changing a connection
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
                echo "success";
            } else {
                echo "error";
            }
            $stmt->close();
        } else {
            echo "error";
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
                echo "success";
            } else {
                echo "error";
            }
            $stmt->close();
        } else {
            echo "error";
        }
    } elseif (isset($_POST['change_connection'])) {
        // Change the connection relation
        $new_relation = $_POST['new_relation'];
        
        // Fetch the current relation and member IDs
        $query = "
            SELECT MemberID1, MemberID2, Relation 
            FROM Connections 
            WHERE ConnectionID = ?
        ";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("i", $connection_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $current_relation = $row['Relation'];
            $member_id1 = $row['MemberID1'];
            $member_id2 = $row['MemberID2'];
            $stmt->close();
            
            // If the relation has changed, delete the old connection and create a new one
            if ($current_relation !== $new_relation) {
                // Delete the existing connection
                $query = "
                    DELETE FROM Connections 
                    WHERE ConnectionID = ?
                ";
                if ($stmt = $conn->prepare($query)) {
                    $stmt->bind_param("i", $connection_id);
                    $stmt->execute();
                    $stmt->close();
                }

                // Create a new connection record
                $query = "
                    INSERT INTO Connections (MemberID1, MemberID2, Relation, Status)
                    VALUES (?, ?, ?, 'Requested')
                ";
                if ($stmt = $conn->prepare($query)) {
                    // Assign MemberID1 as the user and MemberID2 as the other person
                    $member_id2 = ($user_id == $member_id1) ? $member_id2 : $member_id1;
                    $stmt->bind_param("iis", $user_id, $member_id2, $new_relation);
                    if ($stmt->execute()) {
                        echo "success";
                    } else {
                        echo "error";
                    }
                    $stmt->close();
                } else {
                    echo "error";
                }
            } else {
                echo "No change in relation.";
            }
        } else {
            echo "error";
        }
    }
    exit();
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

function changeConnection(connectionID, currentRelation) {
    const newRelation = prompt(
        "Change to:\n1. Friend\n2. Family\n3. Colleague",
        currentRelation
    );

    if (newRelation !== currentRelation) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "connections.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                if (xhr.responseText === "success") {
                    alert("Connection changed successfully!");
                    location.reload();
                } else {
                    alert("Error: Unable to change the connection.");
                }
            }
        };
        xhr.send("change_connection=true&connection_id=" + connectionID + "&new_relation=" + newRelation);
    }
}
</script>

<?php
// Helper function to display connections
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
        } elseif ($type === 'Friends' || $type === 'Family' || $type === 'Colleagues') {
            // Here we assume the other status is 'Confirmed', and 'Change' button is available
            echo '<button class="change-connection-btn" onclick="changeConnection(' . $connection['ConnectionID'] . ', \'' . htmlspecialchars($connection['Relation']) . '\')">Change</button>';
        }

        echo '</div></div>';
    }
}

?>

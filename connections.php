<?php
session_start();
include('config.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle AJAX requests for canceling, accepting, changing, blocking, or unblocking a connection
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
    } elseif (isset($_POST['block_connection'])) {
       // Block the connection
    // Step 1: Fetch the current relation and member IDs
    $query = "
        SELECT MemberID1, MemberID2, Relation
        FROM Connections
        WHERE ConnectionID = ?
    ";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $connection_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $member_id1 = $row['MemberID1'];
            $member_id2 = $row['MemberID2'];
            $relation = $row['Relation'];
            $stmt->close();

            // Step 2: Delete the old connection record
            $query = "
                DELETE FROM Connections
                WHERE ConnectionID = ?
            ";
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("i", $connection_id);
                if ($stmt->execute()) {
                    $stmt->close();

                    // Step 3: Insert a new record with the user as MemberID1, blocked person as MemberID2, and status as 'Blocked'
                    $query = "
                        INSERT INTO Connections (MemberID1, MemberID2, Status, Relation)
                        VALUES (?, ?, 'Blocked', ?)
                    ";
                    if ($stmt = $conn->prepare($query)) {
                        // Ensure MemberID1 is the user and MemberID2 is the blocked person
                        $blocked_member_id = ($user_id == $member_id1) ? $member_id2 : $member_id1;
                        $stmt->bind_param("iis", $user_id, $blocked_member_id, $relation);
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
                    echo "error";
                }
            } else {
                echo "error";
            }
        } else {
            echo "error"; // No matching record found
        }
    } else {
        echo "error";
    }
    } elseif (isset($_POST['unblock_connection'])) {
        // Unblock the connection
        $query = "
            UPDATE Connections
            SET Status = 'Requested'
            WHERE ConnectionID = ? 
            AND (MemberID1 = ? OR MemberID2 = ?)
            AND Status = 'Blocked'
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
    }
    exit();
}

// Fetch connections for Confirmed and Requested statuses
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
    AND c.Status IN ('Requested', 'Confirmed')
    ORDER BY c.Relation, m.FirstName
";

$result = $conn->query($query);
$connections = [
    'Friend' => ['Requested' => [], 'Confirmed' => [], 'Blocked' => []], 
    'Family' => ['Requested' => [], 'Confirmed' => [], 'Blocked' => []], 
    'Colleague' => ['Requested' => [], 'Confirmed' => [], 'Blocked' => []]
];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $connections[$row['Relation']][$row['Status']][] = $row;
    }
}

// Fetch connections specifically for Blocked status
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
    JOIN Members m ON m.MemberID = c.MemberID2
    WHERE c.MemberID1 = $user_id AND c.Status = 'Blocked'
    ORDER BY c.Relation, m.FirstName
";

$result = $conn->query($query);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $connections[$row['Relation']]['Blocked'][] = $row;
    }
}

include('includes/header.php');
?>

<main>
    <section class="connections-section">
        <h1>Your Connections</h1>

        <div class="tabs">
            <div>
                <button class="tab-button active" onclick="showTab('friends')">Friends</button>
                <button class="tab-button" onclick="showTab('family')">Family</button>
                <button class="tab-button" onclick="showTab('colleagues')">Colleagues</button>
            </div>
            <button class="add-connection-btn" onclick="window.location.href='add_connection.php'">Add Connection</button>
        </div>

        <div id="friends" class="tab-content active">
            <h2>Requested</h2>
            <?php displayConnections($connections['Friend']['Requested'], 'Requested'); ?>

            <h2>Friends</h2>
            <?php displayConnections($connections['Friend']['Confirmed'], 'Friends'); ?>

            <h2>Blocked</h2>
            <?php displayConnections($connections['Friend']['Blocked'], 'Blocked'); ?>
        </div>

        <div id="family" class="tab-content">
            <h2>Requested</h2>
            <?php displayConnections($connections['Family']['Requested'], 'Requested'); ?>

            <h2>Family</h2>
            <?php displayConnections($connections['Family']['Confirmed'], 'Family'); ?>

            <h2>Blocked</h2>
            <?php displayConnections($connections['Family']['Blocked'], 'Blocked'); ?>
        </div>

        <div id="colleagues" class="tab-content">
            <h2>Requested</h2>
            <?php displayConnections($connections['Colleague']['Requested'], 'Requested'); ?>

            <h2>Colleagues</h2>
            <?php displayConnections($connections['Colleague']['Confirmed'], 'Colleagues'); ?>

            <h2>Blocked</h2>
            <?php displayConnections($connections['Colleague']['Blocked'], 'Blocked'); ?>
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
    // Create and append the modal with a dropdown for selecting the new relation
    let modalContent = `
        <div id="changeModal" class="modal">
            <div class="modal-content">
                <h3>Select a new relation type:</h3>
                <select id="relationDropdown">
                    <option value="Friend" ${currentRelation === 'Friend' ? 'selected' : ''}>Friend</option>
                    <option value="Family" ${currentRelation === 'Family' ? 'selected' : ''}>Family</option>
                    <option value="Colleague" ${currentRelation === 'Colleague' ? 'selected' : ''}>Colleague</option>
                </select>
                <div class="modal-buttons">
                    <button onclick="confirmChange(${connectionID}, '${currentRelation}')">OK</button>
                    <button class="cancel" onclick="cancelChange()">Cancel</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalContent);

    // Show the modal (overlay + content)
    document.getElementById('changeModal').style.display = 'block';

    // Function to handle the confirmation of the change
    window.confirmChange = function (connectionID, currentRelation) {
        let newRelation = document.getElementById('relationDropdown').value;

        // Close the modal
        document.getElementById('changeModal').style.display = 'none';

        // If the relation is different, send an AJAX request
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
    };

    // Function to handle the cancel action (closes the modal)
    window.cancelChange = function () {
        document.getElementById('changeModal').style.display = 'none';
    };
}

function blockConnection(connectionID) {
    if (confirm("Are you sure you want to block this connection?")) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "connections.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                if (xhr.responseText === "success") {
                    alert("Connection blocked successfully");
                    location.reload();
                } else {
                    alert("Error: Unable to block the connection.");
                }
            }
        };
        xhr.send("block_connection=true&connection_id=" + connectionID);
    }
}

function unblockConnection(connectionID) {
    if (confirm("Are you sure you want to unblock this connection?")) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "connections.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                if (xhr.responseText === "success") {
                    alert("Connection unblocked successfully");
                    location.reload();
                } else {
                    alert("Error: Unable to unblock the connection.");
                }
            }
        };
        xhr.send("unblock_connection=true&connection_id=" + connectionID);
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
                echo '<button class="accept-connection-btn" style="background-color: green; margin-left: 0.5rem" onclick="acceptConnection(' . $connection['ConnectionID'] . ')">Accept</button>';
            }
        } elseif ($type === 'Blocked') {
            echo '<button class="unblock-connection-btn" style="background-color: orange;" onclick="unblockConnection(' . $connection['ConnectionID'] . ')">Unblock</button>';
        } elseif ($type === 'Friends' || $type === 'Family' || $type === 'Colleagues') {
            echo '<button class="change-connection-btn" onclick="changeConnection(' . $connection['ConnectionID'] . ', \'' . htmlspecialchars($connection['Relation']) . '\')">Change Relation</button>';
            echo '<button class="block-connection-btn" style="background-color: red; margin-left:10px" onclick="blockConnection(' . $connection['ConnectionID'] . ')">Block</button>';
            
        }

        echo '</div></div>';
    }
}

?>

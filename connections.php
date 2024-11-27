<?php
session_start();
include('config.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch connections
$query = "
    SELECT 
        c.Relation, 
        m.FirstName, 
        m.LastName, 
        m.Username, 
        c.ConnectionID
    FROM Connections c
    JOIN Members m ON (m.MemberID = IF(c.MemberID1 = $user_id, c.MemberID2, c.MemberID1))
    WHERE (c.MemberID1 = $user_id OR c.MemberID2 = $user_id) AND c.Status = 'Confirmed'
    ORDER BY c.Relation, m.FirstName
";

$result = $conn->query($query);
$connections = ['Friend' => [], 'Family' => [], 'Colleague' => []];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $connections[$row['Relation']][] = $row;
    }
}

include('includes/header.php');
?>

<main>
    <section class="connections-section">
        <h1>Your Connections</h1>

        <!-- Tabs for different connection types -->
        <div class="tabs">
            <button class="tab-button active" onclick="showTab('friends')">Friends</button>
            <button class="tab-button" onclick="showTab('family')">Family</button>
            <button class="tab-button" onclick="showTab('colleagues')">Colleagues</button>
        </div>

        <!-- Tab content -->
        <div id="friends" class="tab-content active">
            <?php displayConnections($connections['Friend'], 'Friends'); ?>
        </div>
        <div id="family" class="tab-content">
            <?php displayConnections($connections['Family'], 'Family'); ?>
        </div>
        <div id="colleagues" class="tab-content">
            <?php displayConnections($connections['Colleague'], 'Colleagues'); ?>
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
                    <strong>' . htmlspecialchars($connection['FirstName']) . ' ' . htmlspecialchars($connection['LastName']) . '</strong>
                    <br>
                    <span style="color: grey;">' . htmlspecialchars($connection['Username']) . '</span>
                </div>
                <div class="connection-action">
                    <form class="change-connection-form" action="change_connection.php" method="POST">
                        <input type="hidden" name="connection_id" value="' . $connection['ConnectionID'] . '">
                        <button type="submit" class="change-button">Change Connection</button>
                    </form>
                </div>
            </div>
        ';
    }
}
?>

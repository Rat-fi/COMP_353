<?php
// Start session and include the configuration file
session_start();
include('config.php'); 

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // This should come from the login process

// Fetch Sent Gifts
$sentGiftsQuery = "SELECT g.GiftsId, g.GiftName, g.ImageLink, g.Timestamp, r.FirstName AS ReceiverFirstName, r.LastName AS ReceiverLastName, g.Status
                   FROM Gifts g
                   JOIN Members r ON g.ReceiverID = r.MemberID
                   WHERE g.SenderID = $user_id";
$sentGiftsResult = mysqli_query($conn, $sentGiftsQuery);

// Fetch Received Gifts
$receivedGiftsQuery = "SELECT g.GiftsId, g.GiftName, g.ImageLink, g.Status, g.Timestamp, s.FirstName AS SenderFirstName, s.LastName AS SenderLastName
                       FROM Gifts g
                       JOIN Members s ON g.SenderID = s.MemberID
                       WHERE g.ReceiverID = $user_id";
$receivedGiftsResult = mysqli_query($conn, $receivedGiftsQuery);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gifts Exchange</title>
    <style>
        /* Basic reset for margin and padding */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        /* Gift card styling */
        .gift-card {
            background-color: white;
            padding: 20px;
            margin: 10px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .gift-card:hover {
            transform: scale(0.995);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .gift-card img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            max-width: 300px;
            margin-bottom: 15px;
        }

        .gift-card h3 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .gift-card p {
            font-size: 14px;
            margin-bottom: 10px;
        }

        /* Action button styling */
        .gift-card button {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            margin-right: 10px;
            cursor: pointer;
        }

        .gift-card button:hover {
            opacity: 0.8;
        }

        .gift-card .accept-btn {
            background-color: #4CAF50;
            color: white;
        }

        .gift-card .reject-btn {
            background-color: #f44336;
            color: white;
        }

        .gift-card .open-btn {
            background-color: #FFEB3B;
            color: black;
        }

        .gift-card .hidden-img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            max-width: 300px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            padding-left: 20px;
        }

        .gift-card .status {
            font-weight: bold;
        }
    </style>
    <script>
        function showTab(tabName) {
            var tabs = document.querySelectorAll('.tab-content');
            var buttons = document.querySelectorAll('.tab-button');
            tabs.forEach(function(tab) {
                tab.classList.remove('active');
            });
            buttons.forEach(function(button) {
                button.classList.remove('active');
            });

            document.getElementById(tabName).classList.add('active');
            document.querySelector('#' + tabName + 'Tab').classList.add('active');
        }

        function updateGiftStatus(giftId, status) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "updateGiftStatus.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert('Status updated!');
                    location.reload(); // Reload the page to reflect changes
                }
            };
            xhr.send("giftId=" + giftId + "&status=" + status);
        }
    </script>
</head>
<body>

<?php include('./includes/header.php'); ?> <!-- Include your header for navigation -->
<div style="text-align:center">
    <h2>Gifts Exchange</h2>
</div>
<div class="tabs" style="justify-content: center;">
    <button class="tab-button active" id="sentTab" onclick="showTab('sent')">Sent Gifts</button>
    <button class="tab-button" id="receivedTab" onclick="showTab('received')">Received Gifts</button>
</div>

<div id="sent" class="tab-content active">
    <h2>Sent Gifts</h2>
    <?php if (mysqli_num_rows($sentGiftsResult) > 0) { 
        while ($row = mysqli_fetch_assoc($sentGiftsResult)) { ?>
            <div class="gift-card">
                <h3>To: <?php echo $row['ReceiverFirstName'] . ' ' . $row['ReceiverLastName']; ?></h3>
                <p><strong>Gift Name:</strong> <?php echo $row['GiftName']; ?></p>
                <p><strong>Date Sent:</strong> <?php echo $row['Timestamp']; ?></p>
                <p><strong>Status:</strong> <?php echo $row['Status']; ?></p>
                <?php if ($row['ImageLink']) { ?>
                    <img src="<?php echo $row['ImageLink']; ?>" alt="Gift Image">
                <?php } ?>
            </div>
    <?php } } else { ?>
        <p>No sent gifts found.</p>
    <?php } ?>
</div>

<div id="received" class="tab-content">
    <h2>Received Gifts</h2>
    <?php 
    $hiddenGiftImage = "https://mothercityliquor.co.za/cdn/shop/files/Untitleddesign_1785d5a8-ee29-4cd5-940c-ff4068f8ec53_1200x1200.jpg?v=1695194352";
    
    if (mysqli_num_rows($receivedGiftsResult) > 0) { 
        while ($row = mysqli_fetch_assoc($receivedGiftsResult)) { ?>
            <div class="gift-card">
                <h3>From: <?php echo $row['SenderFirstName'] . ' ' . $row['SenderLastName']; ?></h3>
                <p><strong>Gift Name:</strong> <?php echo $row['GiftName']; ?></p>
                <p><strong>Date Received:</strong> <?php echo $row['Timestamp']; ?></p>
                <?php if ($row['Status'] === 'Opened') { ?>
                    <p><strong>Status:</strong> Opened</p>
                    <img src="<?php echo $row['ImageLink']; ?>" alt="Gift Image">
                <?php } else { ?>
                    <p><strong>Status:</strong> <?php echo $row['Status']; ?></p>
                    <?php echo getReceivedGiftButtons($row['GiftsId'], $row['Status']); ?>
                    <img src="<?php echo $hiddenGiftImage; ?>" alt="Hidden Gift Image" class="hidden-img">

                <?php } ?>
            </div>
    <?php } } else { ?>
        <p>No received gifts found.</p>
    <?php } ?>
</div>

<?php
// Function to display action buttons based on gift status
function getReceivedGiftButtons($giftId, $status) {
    if ($status === 'Pending') {
        return '
            <button class="accept-btn" onclick="updateGiftStatus(' . $giftId . ', \'Approved\')">Accept</button>
            <button class="reject-btn" onclick="updateGiftStatus(' . $giftId . ', \'Rejected\')">Reject</button>';
    } elseif ($status === 'Approved') {
        return '<button class="open-btn" onclick="updateGiftStatus(' . $giftId . ', \'Opened\')">Open</button>';
    } elseif ($status === 'Rejected') {
        return '<p class="status">Status: Rejected</p>';
    } elseif ($status === 'Opened') {
        return '<p class="status">Status: Opened</p>';
    }
}
?>

</body>
</html>
<?php include('./includes/footer.php'); ?>

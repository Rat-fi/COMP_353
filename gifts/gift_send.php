<?php
// Start session and include the configuration file
session_start();
include('../config.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // This should come from the login process

// Fetch connections of the user
$connectionsQuery = "
    SELECT 
        c.ConnectionID, 
        c.MemberID1, 
        c.MemberID2,
        IF(c.MemberID1 = $user_id, m2.FirstName, m1.FirstName) AS ReceiverFirstName,
        IF(c.MemberID1 = $user_id, m2.LastName, m1.LastName) AS ReceiverLastName,
        IF(c.MemberID1 = $user_id, c.MemberID2, c.MemberID1) AS ReceiverID
    FROM Connections c
    JOIN Members m1 ON c.MemberID1 = m1.MemberID
    JOIN Members m2 ON c.MemberID2 = m2.MemberID
    WHERE (c.MemberID1 = $user_id OR c.MemberID2 = $user_id)
    AND c.Status = 'Confirmed'";  // Only show confirmed connections

$connectionsResult = mysqli_query($conn, $connectionsQuery);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sendGift'])) {
    $giftName = mysqli_real_escape_string($conn, $_POST['giftName']);
    $receiverID = $_POST['receiverID'];
    $imageLink = mysqli_real_escape_string($conn, $_POST['imageLink']);

    // Insert the new gift into the Gifts table
    $insertGiftQuery = "INSERT INTO Gifts (GiftName, SenderID, ReceiverID, ImageLink, Status)
                        VALUES ('$giftName', $user_id, $receiverID, '$imageLink', 'Pending')";

    if (mysqli_query($conn, $insertGiftQuery)) {
        $message = "Gift sent successfully!";
        $redirect = true; // Flag for redirect
    } else {
        $message = "Error sending gift: " . mysqli_error($conn);
        $redirect = false; // No redirect on error
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send a Gift</title>
    <style>
        /* Styling for the form and connections table */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        .gift-form, .connection-list {
            margin: 20px 0;
        }

        .gift-form input, .gift-form select {
            padding: 10px;
            margin: 10px 0;
            width: 100%;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .gift-form button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .gift-form button:hover {
            background-color: #45a049;
        }

        .connection-list table {
            width: 100%;
            border-collapse: collapse;
        }

        .connection-list th, .connection-list td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .connection-list th {
            background-color: #f2f2f2;
        }

        .message {
            color: green;
            font-weight: bold;
        }

        .error {
            color: red;
            font-weight: bold;
        }
    </style>

    <script>
        <?php if (isset($redirect) && $redirect === true) { ?>
            window.location.href = "gifts.php"; 
        <?php } ?>
    </script>
</head>
<body>

<?php include('../includes/header.php'); ?>

<div style="text-align:center">
    <h2>Send a Gift</h2>
    <?php
    if (isset($message)) {
        echo "<p class='message'>$message</p>";
    }
    ?>
</div>

<div class="gift-form">
    <form action="gift_send.php" method="POST">
        <label for="giftName">Gift Name:</label>
        <input type="text" id="giftName" name="giftName" required>

        <label for="imageLink">Gift Image URL:</label>
        <input type="text" id="imageLink" name="imageLink">

        <label for="receiverID">Select Connection:</label>
        <select id="receiverID" name="receiverID" required>
            <option value="">-- Select a connection --</option>
            <?php
            if (mysqli_num_rows($connectionsResult) > 0) {
                while ($row = mysqli_fetch_assoc($connectionsResult)) {
                    echo "<option value='" . $row['ReceiverID'] . "'>" . $row['ReceiverFirstName'] . " " . $row['ReceiverLastName'] . "</option>";
                }
            } else {
                echo "<option value='' disabled>No confirmed connections found</option>";
            }
            ?>
        </select>

        <button type="submit" name="sendGift">Send Gift</button>
    </form>
</div>

</body>
</html>

<?php include('../includes/footer.php'); ?>

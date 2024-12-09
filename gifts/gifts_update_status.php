<?php
// Start the session and include the database connection
session_start();
include('../config.php');

// Ensure the giftId and status are passed in the POST request
if (isset($_POST['giftId']) && isset($_POST['status'])) {
    $giftId = $_POST['giftId'];
    $status = $_POST['status'];

    // Update the status of the gift in the database
    $updateQuery = "UPDATE Gifts SET Status = '$status' WHERE GiftsId = $giftId";
    if (mysqli_query($conn, $updateQuery)) {
        echo "Success"; // Optional success message
    } else {
        echo "Error updating status: " . mysqli_error($conn); // Optional error message
    }
}
?>

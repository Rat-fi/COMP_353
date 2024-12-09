<?php
session_start();
if (!isset($_SESSION['privilege']) || $_SESSION['privilege'] !== 'Administrator') {
    echo "<div style='display: flex; justify-content: center; align-items: center; height: 100vh;'>
            <h1>Access Denied Page For Non Admins.</h1>
          </div>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);  // Hashing the password
    $privilege = $_POST['privilege'];

    include('../config.php');
    
    $stmt = $conn->prepare("INSERT INTO Members (Username, Password, Email, FirstName, LastName, Privilege) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $username, $password, $email, $first_name, $last_name, $privilege);
    if ($stmt->execute()) {
        header("Location: admin.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
<?php include('../includes/header.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Member</title>
    <style>
        body{
            text-align: center;
        }
        .form-group{
            margin-top:10px;
            margin-bottom: 20px;
        }

        form{
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        button{
            align-self: end;
        }

        input{
            margin-top:10px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Add New Member</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" class="form-control" id="first_name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" class="form-control" id="last_name" name="last_name">
            </div>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="privilege">Privilege:</label>
                <select class="form-control" id="privilege" name="privilege">
                    <option value="Junior">Junior</option>
                    <option value="Senior">Senior</option>
                    <option value="Administrator">Administrator</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Add Member</button>
        </form>
    </div>
</body>
</html>
<?php include('../includes/footer.php'); ?>

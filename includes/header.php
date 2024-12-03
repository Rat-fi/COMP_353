<?php
// Start the session if it hasn't already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to COSN</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="index.php">COSN</a>
            </div>
            <div style="align-self: center;">
                <?php if (isset($_SESSION['username'])): ?>
                    <p><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <?php endif; ?>
            </div>
            <ul class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php">
                            <p>Home</p>
                        </a></li>
                    <li><a href="connections.php">
                            <p>My Connections</p>
                        </a></li>
                    <li><a href="chat_list.php">
                            <p>Chat</p>
                        </a></li>
                    <?php if (isset($_SESSION['privilege']) && $_SESSION['privilege'] === 'Administrator'): ?>
                        <li><a href="admin.php">
                                <p>Admin</p>
                            </a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">
                            <p>Logout</p>
                        </a></li>
                <?php else: ?>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
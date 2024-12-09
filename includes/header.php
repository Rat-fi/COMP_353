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
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>
    <header>
        <nav style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 1rem; background-color: #212e54; border-bottom: 1px solid #ddd; height: 50px">
            <!-- Logo on the left -->
            <div style="flex: 1; text-align: left;">
                <a style="text-decoration: none; font-weight: bold; font-size: 1.5rem; color: white;">COSN</a>
            </div>

            <!-- Username in the center -->
            <div style="flex: 1; text-align: center;">
                <?php if (isset($_SESSION['username'])): ?>
                    <p style="margin: 0; font-size: 1rem; font-weight: bold; color: white;">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Navigation buttons on the right -->
            <ul style="flex: 1; list-style-type: none; margin: 0; padding: 0; display: flex; justify-content: flex-end; gap: 1rem;">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="/dashboard.php" class="link-button">Home</a></li>
                    <li><a href="../connections/connections.php" class="link-button">Connections</a></li>
                    <?php if (isset($_SESSION['privilege']) && $_SESSION['privilege'] === 'Administrator'): ?>
                        <li><a href="../admin/admin.php" class="link-button">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="../gifts/gifts.php" class="link-button">Gifts</a></li>
                    <li><a href="/logout.php" class="link-button">Logout</a></li>
                <?php else: ?>
                    <li><a href="/register.php" class="link-button">Register</a></li>
                    <li><a href="/login.php" class="link-button">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
</body>

</html>

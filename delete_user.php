<?php
// Include config file for database connection
include 'config.php';

// Check if ID is set
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Delete user from database
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("s", $id);
    if ($stmt->execute()) {
        echo "<p>User deleted successfully.</p>";
    } else {
        echo "<p>Error deleting user: " . $stmt->error . "</p>";
    }
    $stmt->close();

    // Redirect back to the index page
    header("Location: index.php");
    exit;
} else {
    echo "<p>No user ID specified for deletion.</p>";
}
?>

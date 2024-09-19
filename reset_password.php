<?php
include("connection.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newPassword = $_POST["new_password"];
    $confirmPassword = $_POST["confirm_password"];

    if ($newPassword !== $confirmPassword) {
        echo "Passwords do not match. Please try again.";
        exit;
    }

    $email = $_SESSION["reset_email"];
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $query = "UPDATE users SET password = ? WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $hashedPassword, $email);

    if ($stmt->execute()) {
        $successMessage = "Your password has been successfully updated. For added security, please remember to keep it confidential and avoid sharing it with anyone.";
        echo "<div style='text-align:center;color:green;'>$successMessage you can login now...</div>";
        echo '<audio autoplay><source src="sounds/createpassword.mp3" type="audio/mpeg"></audio>';
        echo "<script>setTimeout(function(){ window.location.href = 'login.html'; }, 11000);</script>";
    } else {
        echo "Error updating password. Please try again.";
    }

    $stmt->close();
}
?>

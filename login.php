<?php
include("connection.php");
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $query = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION["user_id"] = $user['user_id']; 
            $_SESSION["email"] = $email;
          
            echo "<div style='text-align:center;color:green;'>got it!, so you may proceed to your dashboard, upload your image for your profile, and book a service, inside the dashboard there are buttons you may click each of them......</div>";
            echo '<audio autoplay><source src="sounds/messageforlogin.mp3" type="audio/mpeg"></audio>';

            echo "<script>setTimeout(function() { window.location.href = 'user_dashboard.php'; }, 12000);</script>";
            exit;
        } else {
            echo "Invalid email or password. Please try again.";
        }
    } else {
        echo "Invalid email or password. Please try again.";
    }

    $stmt->close();
}
?>

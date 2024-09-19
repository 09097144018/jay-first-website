<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $verificationCode = $_POST["verification_code"];

    if ($verificationCode == $_SESSION["verification_code"]) {
        echo "<div style='text-align:center;color:green;'>The confirmation code is correct. Redirecting to create a new, uniquely strong password. Make sure it's memorable yet robust to enhance security......</div>";
        echo '<audio autoplay><source src="sounds/afterconfirmation.mp3" type="audio/mpeg"></audio>';
        echo "<script>setTimeout(function(){ window.location.href = 'create_new_password.php'; }, 12000);</script>";
    } else {
        echo "Invalid verification code. Please try again.";
    }
}
?>

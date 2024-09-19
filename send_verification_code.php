<?php
include("connection.php");
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];

    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
  
    if ($result->num_rows == 1) {
        $verificationCode = mt_rand(100000, 999999);
        $_SESSION["verification_code"] = $verificationCode;
        $_SESSION["reset_email"] = $email;

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ibanezjayniel913@gmail.com';
            $mail->Password = 'cbtl nfux wdef ijcs';
            $mail->Port = 465;
            $mail->SMTPSecure = 'ssl';

            $mail->setFrom('ibanezjayniel913@gmail.com', 'Dental Clinic Reservation System');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Verification Code';
            $mail->Body = "Hello <br><br>Your verification code is: $verificationCode";

            $mail->send(); 
            echo "<div style='text-align:center;color:green;'>I send you a confirmation code to your email, view it, and after you receive it, please come back here and enter your confirmation code......</div>";
            echo '<audio autoplay><source src="sounds/afterforgotpassword.mp3" type="audio/mpeg"></audio>';
            echo "<script>setTimeout(function() { window.location.href = 'verify_code.html'; }, 10000);</script>";
        } catch (Exception $e) {
            echo "Error sending email: {$mail->ErrorInfo}";
        }
    } else {
        echo "Email address not found. Please try again.";
    }

    $stmt->close();
}
?>

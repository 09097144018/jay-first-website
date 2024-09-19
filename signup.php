<?php
include("connection.php");
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = $_POST["fullname"]; 
    $gender = $_POST["gender"];
    $age = $_POST["age"];
    $address = $_POST["address"];
    $phone = $_POST["phone"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    
    $checkEmailQuery = "SELECT email FROM users WHERE email = ?";
    $checkEmailStmt = $conn->prepare($checkEmailQuery);
    $checkEmailStmt->bind_param("s", $email);
    $checkEmailStmt->execute();
    $checkEmailStmt->store_result();
    if ($checkEmailStmt->num_rows > 0) {
        echo "Email address already exists. Please use a different email.";
        exit;
    }
    $checkEmailStmt->close();

    $verificationCode = mt_rand(100000, 999999);

    $userData = array(
        "fullname" => $fullname,
        "gender" => $gender,
        "age" => $age,
        "address" => $address,
        "phone" => $phone,
        "email" => $email,
        "password" => $hashedPassword,
        "verification_code" => $verificationCode
    );

    
    $jsonFile = 'userdata.json';
    $existingData = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
    $existingData[] = $userData;
    file_put_contents($jsonFile, json_encode($existingData));

    
    $mail = new PHPMailer(true);
    try {
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ibanezjayniel913@gmail.com';
        $mail->Password = 'qzrs yikp leul bfgq';
        $mail->Port = 465;
        $mail->SMTPSecure = 'ssl';

        
        $mail->setFrom('ibanezjayniel913@gmail.com', 'Dental Clicnic Reservation System');
        $mail->addAddress($email, $fullname);
        $mail->isHTML(true);
        $mail->Subject = 'Verification Code';
        $mail->Body = "Hello $fullname,<br><br>Your verification code is: $verificationCode";

        
        $mail->send();
        echo "<div style='text-align:center;color:green;'>Thank you for signing up, dear user. Please verify your account. The verification code will be sent to you via email to grant permission to log in.......</div>";
        echo '<audio autoplay><source src="sounds/aftersignup.mp3" type="audio/mpeg"></audio>';
    } catch (Exception $e) {
        echo "Error sending email: {$mail->ErrorInfo}";
    }

    header("refresh:12; url=verification.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
</head>
<body>
    <h2>Registration</h2>
    <form action="" method="post">
        <label for="fullname">Full Name:</label><br>
        <input type="text" id="fullname" name="fullname" required><br><br>
        
        <input type="submit" value="Submit">
    </form>
</body>
</html>

<?php
include("connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $verification_code = mysqli_real_escape_string($conn, $_POST["verification_code"]);

    
    $jsonFile = 'userdata.json';
    $jsonData = file_get_contents($jsonFile);
    $userDataArray = json_decode($jsonData, true);

    $userFound = false;
    $userDataIndex = null;

    
    foreach ($userDataArray as $index => $userData) {
        if ($userData['verification_code'] == $verification_code) {
            $userFound = true;
            $userDataIndex = $index;
            break;
        }
    }

    if ($userFound) {
        
        $insertQuery = "INSERT INTO users (fullname, email, password, phone, address, gender, age, verification_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);

        if (!$insertStmt) {
            die("Error in prepare statement: " . $conn->error);
        }

        $userData = $userDataArray[$userDataIndex];
        $insertStmt->bind_param("ssssssss", $userData['fullname'], $userData['email'], $userData['password'], $userData['phone'], $userData['address'],  $userData['gender'],  $userData['age'], $userData['verification_code']);

        if ($insertStmt->execute()) {
            echo "<div style='text-align:center;color:green;'>Congratulations! Your account is already verified. You may proceed to log in......</div>";
            echo '<audio autoplay><source src="sounds/afterverification.mp3" type="audio/mpeg"></audio>';
            
            unset($userDataArray[$userDataIndex]);
            file_put_contents($jsonFile, json_encode(array_values($userDataArray)));

            
            header("refresh:8; url=login.html");
            exit;
        } else {
            echo "Error: " . $insertStmt->error;
        }

        $insertStmt->close();
    } else {
        echo "Invalid verification code. Please try again.";
    }
}
?>

<?php
session_start();
if (!isset($_SESSION["verification_code"])) {
    header("Location: forgot_password.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $enteredCode = $_POST["confirmation_code"];
    $expectedCode = $_SESSION["confirmation_code"];

    if ($enteredCode == $expectedCode) {
        echo "<div style='text-align:center;color:green;'>The confirmation code is correct. Redirecting to create a new, uniquely strong password. Make sure it's memorable yet robust to enhance security......</div>";
        echo '<audio autoplay><source src="sounds/afterconfirmation.mp3" type="audio/mpeg"></audio>';
        echo "<script>setTimeout(function(){ window.location.href = 'create_password.php'; }, 12000);</script>";
        exit; 
    } else {
        $error = "Invalid confirmation code. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .container {
            max-width: 400px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            font-weight: bold;
            color: #666;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Confirmation</h2>
        <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <label for="confirmation_code">Enter confirmation code:</label><br>
            <input type="text" id="confirmation_code" name="confirmation_code" required><br><br>
            <input type="submit" value="Submit">
        </form>
    </div>
</body>
</html>

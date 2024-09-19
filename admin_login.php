<?php
include("connection.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST["username"]);
    $password = mysqli_real_escape_string($conn, $_POST["password"]);

    if ($username === "admin" && $password === "dental123") {
        $_SESSION['admin'] = true;
        header("location: admin_dashboard.php");
        exit();
    } else {
        echo "Incorrect username or password. Please try again.";
    }
}

//$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="css/index.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            max-width: 100%;
        }

        .container1 {
            position: fixed;
            left: 0;
            right: 0;
            background-color: tomato;
            padding: 0px;
        }

        .navigation {
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding-top: 5px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ccc;
        }

        .navigation a {
            text-decoration: none;
            color: white;
            margin: 0 10px;
            padding: 5px;
            outline: none;
        }

        .navigation a:hover {
            background-color: #ddd;
        }

        .login-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 400px; 
            margin: 100px auto; 
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .login-form label {
            display: block;
            margin-bottom: 10px;
        }

        .login-form input[type="text"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .login-form input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .login-form input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: red;
            margin-top: 10px;
            text-align: center;
        }

        .h1f{
           margin-left: 500px;
        }
    </style>
</head>
<body>
<div class="container1">
    <h1 class = "h1f">Dental Clinic Reservation System</h1>
    <div class="navigation">
        <a href="index.html">Home</a>
        <a href="services.html">Services</a>
        <a href="contact.html">Contact</a>
    </div>
</div><br><br><br>
<div class="login-container">
    <h2>Admin</h2>
    <form class="login-form" action="" method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" placeholder="Enter your username" required><br><br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" placeholder="Enter your password"  required><br><br>
        <input type="submit" value="Login">
    </form>
    <?php if (isset($error)) { echo '<p class="error-message">' . $error . '</p>'; } ?>
</div>
</body>
</html>

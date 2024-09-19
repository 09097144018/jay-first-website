<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Password</title>
    <style>
     body{
        padding:0;
        margin:0;
        font-family: Arial, sans-serif;
     }

     .container1{
        display: flex;
        justify-content: center;
        margin-top: 0px;
        padding: 120px;
     }

     .container2{
       background-color: blue;
       padding: 70px 110px;
       border-radius: 10px;
       width: 30%;
       text-align: center;
        align-items: center;
        box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.3);
       
     }

     .container2 h2{
        font-size: 30px;
     }

     .container2 label{
        font-size: 20px;
        color: white;
     }

     .container2 .input{
        height: 29px;
        width: 100%;
     }
     .container2 .button{
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        color: white;
        background-color:  #4db6ac;
        font-size: 18px;
        transition: opacity 0.3s;


     }

     .container2 .button:hover{
        opacity: 0.8;
     }

    </style>
</head>
<body>
    <div class="container1">
        <div class="container2">
    <h2>Create New Password</h2>
    <form action="reset_password.php" method="post">
        <label for="new_password">New Password:</label><br>
        <input class= "input" type="password" id="new_password" name="new_password" required><br><br>
        <label for="confirm_password">Confirm Password:</label><br>
        <input class= "input" type="password" id="confirm_password" name="confirm_password" required><br><br>
        <input class= "button" type="submit" value="Submit">
    </form>
    </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body{
            margin: 0;
            padding: 0;
            height: 1000px;
            width: 100%;
            font-family: "Robito" ,Arial; 
        }
        .container1{
            background-color: gold;
            margin-bottom: 50px;
            background-color: tomato;
            margin-top: 0px;
            padding: 30px;

        }
        .container1 ul li{
         list-style: none;
         padding: 10px;
         border-radius: 5px;
         transition: background-color 0.3s;
        }
        .container1 ul li:hover{
          background-color: green;

        }
        .container1 ul li a {
            text-decoration: none;
            cursor: pointer;
            color: white;
        }
        .container1 ul {
            display: flex;
            justify-content: space-evenly;
        }
        .container2{
            display: flex;
            justify-content: center;
            flex-direction: column;
            align-items: center;
        }

        
        .container3{
            display: inline-block;
            background-color: aqua;
            padding:50px;
            border-radius: 10px;
            width: 500px;
            align-items: center;
            text-align: center;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.3);
        }
        .input{
            width: 80%;
            height: 40px;
        }
        .label{
            font-size: 20px;
            color: blue;
    
        }

        
        .button{
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background-color: purple;
            color: white;
            cursor: pointer;
            transition: opacity 0.3s;
            font-size: 15px;
        }
        .button:hover{
            opacity: .8;
        }

    </style>
</head>
<body>
    <div class="container1">
       <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="contact.html">Contact</a></li>
        <li><a href="services.html">Services</a></li>
       </ul>

    </div><br>
    <div class="container2">
        <div class="container3">
    <h2>Forgot Password</h2>
    <form action="send_verification_code.php" method="post">
        <label  class = "label" for="email">Email:</label><br>
        <input class = "input" type="email" id="email" name="email" required><br><br>
        <input class = "button" type="submit" value="Submit">
    </form>
    </div>
    </div>
</body>
</html>

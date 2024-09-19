<?php
include("connection.php");
require_once "config.php";
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;




if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION["user_id"];


$query = "SELECT user_id, fullname, email, profile_image, loyalty_points FROM users WHERE user_id=?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

function generateNextAvailableTime($conn, $booking_date) {
    $start_time = strtotime('9:30 AM');
    $end_time = strtotime('4:30 PM');
    $interval = 15 * 60; 

    $query = "SELECT booking_time FROM bookings WHERE booking_date = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $booking_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $booked_times = [];
    while ($row = $result->fetch_assoc()) {
        $booked_times[] = strtotime($row['booking_time']);
    }
    $stmt->close();

    for ($time = $start_time; $time <= $end_time; $time += $interval) {
        if (!in_array($time, $booked_times)) {
            return date('h:i A', $time);
        }
    }

    return null;
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["book_service"])) {
    $service_id = $_POST["service_id"];
    $booking_date = $_POST["booking_date"];
    $booking_time = generateNextAvailableTime($conn, $booking_date);

    if ($booking_time === null) {
        echo "The booking schedules are full. Please select another date.";
        echo "<meta http-equiv='refresh' content='3;url=user_dashboard.php'>";
        exit();
    }

    
    $user_query = "SELECT fullname, email FROM users WHERE user_id=?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    $user_name = $user_data['fullname'];
    $user_email = $user_data['email'];
    $stmt->close();

    
    $service_query = "SELECT service_name, price FROM services WHERE service_id=?";
    $stmt = $conn->prepare($service_query);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $service_info = $result->fetch_assoc();

    if ($service_info) {
        $service_name = $service_info['service_name'];
        $price = $service_info['price'];

      
        $booking_query = "INSERT INTO bookings (user_id, user_name, service_id, service_name, price, booking_date, booking_time) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($booking_query);
        $stmt->bind_param("isissss", $user_id, $user_name, $service_id, $service_name, $price, $booking_date, $booking_time);
        $stmt->execute();
        $stmt->close();

        
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ibanezjayniel913@gmail.com';
            $mail->Password = 'qzrs yikp leul bfgq';
            $mail->Port = 465;
            $mail->SMTPSecure = 'ssl';

            $mail->setFrom('ibanezjayniel913@gmail.com', 'Dental Clinic Reservation System');
            $mail->addAddress($user_email, $user_name);

            $mail->isHTML(true);
            $mail->Subject = 'Booking Confirmation';
            $mail->Body    = "<p>Dear {$user_name},</p>
                              <p>Thank you for booking a service with us. Here are your booking details:</p>
                              <p>Service Name: {$service_name}</p>
                              <p>Price: ₱{$price}</p>
                              <p>Date: {$booking_date}</p>
                              <p>Time: {$booking_time}</p>
                              <p>We look forward to seeing you!</p>";

            $mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

        header("Location: user_dashboard.php");
        exit();
    } else {
        echo "Error: Service information not found.";
        exit();
    }
}

// Handle profile image upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_image"])) {
    $timestamp = time();
    $target_directory = "profile_images/";

    // Ensure the target directory exists
    if (!file_exists($target_directory)) {
        mkdir($target_directory, 0777, true);
    }

    $target_file = $target_directory . $timestamp . "_" . basename($_FILES["profile_image"]["name"]);
    $upload_ok = 1;
    $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate file type and size
    if ($image_file_type != "jpg" && $image_file_type != "png" && $image_file_type != "jpeg" && $image_file_type != "gif") {
        $upload_ok = 0;
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    }

    if ($_FILES["profile_image"]["size"] > 1000000) {
        $upload_ok = 0;
        echo "Sorry, your file is too large.";
    }

    if ($upload_ok == 1) {
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $query = "UPDATE users SET profile_image = ? WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $target_file, $user_id);
            $stmt->execute();
            $stmt->close();
            header("Location: user_dashboard.php");
            exit();
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: login.html");
    exit();
}

// Fetch user details again to ensure they are up-to-date
$query = "SELECT * FROM users WHERE user_id=?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$user_profile_image = isset($user['profile_image']) ? $user['profile_image'] : 'default_profile_image.jpg';

// Fetch available services
$services_query = "SELECT * FROM services";
$services_result = $conn->query($services_query);
$services = [];
if ($services_result->num_rows > 0) {
    while ($row = $services_result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Fetch user bookings
$booking_query = "SELECT * FROM bookings WHERE user_id=?";
$stmt = $conn->prepare($booking_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

// Function to escape HTML for output
function escape($html) {
    return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

// Fetch paid bookings with user details
$paidBookingSql = "SELECT pb.*, u.fullname AS user_name FROM paid_bookings pb JOIN users u ON pb.user_id = u.user_id";
$paidBookingResult = $conn->query($paidBookingSql);


if (isset($_POST["action"]) && isset($_POST["booking_id"])) {
    $action = $_POST["action"];
    $booking_id = $_POST["booking_id"];

    // Check if the booking exists
    $check_booking_query = "SELECT * FROM bookings WHERE booking_id=?";
    $stmt = $conn->prepare($check_booking_query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
       
        if ($action === "disapprove") {
            // Delete from original bookings
            $delete_query = "DELETE FROM bookings WHERE booking_id=?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $stmt->close();

            // Redirect to the same page to prevent duplicate requests
            header("Location: user_dashboard.php");
            exit();
        }
    } 
}



?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 50000vh;
        }
        
        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #C5A3FF;
            padding-top: 20px;
        }
        
        .sidebar h2 {
            color: #fff;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }
        
        .sidebar ul li {
            padding: 10px 20px;
            border-bottom: 1px solid #555;
            color: #fff;
            transition: background-color 0.3s;
        }
        
        .sidebar ul li a {
            text-decoration: none;
            color: #fff;
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .sidebar ul li a i {
            margin-right: 10px;
        }
        
        .sidebar ul li a:hover {
            background-color: #555;
        }
        
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .user-dashboard {
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px; 
        }

        .user-dashboard h2 {
            color: #007bff;
            margin-bottom: 20px;
        }

        .profile-image {
            border-radius: 50%;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100px; 
            height: 100px; 
            object-fit: cover; 
        }
        .upload-form input[type="file"] {
            margin-top: 10px;
        }

        .upload-form input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .upload-form input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .dashboard-links {
            margin-top: 20px;
        }
        
        .dashboard-links li {
            color: #007bff;
            text-decoration: none;
            margin-right: 20px;
            transition: color 0.3s ease;
            list-style: none;
        }
        
        .dashboard-links a:hover {
            color: #0056b3;
        }
        
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: tomato;
            color: white;
        }

        tr:nth-child(even) {
            background-color:  #F5F5DC;
        }
         td{
            background-color: white;
         }
        tr:hover {
            background-color: #ddd;
        }

        th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: left;
        }

        .Select-image {
            color: white;
        }

        .submit-button {
            margin-top: 10px;
        }

        .servicescon {
            display: none;
            background-color: aqua;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .booking-container {
            display: none;
            background-color: lightyellow;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .booking-container h3 {
            color: #007bff;
            margin-bottom: 20px;
        }

        .bookings-container {
            display: none;
            background-color: #ffffcc;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .bookings-container h3 {
            color: #007bff;
            margin-bottom: 20px;
        }

        .bookings-container table {
            border-collapse: collapse;
            width: 100%;
        }

        .bookings-container th, .bookings-container td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .bookings-container th {
            background-color: #007bff;
            color: white;
        }

        .bookings-container tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .bookings-container tr:hover {
            background-color: #ddd;
        }
        .booking-form {
    max-width: 400px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 20px;
    text-align: center;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #333;
}

.form-group select,
.form-group input[type="date"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 16px;
}

.submit-button {
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    text-align: center;
}

.submit-button:hover {
    background-color: #0056b3;
}
#map-container {
        width: 100%;
        max-width: 640px; 
        margin: 20px auto; 
    }

    #map {
        width: 100%;
        height: 480px; 
        border: 2px solid #ddd; 
        border-radius: 8px; 
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); 
    }
    .paid-bookings {
            border-radius: 8px;
            margin-left: 10px;
            display: none;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .paid-bookings table {
            width: 100%;
            border-collapse: collapse;
        }
        .paid-bookings th, .paid-bookings td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .paid-bookings th {
            background-color: green;
        }
        .paid-bookings tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .bookings-container button {
    padding: 5px 10px;
    margin-right: 5px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

   .disapprove{
    background-color: red;
    color: white;
    cursor: pointer;
    transition: opacity o.3s;
   }

   .disapprove:hover{
    opacity: 0.8;
   }



    </style>
</head>
<body>
<div class="sidebar">
     <div class="user-dashboard">
            <h2>Welcome, <?php echo htmlspecialchars($user['fullname']); ?>!</h2>
            <img src="<?php echo htmlspecialchars($user_profile_image); ?>" alt="Profile Image" class="profile-image">
            <form action="user_dashboard.php" method="post" enctype="multipart/form-data" class="upload-form">
    <label  for="profile_image">Choose Profile Image:</label>
    <input  id = "file" type="file" name="profile_image" id="profile_image">
    <input  style = "display: none" id = "file-button" type="submit" value="Upload" class="submit-button">
</form>
<p>Loyalty Points: <?php echo htmlspecialchars($user['loyalty_points']); ?></p>

        </div>

    <ul>
        <li><a href="#" onclick="toggleBookingForm()"><i class="fas fa-calendar-plus"></i>Book Service</a></li>
        <li><a href="#" onclick="toggleServices()"><i class="fas fa-list"></i>Services</a></li> 
        <li><a href="#" onclick="toggleBookings()"><i class="fas fa-book"></i>Your Schedules</a></li>
        <li><a onclick="togglePaidBookings()"><i class="fas fa-money-bill-wave"></i>Receipt Paid Bookings</a></li>
        <li><a href="#" onclick="toggleMap()"><i class="fas fa-map-marked-alt"></i>Location</a></li>
        <li><a href="?logout=1" onclick="return confirm('Are you sure you want to logout?')"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
    </ul>
</div><hr>
<div class="content">
    <div id="services-container" class="servicescon">
        <h3>Available Services</h3>
        <table>
            <tr>
                <th>Service ID</th>
                <th>Service Name</th>
                <th>Price</th>
                <th>Description</th>
            </tr>
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($service['service_id']); ?></td>
                        <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                        <td><?php echo htmlspecialchars($service['price']); ?></td>
                        <td><?php echo htmlspecialchars($service['service_description']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No services available</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div id="booking-container" class="booking-container">
    <h3>Book a Service</h3>
    <form method="post" action="user_dashboard.php" class="booking-form">
        <div class="form-group">
            <label for="service_id">Select Service:</label>
            <select name="service_id" required>
                <?php foreach ($services as $service): ?>
                    <option value="<?php echo htmlspecialchars($service['service_id']); ?>">
                        <?php echo htmlspecialchars($service['service_name']); ?> - ₱<?php echo htmlspecialchars($service['price']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="booking_date">Select Date:</label>
            <input type="date" name="booking_date" required>
        </div>
        <div class="form-group">
            <input type="submit" name="book_service" value="Book" class="submit-button">
        </div>
    </form>
</div>
<div id="bookings-container" class="bookings-container">
    <h3>Your Bookings And Schedules</h3>
    <table>
        <tr>
            <th>Booking ID</th>
            <th>Service Name</th>
            <th>Price</th>
            <th>Date</th>
            <th>Available Time</th>
            <th>Action</th> 
        </tr>
        <?php if (!empty($bookings)): ?>
            <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                    <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                    <td><?php echo htmlspecialchars($booking['price']); ?></td>
                    <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                    <td><?php echo htmlspecialchars($booking['booking_time']); ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                            <input type="hidden" name="action" value="disapprove">
                            <button class = "disapprove" type="submit">Cancel</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No bookings available</td> 
            </tr>
        <?php endif; ?>
    </table>
</div>

    <div class="paid-bookings" id="paid-bookings" style = "display: none;">
        <p>Number of Paid Bookings: <?php echo $paidBookingResult->num_rows; ?></p>
        <h2>Paid Bookings</h2>
        <table>
            <tr>
                <th>Booking ID</th>
                <th>Service Name</th>
                <th>Price</th>
                <th>Come Date</th>
                <th>Come Time</th>
                <th>Booked At</th>
            </tr>
            <?php
           if ($paidBookingResult->num_rows > 0) {
            while($row = $paidBookingResult->fetch_assoc()) {
                echo "<tr>";
                echo "<td>".$row["booking_id"]."</td>"; 
                echo "<td>".$row["service_name"]."</td>";
                echo "<td>".$row["price"]."</td>";
                echo "<td>".$row["booking_date"]."</td>";
                echo "<td>".$row["booking_time"]."</td>";
                echo "<td>".$row["created_at"]."</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='9'>No paid bookings found</td></tr>";
        }
            ?>
        </table>
    </div>
<div id="map-container" style = "display: none;">
    <iframe src="https://www.google.com/maps/d/embed?mid=1XRrYKLluYx-P0YlBTx68KzHwgrCk2M0&ehbc=2E312F" width="640" height="480"></iframe>
</div>

<script>
function logoutSecurely() {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "/logout", true); 
    xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            console.log("User securely logged out.");
        }
    };
    xhr.send(JSON.stringify({}));
}

function isLoggedIn() {
    return true; 
}

window.addEventListener("beforeunload", function(event) {
    logoutSecurely(); 
    var warningMessage = "Warning: You are about to leave the User dashboard.";
    return warningMessage;
});

window.addEventListener("popstate", function(event) {
    history.forward(); 
});

if (window.location.href.includes("user_dashboard.php")) {
    if (!isLoggedIn()) {
        window.location.href = "login.html"; 
    }
}

document.addEventListener('contextmenu', event => event.preventDefault());

document.addEventListener('keydown', function (event) {
    if (event.ctrlKey && (event.key === 'f' || event.key === 'F' || event.key === 'u' || event.key === 'U' || event.key === 's' || event.key === 'S' || event.key === 'p' || event.key === 'P')) {
        event.preventDefault();
    }
    if (event.ctrlKey && event.shiftKey && (event.key === 'i' || event.key === 'I' || event.key === 'c' || event.key === 'C' || event.key === 'j' || event.key === 'J')) {
        event.preventDefault();
    }
});

function toggleServices() {
    var services = document.getElementById("services-container");
    if (services.style.display == "none") {
        services.style.display = "block";
    } else {
        services.style.display = "none";
    }
}

function toggleBookingForm() {
    var bookingContainer = document.getElementById("booking-container");
    if (bookingContainer.style.display === "none") {
        bookingContainer.style.display = "block";
    } else {
        bookingContainer.style.display = "none";
    }
}

function toggleBookings() {
    var bookingsContainer = document.getElementById("bookings-container");
    if (bookingsContainer.style.display === "none") {
        bookingsContainer.style.display = "block";
    } else {
        bookingsContainer.style.display = "none";
    }
}
function toggleMap() {
    var mapContainer = document.getElementById("map-container");
    if (mapContainer.style.display === "none") {
        mapContainer.style.display = "block";
    } else {
        mapContainer.style.display = "none";
    }
}
function togglePaidBookings() {
        var x = document.getElementById("paid-bookings");
        if (x.style.display === "none") {
            x.style.display = "block";
        } else {
            x.style.display = "none";
        }
    }


    const fileEl = document.getElementById("file");
    const fileButtonEl = document.getElementById("file-button");

    let timer;

    fileEl.addEventListener("click", () => {
         if(fileButtonEl.style.display === "none"){
            fileButtonEl.style.display = "block";
            clearTimeout(timer);


         }else{
            fileButtonEl.style.display = "none";

         }

         timer =  setTimeout (() => {
            fileButtonEl.style.display = "none";
        

         }, 60000);

    });










</script>
</body>
</html>  
<?php
require_once "config.php";
include("connection.php");
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['admin'])) {
    header("location: admin_login.php");
    exit();
}

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

    header("Location: admin_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_service'])) {
        $serviceName = $conn->real_escape_string($_POST['service_name']);
        $serviceDescription = $conn->real_escape_string($_POST['service_description']);
        $price = $conn->real_escape_string($_POST['price']);

        $checkServiceSql = "SELECT * FROM services WHERE service_name='$serviceName'";
        $checkServiceResult = $conn->query($checkServiceSql);
        if ($checkServiceResult->num_rows > 0) {
            $message = "Service with the same name already exists!";
        } else {
            $insertServiceSql = "INSERT INTO services (service_name, service_description, price) VALUES ('$serviceName', '$serviceDescription', '$price')";
            if ($conn->query($insertServiceSql) === TRUE) {
                $message = "New service added successfully!";
            } else {
                $message = "Error: " . $conn->error;
            }
        }
    } elseif (isset($_POST['update_service'])) {
        $serviceId = $conn->real_escape_string($_POST['service_id']);
        $serviceName = $conn->real_escape_string($_POST['service_name']);
        $serviceDescription = $conn->real_escape_string($_POST['service_description']);
        $price = $conn->real_escape_string($_POST['price']);

        $updateServiceSql = "UPDATE services SET service_name='$serviceName', service_description='$serviceDescription', price='$price' WHERE service_id='$serviceId'";
        if ($conn->query($updateServiceSql) === TRUE) {
            $message = "Service updated successfully!";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}

$sql = "SELECT * FROM users";
$result = $conn->query($sql);

$countSql = "SELECT COUNT(*) as user_count FROM users";
$countResult = $conn->query($countSql);
$userCount = $countResult->fetch_assoc()['user_count'];

$bookingSql = "SELECT b.*, u.fullname AS user_name, u.email AS user_email FROM bookings b JOIN users u ON b.user_id = u.user_id";
$bookingResult = $conn->query($bookingSql);

$serviceSql = "SELECT DISTINCT * FROM services";
$serviceResult = $conn->query($serviceSql);

if (isset($_GET['delete_service'])) {
    $serviceId = $conn->real_escape_string($_GET['delete_service']);
    $deleteServiceSql = "DELETE FROM services WHERE service_id='$serviceId'";
    if ($conn->query($deleteServiceSql) === TRUE) {
        $message = "Service deleted successfully!";
    } else {
        $message = "Error: " . $conn->error;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['booking_id'])) {
    $bookingId = $conn->real_escape_string($_POST['booking_id']);
    $bookingSql = "SELECT * FROM bookings WHERE booking_id='$bookingId'";
    $bookingResult = $conn->query($bookingSql);

    if ($bookingResult->num_rows == 1) {
        $row = $bookingResult->fetch_assoc();
        $insertSql = "INSERT INTO paid_bookings (user_id, booking_id, service_name, price, booking_date, booking_time, created_at)
                      VALUES ('".$row['user_id']."', '".$row['booking_id']."', '".$row['service_name']."', '".$row['price']."', '".$row['booking_date']."', '".$row['booking_time']."', '".$row['created_at']."')";
        if ($conn->query($insertSql) === TRUE) {
            $deleteSql = "DELETE FROM bookings WHERE booking_id='$bookingId'";
            if ($conn->query($deleteSql) === TRUE) {
                $updateLoyaltySql = "UPDATE users SET loyalty_points = loyalty_points + 20 WHERE user_id = '".$row['user_id']."'";
                if ($conn->query($updateLoyaltySql) === TRUE) {
                    echo "Booking marked as paid successfully. Loyalty points updated.";
                } else {
                    echo "Error updating loyalty points: " . $conn->error;
                }
            } else {
                echo "Error deleting booking: " . $conn->error;
            }
        } else {
            echo "Error marking booking as paid: " . $conn->error;
        }
    } else {
        echo "Booking not found.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_booking_id'])) {
    $bookingId = $conn->real_escape_string($_POST['approve_booking_id']);
    $approveBookingSql = "UPDATE bookings SET is_approved = 1 WHERE booking_id='$bookingId'";
    if ($conn->query($approveBookingSql) === TRUE) {
        // The booking has been successfully approved.
        echo "Booking approved successfully.";
    } else {
        // Error occurred while approving the booking.
        echo "Error approving booking: " . $conn->error;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_booking_id'])) {
    $bookingId = $conn->real_escape_string($_POST['approve_booking_id']);
    $bookingSql = "SELECT b.*, u.email AS user_email, u.fullname AS user_name FROM bookings b JOIN users u ON b.user_id = u.user_id WHERE booking_id='$bookingId'";
    $bookingResult = $conn->query($bookingSql);

    if ($bookingResult->num_rows == 1) {
        $row = $bookingResult->fetch_assoc();

        // PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ibanezjayniel913@gmail.com'; // replace with your email
            $mail->Password = 'cbtl nfux wdef ijcs'; // replace with your email password
            $mail->Port = 465;
            $mail->SMTPSecure = 'ssl';

            $mail->setFrom('ibanezjayniel913@gmail.com', 'Dental Clinic Reservation System'); // replace with your email
            $mail->addAddress($row['user_email'], $row['user_name']);
            $mail->isHTML(true);
            $mail->Subject = 'Booking Approved';
            $mail->Body = 'Dear ' . $row['user_name'] . ',<br><br>Your booking for ' . $row['service_name'] . ' on ' . $row['booking_date'] . ' at ' . $row['booking_time'] . ' has been approved.<br><br>Thank you for choosing our services.<br><br>Best Regards,<br>Dental Clinic Reservation System';

            $mail->send();
            echo 'Booking approved and email sent successfully.';
        } catch (Exception $e) {
            echo 'Error sending email: ' . $mail->ErrorInfo;
        }
    } else {
        echo "Booking not found.";
    }
}

$paidBookingSql = "SELECT pb.*, u.fullname AS user_name FROM paid_bookings pb JOIN users u ON pb.user_id = u.user_id";
$paidBookingResult = $conn->query($paidBookingSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin-bottom: 2000px;
            background-color: darkgrey;
            font-family: Arial, sans-serif;
            width: 100%;
        }
        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #B0E0E6;
            overflow-x: hidden;
            padding-top: 20px;
        }
        .sidebar a {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 18px;
            color: #818181;
            display: block;
            padding-left: 100px;
            cursor: pointer;
        }
        .sidebar a:hover {
            color: red;
            background-color: darkgray;
        }
        .content {
            margin-left: 250px;
            padding: 16px;
            background-color: Lavender;
            width: 100%;
        }
        .container {
            background-color: white;
            padding: 20px;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 20px auto;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid maroon;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
        form {
            margin-top: 20px;
        }
        form h3 {
            margin-bottom: 15px;
            color: #333;
        }
        form label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
            color: #333;
        }
        form input, form textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        form button {
            margin-top: 15px;
            padding: 10px 15px;
            background-color:  #50C878 ;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
        }
        form button:hover {
            background-color: #218838;
        }
        .message {
            color: #28a745;
            font-weight: bold;
        }
        .edit-form {
            display: none;
        }
        .edit-button {
            padding: 5px 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: 0.3s;
        }
        .edit-button:hover {
            background-color: #218838;
        }
        .delete-button {
            padding: 5px 10px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: 0.3s;
        }
        .delete-button:hover {
            background-color: #c82333;
        }
        .update {
            padding: 5px;
            background-color: #64b5f6;
        }

        .services{
            padding: 20px;
            margin-left: 250px;
            background-color: #FFFDD0;
            width: 100%;
        }

        .bookings{
            padding: 20px;
            margin-left: 250px;
            width: 100%;
            background-color: #F0F8FF;
        }

        .paid-bookings {
            margin-left: 250px;
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
            background-color: #f2f2f2;
        }
        .paid-bookings tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .mark-as-paid-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .mark-as-paid-btn:hover {
            background-color: #45a049;
        }

        .approve-btn {
            background-color: gold; /* Green */
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 5px;
            transition: opacity 0.3s;
        }

        .approve-btn:hover {
           opacity: 0.8;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <a href="#" onclick="toggleSection('content')"><i class="fas fa-users"></i>Manage Users</a><hr>
    <a href="#" onclick="toggleSection('service-form')"><i class="fas fa-concierge-bell"></i> Manage Services</a><hr>
    <a href="#" onclick="toggleSection('services')"><i class="fas fa-list"></i> View Services</a><hr>
    <a href="#" onclick="toggleSection('bookings')"><i class="fas fa-calendar-alt"></i> Manage Bookings</a><hr>
    <a onclick="togglePaidBookings()"><i class="fas fa-money-bill-wave"></i> Paid Bookings</a>
    <a href="?logout=1" onclick="return confirm('Are you sure you want to logout?')"><i class="fas fa-sign-out-alt"></i> Logout</a><hr>
</div>

<div class="content" id="content" style="display: none;">
    <h2>Welcome Admin</h2>
    <p>Total Users: <?php echo $userCount; ?></p>
    <table>
        <tr>
            <th>Profile Image</th>
            <th>User ID</th>
            <th>Full Name</th>
            <th>Gender</th>
            <th>Age</th>
            <th>Address</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Password</th>
            <th>Loyalty Points</th>
            <th>Created At</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td><img src='".$row["profile_image"]."' alt='Profile Image' style='width: 100px;'></td>";
                echo "<td>".$row["user_id"]."</td>";
                echo "<td>".$row["fullname"]."</td>";
                echo "<td>".$row["gender"]."</td>";
                echo "<td>".$row["age"]."</td>";
                echo "<td>".$row["address"]."</td>";
                echo "<td>".$row["phone"]."</td>";
                echo "<td>".$row["email"]."</td>";
                echo "<td>".$row["password"]."</td>";
                echo "<td>".$row["loyalty_points"]."</td>";
                echo "<td>".$row["created_at"]."</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='10'>No users found</td></tr>";
        }
        ?>
    </table>
</div>

<div class="container" id="service-form" style="display: none;">
    <form method="POST" action="">
        <h3>Add New Service</h3>
        <label for="service_name">Service Name:</label><br>
        <input type="text" id="service_name" name="service_name" required><br>
        <label for="service_description">Service Description:</label><br>
        <textarea id="service_description" name="service_description" required></textarea><br>
        <label for="price">Price:</label><br>
        <input type="number" step="0.01" id="price" name="price" required><br>
        <button type="submit" name="add_service">Add Service</button>
    </form>

    <?php
    if (isset($message)) {
        echo "<p class='message'>$message</p>";
    }
    ?>
</div>

<div class= "services" class="content" id="services" style="display: none;">
<p>Number of Services: <?php echo $serviceResult->num_rows; ?></p>
    <h2>View Services</h2>
    <table>
        <tr>
            <th>Service ID</th>
            <th>Service Name</th>
            <th>Service Description</th>
            <th>Price</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
        <?php
        if ($serviceResult->num_rows > 0) {
            while($row = $serviceResult->fetch_assoc()) {
                echo "<tr>";
                echo "<td>".$row["service_id"]."</td>";
                echo "<td>".$row["service_name"]."</td>";
                echo "<td>".$row["service_description"]."</td>";
                echo "<td>".$row["price"]."</td>";
                echo "<td>".$row["created_at"]."</td>";
                echo "<td>
                        <button class='edit-button' onclick=\"editService('".$row["service_id"]."', '".$row["service_name"]."', '".$row["service_description"]."', '".$row["price"]."')\">Edit</button>
                        <a class='delete-button' href='?delete_service=".$row["service_id"]."' onclick=\"return confirm('Are you sure you want to delete this service?')\">Delete</a>
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No services found</td></tr>";
        }
        ?>
    </table>
</div>

<div class="container edit-form" id="edit-service-form">
    <form method="POST" action="">
        <h3>Edit Service</h3>
        <input type="hidden" id="service_id" name="service_id" required>
        <label for="edit_service_name">Service Name:</label><br>
        <input type="text" id="edit_service_name" name="service_name" required><br>
        <label for="edit_service_description">Service Description:</label><br>
        <textarea id="edit_service_description" name="service_description" required></textarea><br>
        <label for="edit_price">Price:</label><br>
        <input type="number" step="0.01" id="edit_price" name="price" required><br>
        <button class="update" type="submit" name="update_service">Update Service</button>
    </form>
</div>


<div class="bookings" id="bookings" style="display: none;">
    <p>Number of Bookings: <?php echo $bookingResult->num_rows; ?></p>
    <h2>Manage Bookings</h2>
    <table>
        <tr>
            <th>User Name</th>
            <th>Booking ID</th>
            <th>User ID</th>
            <th>Service Name</th>
            <th>Price</th>
            <th>Booking Date</th>
            <th>Booking Time</th>
            <th>Booked At</th>
            <th>Actions</th>
        </tr>
        <?php
        if ($bookingResult->num_rows > 0) {
            while($row = $bookingResult->fetch_assoc()) {
                echo "<tr>";
                echo "<td>".$row["user_name"]."</td>";
                echo "<td>".$row["booking_id"]."</td>";
                echo "<td>".$row["user_id"]."</td>";
                echo "<td>".$row["service_name"]."</td>";
                echo "<td>".$row["price"]."</td>";
                echo "<td>".$row["booking_date"]."</td>";
                echo "<td>".$row["booking_time"]."</td>";
                echo "<td>".$row["created_at"]."</td>";
                echo "<td>";
                echo "<button class='mark-as-paid-btn' onclick='markAsPaid(\"".$row["booking_id"]."\")'>Mark as Paid</button>";
                echo "<button class='approve-btn' onclick='approveBooking(\"".$row["booking_id"]."\")'>Approve</button>";
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='9'>No bookings found</td></tr>";
        }
        ?>
    </table>
</div>
    <div class="paid-bookings" id="paid-bookings" style = "display: none;">
        <p>Number of Paid Bookings: <?php echo $paidBookingResult->num_rows; ?></p>
        <h2>Paid Bookings</h2>
        <table>
            <tr>
                <th>Users Name</th>
                <th>Booking ID</th>
                <th>User ID</th>
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
                echo "<td>".$row["user_name"]."</td>";
                echo "<td>".$row["booking_id"]."</td>"; 
                echo "<td>".$row["user_id"]."</td>";
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
            window.location.href = "login.php"; 
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

    function toggleSection(sectionId) {  
        var section = document.getElementById(sectionId);
        if (section.style.display == "none") {
            section.style.display = "block";
        } else {
            section.style.display = "none";
        }
    }

    function editService(serviceId, serviceName, serviceDescription, price) {
        document.getElementById('service_id').value = serviceId;
        document.getElementById('edit_service_name').value = serviceName;
        document.getElementById('edit_service_description').value = serviceDescription;
        document.getElementById('edit_price').value = price;
        document.getElementById('edit-service-form').style.display = 'block';
    }

function markAsPaid(bookingId) {
    if (confirm('Are you sure you want to mark this booking as paid?')) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'admin_dashboard.php', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                location.reload();
            } else {
                alert('Error marking booking as paid. Please try again.');
            }
        };
        xhr.send('booking_id=' + bookingId);
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

    function approveBooking(bookingId) {
    if (confirm('Are you sure you want to approve this booking?')) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'admin_dashboard.php', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                location.reload();
            } else {
                alert('Error approving booking. Please try again.');
            }
        };
        xhr.send('approve_booking_id=' + bookingId);
    }
}


</script>
</body>
</html>
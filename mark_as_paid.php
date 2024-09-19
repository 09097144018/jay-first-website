<?php
session_start();
include("connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['booking_id'])) {
    $bookingId = $conn->real_escape_string($_POST['booking_id']);
    
    $bookingSql = "SELECT * FROM bookings WHERE booking_id='$bookingId'";
    $bookingResult = $conn->query($bookingSql);
    
    if ($bookingResult->num_rows == 1) {
        $row = $bookingResult->fetch_assoc();
        
        $insertSql = "INSERT INTO paid_bookings (user_id, service_id, service_name, price, booking_date, booking_time, created_at)
                      VALUES ('".$row['user_id']."', '".$row['service_id']."', '".$row['service_name']."', '".$row['price']."', '".$row['booking_date']."', '".$row['booking_time']."', '".$row['created_at']."')";
        
        if ($conn->query($insertSql) === TRUE) {
            $deleteSql = "DELETE FROM bookings WHERE booking_id='$bookingId'";
            if ($conn->query($deleteSql) === TRUE) {
                echo "Booking marked as paid successfully.";
            } else {
                echo "Error deleting booking: " . $conn->error;
            }
        } else {
            echo "Error marking booking as paid: " . $conn->error;
        }
    } else {
        echo "Booking not found.";
    }
} else {
    echo "Invalid request.";
}
?>

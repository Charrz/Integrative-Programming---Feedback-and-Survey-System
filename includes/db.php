<?php
$conn = new mysqli("localhost", "root", "", "survey");

if ($conn->connect_error) {
    die("Database connection failed. Please make sure MySQL is running in XAMPP and the survey database has been imported.");
}
?>

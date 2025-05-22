<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "course_registration_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if POST data is set
if (!isset($_POST['studentID']) || !isset($_POST['password'])) {
    die("Error: Missing login credentials.");
}

$studentID = $_POST['studentID'];
$studentPassword = $_POST['password'];

// Prepare SQL statement
$sql = "SELECT studentID, studentName FROM Students WHERE studentID = ? AND studentPassword = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $studentID, $studentPassword);

// Execute and get result
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $_SESSION['studentID'] = $row['studentID'];
    $_SESSION['studentName'] = $row['studentName'];
    
    header("Location: regPage.php");
    exit();
} else {
    echo "Login Failed";
}

$stmt->close();
$conn->close();

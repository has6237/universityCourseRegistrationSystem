<?php
session_start();

if (!isset($_SESSION['studentID'])) {
    die("Error: User is not logged in. Please log in first.");
}

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

$studentID = $_SESSION['studentID'];

// Check if student exists and fetch name
$sql_check = "SELECT studentID, studentName FROM students WHERE studentID = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $studentID);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows === 0) {
    die("Error: User not found in database.");
}

$stmt_check->bind_result($fetchedID, $fetchedName);
$stmt_check->fetch();
$_SESSION['studentName'] = $fetchedName;
$stmt_check->close();

// Add course if requested
if (isset($_GET['add'])) {
    $courseCode = $_GET['add']; // Assuming courseCode is a string like "CS101"
    
    $stmt_add = $conn->prepare("INSERT IGNORE INTO registeredCourse (studentID, courseCode) VALUES (?, ?)");
    $stmt_add->bind_param("ss", $studentID, $courseCode); // use "ss" if courseCode is a string
    $stmt_add->execute();
    $stmt_add->close();
}

// Fetch available courses not yet registered
$stmt_courses = $conn->prepare("
    SELECT * FROM courseList 
    WHERE courseCode NOT IN (
        SELECT courseCode FROM registeredCourse WHERE studentID = ?
    )
");
$stmt_courses->bind_param("s", $studentID);
$stmt_courses->execute();
$result = $stmt_courses->get_result();
?>

<!doctype html>
<html>
<head>
    <title>Course Registration Page</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f9f9f9;
        color: #333;
        margin: 40px;
        line-height: 1.6;
    }

    h1, h2 {
        color: #2c3e50;
    }

    a {
        color: #3498db;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    th, td {
        padding: 12px 15px;
        border: 1px solid #ddd;
        text-align: left;
    }

    th {
        background-color: #f0f0f0;
        font-weight: bold;
    }

    tr:hover {
        background-color: #f9f9f9;
    }

    p {
        margin-top: 10px;
    }
</style>

</head>
<body>
    
    <h1>Hello! <?php echo htmlspecialchars($_SESSION['studentName']); ?></h1>
    <h2>Student ID: <?php echo htmlspecialchars($_SESSION['studentID']); ?></h2>
    <h2>Available Courses</h2>
    <p><a href="registeredCourses.php">View Registered Courses</a></p>
    <p><a href="logout.php">Logout</a></p>
    <input type="text" id="searchInput" placeholder="Search courses by name or code..." style="padding:8px; width: 300px; margin-bottom: 15px; font-size: 16px;">

    <table border="1" id="coursesTable">
        <tr><th>Courses</th><th>Code</th><th>Credit</th><th>Action</th></tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['courseName']) ?></td>
                <td><?= htmlspecialchars($row['courseCode']) ?></td>
                <td><?= htmlspecialchars($row['courseCredit']) ?></td>
                <td><a href="regPage.php?add=<?= urlencode($row['courseCode']) ?>">Add</a></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>



<script>
// Get the input and table elements
const searchInput = document.getElementById('searchInput');
const table = document.getElementById('coursesTable');
const rows = table.getElementsByTagName('tr');

// Listen for input event on the search box
searchInput.addEventListener('input', function() {
    const filter = this.value.toLowerCase();

    // Loop through table rows (skip the header row)
    for (let i = 1; i < rows.length; i++) {
        const courseName = rows[i].cells[0].textContent.toLowerCase();
        const courseCode = rows[i].cells[1].textContent.toLowerCase();

        // Check if the course name or code contains the filter text
        if (courseName.includes(filter) || courseCode.includes(filter)) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
});
</script>
</html>

<?php
$stmt_courses->close();
$conn->close();
?>

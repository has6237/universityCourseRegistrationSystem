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

// Handle course removal
if (isset($_GET['remove'])) {
    $courseCode = $_GET['remove']; // Assume it's a string like "CS101"

    $stmt_remove = $conn->prepare("DELETE FROM registeredCourse WHERE studentID = ? AND courseCode = ?");
    $stmt_remove->bind_param("ss", $studentID, $courseCode); // use "ss" for two strings
    $stmt_remove->execute();
    $stmt_remove->close();
}

// Fetch registered courses
$stmt_courses = $conn->prepare("
    SELECT courseList.courseCode, courseList.courseName, courseList.courseCredit
    FROM courseList
    JOIN registeredCourse ON courseList.courseCode = registeredCourse.courseCode
    WHERE registeredCourse.studentID = ?
");
$stmt_courses->bind_param("s", $studentID);
$stmt_courses->execute();
$result = $stmt_courses->get_result();
?>

<!doctype html>
<html>
<head>
    <title>Registered Courses</title>
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
        color: #e74c3c;
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
    <h2>Registered Courses</h2>

    <p><a href="regPage.php">Go to Course Registration</a></p>
    <p><a href="logout.php">Logout</a></p>
    <input type="text" id="searchInput" placeholder="Search courses by name or code..." style="padding:8px; width: 300px; margin-bottom: 15px; font-size: 16px;">

    <table border="1" id="coursesTable">
        <tr><th>Courses</th><th>Code</th><th>Credit</th><th>Action</th></tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['courseName']) ?></td>
	      <td><?= htmlspecialchars($row['courseCode']) ?></td>
	      <td><?= htmlspecialchars($row['courseCredit']) ?></td>
                <td><a href="registeredCourses.php?remove=<?= urlencode($row['courseCode']) ?>" onclick="return confirm('Are you sure you want to remove this course?')">Remove</a></td>
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

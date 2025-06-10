<?php
// Database Connection
$host = 'localhost';
$dbname = 'yw_db';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("<div class='alert alert-danger'>Connection failed: " . $conn->connect_error . "</div>");
}

// Handle Delete Request
if (isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
     // Delete related student data first
     $conn->query("DELETE FROM student_courses WHERE student_id = '$delete_id'");
     $conn->query("DELETE FROM students WHERE student_id = '$delete_id'");
 
     // Check if any courses/instructors are still linked
     $conn->query("DELETE FROM courses WHERE course_id NOT IN (SELECT DISTINCT course_id FROM student_courses)");
     $conn->query("DELETE FROM instructors WHERE instructor_id NOT IN (SELECT DISTINCT instructor_id FROM student_courses)");
 
     // Reset auto-increment ONLY if tables are now empty
     $check_courses = $conn->query("SELECT COUNT(*) AS count FROM courses")->fetch_assoc();
     $check_instructors = $conn->query("SELECT COUNT(*) AS count FROM instructors")->fetch_assoc();
 
     if ($check_courses['count'] == 0) {
         $conn->query("ALTER TABLE courses AUTO_INCREMENT = 1");
     }
     if ($check_instructors['count'] == 0) {
         $conn->query("ALTER TABLE instructors AUTO_INCREMENT = 1");
     }
}

// Handle Form Submission
// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['delete_id'])) {
    $student_id = (int)$_POST['student_id'];
    $student_name = trim($_POST['student_name']);
    $courses = explode(',', trim($_POST['course'])); 
    $instructors = explode(',', trim($_POST['instructor'])); 

    if (!empty($student_id) && !empty($student_name) && !empty($courses) && !empty($instructors)) {
        // Insert Student
        $stmt = $conn->prepare("INSERT INTO students (student_id, student_name) VALUES (?, ?) 
                                ON DUPLICATE KEY UPDATE student_name = VALUES(student_name)");
        $stmt->bind_param("is", $student_id, $student_name);
        $stmt->execute();
        $stmt->close();

        foreach ($courses as $index => $course_name) {
            $course_name = trim($course_name);
            $instructor_name = trim($instructors[$index % count($instructors)]);

            // --- Handle Course ---
            $course_id = null;
            $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_name = ?");
            $stmt->bind_param("s", $course_name);
            $stmt->execute();
            $stmt->bind_result($course_id);
            $stmt->fetch();
            $stmt->close();

            if (!$course_id) {
                $stmt = $conn->prepare("INSERT INTO courses (course_name) VALUES (?)");
                $stmt->bind_param("s", $course_name);
                $stmt->execute();
                $course_id = $conn->insert_id;
                $stmt->close();
            }

            // --- Handle Instructor ---
            $instructor_id = null;
            $stmt = $conn->prepare("SELECT instructor_id FROM instructors WHERE instructor_name = ?");
            $stmt->bind_param("s", $instructor_name);
            $stmt->execute();
            $stmt->bind_result($instructor_id);
            $stmt->fetch();
            $stmt->close();

            if (!$instructor_id) {
                $stmt = $conn->prepare("INSERT INTO instructors (instructor_name) VALUES (?)");
                $stmt->bind_param("s", $instructor_name);
                $stmt->execute();
                $instructor_id = $conn->insert_id;
                $stmt->close();
            }

            // --- Link Student, Course, Instructor ---
            $stmt = $conn->prepare("INSERT INTO student_courses (student_id, course_id, instructor_id) 
                                    VALUES (?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE instructor_id = VALUES(instructor_id)");
            $stmt->bind_param("iii", $student_id, $course_id, $instructor_id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        echo "<div class='alert alert-warning'>Please enter valid data.</div>";
    }
}

// Fetch and display data
$unf_result = $conn->query("SELECT students.student_id, students.student_name, 
                            GROUP_CONCAT(DISTINCT courses.course_name SEPARATOR ', ') AS course_name, 
                            GROUP_CONCAT(DISTINCT instructors.instructor_name SEPARATOR ', ') AS instructor_name
                            FROM students 
                            JOIN student_courses ON students.student_id = student_courses.student_id 
                            JOIN courses ON student_courses.course_id = courses.course_id
                            JOIN instructors ON student_courses.instructor_id = instructors.instructor_id
                            GROUP BY students.student_id, students.student_name");


$first_nf_result = $conn->query("SELECT 
    student_courses.student_id, 
    students.student_name, 
    courses.course_name, 
    instructors.instructor_name 
FROM student_courses 
    JOIN students ON student_courses.student_id = students.student_id
    JOIN courses ON student_courses.course_id = courses.course_id
    JOIN instructors ON student_courses.instructor_id = instructors.instructor_id");

$students_result = $conn->query("SELECT DISTINCT student_id, student_name FROM students");
$courses_result = $conn->query("SELECT DISTINCT course_id, course_name FROM courses");
$student_courses_result = $conn->query("
    SELECT sc.student_id, s.student_name, c.course_id, c.course_name, i.instructor_name
    FROM student_courses sc
    JOIN students s ON sc.student_id = s.student_id
    JOIN courses c ON sc.course_id = c.course_id
    JOIN instructors i ON sc.instructor_id = i.instructor_id
");



$third_nf_result = $conn->query("SELECT DISTINCT courses.course_name, student_courses.instructor FROM student_courses JOIN courses ON student_courses.course_id = courses.course_id");

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <title>Normalization App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background: url('musedash.gif') no-repeat center center fixed;
            background-size: cover;
            color: white;
        }
        .data-table {
            background: rgba(0, 0, 0, 0.8);
            padding: 20px;
            border-radius: 10px;
            transition: transform 0.3s ease-in-out;
        }
        .data-table:hover {
            transform: scale(1.05);
        }
        button:hover {
            transform: scale(1.1);
            transition: transform 0.3s ease-in-out;
        }
    </style>
    <script>
        $(document).ready(function () {
            $('.nav-link').click(function (e) {
                e.preventDefault();
                $('.nav-link').removeClass('active');
                $(this).addClass('active');
                $('.data-table').hide();
                $('#' + $(this).attr('data-target')).show();
            });
            $('.nav-link.active').trigger('click');
        });
    </script>
</head>
<body class="container mt-5">
<h2 class="mb-3 text-center">Normalization App</h2>

<form method="POST" class="mb-4">
    <div class="row mb-2">
        <div class="col"><input type="text" name="student_id" class="form-control" placeholder="Student ID" required></div>
        <div class="col"><input type="text" name="student_name" class="form-control" placeholder="Student Name" required></div>
        <div class="col"><input type="text" name="course" class="form-control" placeholder="Courses (comma-separated)" required></div>
        <div class="col"><input type="text" name="instructor" class="form-control" placeholder="Instructors (comma-separated)" required></div>
        <div class="col"><button type="submit" class="btn btn-primary">Submit</button></div>
    </div>
</form>

<ul class="nav nav-tabs">
    <li class="nav-item"><a class="nav-link active" href="#" data-target="unf">UNF</a></li>
    <li class="nav-item"><a class="nav-link" href="#" data-target="first_nf">1NF</a></li>
    <li class="nav-item"><a class="nav-link" href="#" data-target="second_nf">2NF</a></li>
    <li class="nav-item"><a class="nav-link" href="#" data-target="third_nf">3NF</a></li>
</ul>

<div id="unf" class="data-table">
    <h2>Unnormalized Form (UNF)</h2>
    <table class="table table-bordered table-dark">
        <tr><th>ID</th><th>Student Name</th><th>Courses</th><th>Instructors</th><th>Action</th></tr>
        <?php while ($row = $unf_result->fetch_assoc()) { ?>
            <tr>
    <td><?= htmlspecialchars($row['student_id']); ?></td> <!-- Corrected from 'id' -->
    <td><?= htmlspecialchars($row['student_name']); ?></td>
    <td><?= htmlspecialchars($row['course_name']); ?></td>
    <td><?= htmlspecialchars($row['instructor_name']); ?></td>
    <td>
        <form method="POST">
            <input type="hidden" name="delete_id" value="<?= $row['student_id']; ?>">
            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
        </form>
    </td>
</tr>

        <?php } ?>
    </table>
</div>

<div id="first_nf" class="data-table" style="display:none;">
    <h2>First Normal Form (1NF)</h2>
    <table class="table table-bordered table-dark">
        <tr><th>Student ID</th><th>Student Name</th><th>Course</th><th>Instructor</th></tr>
        <?php while ($row = $first_nf_result->fetch_assoc()) { ?>
            <tr>
    <td><?= htmlspecialchars($row['student_id']); ?></td>
    <td><?= htmlspecialchars($row['student_name']); ?></td>
    <td><?= htmlspecialchars($row['course_name']); ?></td>
    <td><?= htmlspecialchars($row['instructor_name']); ?></td> <!-- Ensure this matches the SELECT alias -->
</tr>


        <?php } ?>
    </table>
</div>

<div id="second_nf" class="data-table" style="display:none;">
    <h2>Second Normal Form (2NF)</h2>

    <!-- Students Table -->
    <h3>Students</h3>
    <table class="table table-bordered table-dark">
        <tr><th>Student ID</th><th>Student Name</th></tr>
        <?php while ($row = $students_result->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row['student_id']); ?></td>
                <td><?= htmlspecialchars($row['student_name']); ?></td>
            </tr>
        <?php } ?>
    </table>

    <!-- Courses Table -->
    <h3>Courses</h3>
    <table class="table table-bordered table-dark">
        <tr><th>Course ID</th><th>Course Name</th></tr>
        <?php while ($row = $courses_result->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row['course_id']); ?></td>
                <td><?= htmlspecialchars($row['course_name']); ?></td>
            </tr>
        <?php } ?>
    </table>

    <!-- Student-Course Relationship Table -->
    <h3>Student-Course Relationship</h3>
    <table class="table table-bordered table-dark">
    <tr><th>Student ID</th><th>Course ID</th><th>Instructor</th></tr>
        <?php while ($row = $student_courses_result->fetch_assoc()) { ?>
            <tr>
            <td><?= htmlspecialchars($row['student_id']); ?></td>
            <td><?= htmlspecialchars($row['course_id']); ?></td>
            <td><?= htmlspecialchars($row['instructor_name']); ?></td> <!-- ✅ Fixed here -->
        </tr>
        <?php } ?>
    </table>
</div>


<div id="third_nf" class="data-table" style="display:none;">
    <h2>Third Normal Form (3NF)</h2>

    <!-- Students Table -->
    <h3>Students</h3>
    <table class="table table-bordered table-dark">
        <tr><th>Student ID</th><th>Student Name</th></tr>
        <?php
        $students_result = $conn->query("SELECT student_id, student_name FROM students");
        while ($row = $students_result->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row['student_id']); ?></td>
                <td><?= htmlspecialchars($row['student_name']); ?></td>
            </tr>
        <?php } ?>
    </table>

    <!-- Courses Table -->
    <h3>Courses</h3>
    <table class="table table-bordered table-dark">
        <tr><th>Course ID</th><th>Course Name</th></tr>
        <?php
        $courses_result = $conn->query("SELECT course_id, course_name FROM courses");
        while ($row = $courses_result->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row['course_id']); ?></td>
                <td><?= htmlspecialchars($row['course_name']); ?></td>
            </tr>
        <?php } ?>
    </table>

    <!-- Instructors Table -->
    <h3>Instructors</h3>
    <table class="table table-bordered table-dark">
        <tr><th>Instructor ID</th><th>Instructor Name</th></tr>
        <?php
        $instructors_result = $conn->query("SELECT instructor_id, instructor_name FROM instructors");
        while ($row = $instructors_result->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row['instructor_id']); ?></td>
                <td><?= htmlspecialchars($row['instructor_name']); ?></td>
            </tr>
        <?php } ?>
    </table>

    <!-- Student-Course-Instructor Relationship Table -->
    <h3>Student-Course-Instructor Relationships</h3>
    <table class="table table-bordered table-dark">
        <tr><th>Student ID</th><th>Course ID</th><th>Instructor ID</th></tr>
        <?php
        $relationships_result = $conn->query("SELECT student_id, course_id, instructor_id FROM student_courses");
        while ($row = $relationships_result->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row['student_id']); ?></td>
                <td><?= htmlspecialchars($row['course_id']); ?></td>
                <td><?= htmlspecialchars($row['instructor_id']); ?></td>
            </tr>
        <?php } ?>
    </table>
</div>

</body>
</html>

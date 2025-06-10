<?php
session_start();

// Check if the user is already logged in, and redirect to the appropriate dashboard
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}

// Database connection function
function getConnection() {
    $servername = "localhost";
    $username = "root";
    $password = ""; 
    $dbname = "inventory_db";
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $roleID = $_POST['role'];
    $registrationCode = isset($_POST['registration_code']) ? $_POST['registration_code'] : '';
    
    // Initialize errors array
    $errors = [];
    
    // Validate username (at least 4 characters)
    if (strlen($username) < 4) {
        $errors[] = "Username must be at least 4 characters long";
    }
    
    // Check if username already exists
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT UserID FROM users WHERE Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Username already exists";
    }
    $stmt->close();
    
    // Validate password (at least 8 characters, with at least one uppercase, one lowercase, and one number)
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    // Check if passwords match
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";   
    }
    
    // Validate registration codes based on role
    // Always check code when role requires it, regardless of whether field is visible in UI
    if ($roleID == 1) { // Admin role
        $adminCode = "ADMIN123"; // Admin registration code
        if ($registrationCode !== $adminCode) {
            $errors[] = "Invalid admin registration code";
        }
    } elseif ($roleID == 2) { // Staff role
        $staffCode = "STAFF456"; // Staff registration code
        if ($registrationCode !== $staffCode) {
            $errors[] = "Invalid staff registration code";
        }
    }
    // No registration code needed for customer/user role (3)
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user into database
        $stmt = $conn->prepare("INSERT INTO users (Username, Password, RoleID) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $username, $hashedPassword, $roleID);
        
        if ($stmt->execute()) {
            // Registration successful, redirect to login page with success message
            header("Location: login.php?success=Registration successful! You can now log in.");
            exit();
        } else {
            $errors[] = "Registration failed: " . $conn->error;
        }
        $stmt->close();
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --primary-light: #E0E7FF;
            --gray: #6B7280;
            --light-gray: #F3F4F6;
            --white: #FFFFFF;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --border-radius: 0.75rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--light-gray);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .register-container {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .register-header {
            margin-bottom: 1.5rem;
        }

        .register-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
        }

        .register-header p {
            font-size: 0.875rem;
            color: var(--gray);
        }

        .form-group {
            margin-bottom: 1rem;
            text-align: left;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        input, select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            color: var(--gray);
            outline: none;
            transition: border-color 0.2s;
        }

        input:focus, select:focus {
            border-color: var(--primary);
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary);
            color: var(--white);
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: var(--primary-dark);
        }

        .register-footer {
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--gray);
        }

        .register-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .register-footer a:hover {
            text-decoration: underline;
        }

        .error-message {
            color: red;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Create an Account</h1>
            <p>Join the Inventory Management System</p>
        </div>

        <!-- Display error messages if there are any -->
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <strong>Please fix the following errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
            </div>

            <div class="form-group">
                <label for="role">Account Type</label>
                <select id="role" name="role" required>
                    <option value="" disabled selected>Select account type</option>
                    <option value="3" <?php echo (isset($_POST['role']) && $_POST['role'] == '3') ? 'selected' : ''; ?>>Customer</option>
                    <option value="2" <?php echo (isset($_POST['role']) && $_POST['role'] == '2') ? 'selected' : ''; ?>>Staff</option>
                    <option value="1" <?php echo (isset($_POST['role']) && $_POST['role'] == '1') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>

            <div class="form-group" id="codeContainer" style="display: none;">
                <label for="registration_code">Registration Code</label>
                <input type="password" id="registration_code" name="registration_code" placeholder="Enter registration code">
            </div>

            <button type="submit" class="btn">Register</button>
        </form>

        <div class="register-footer">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>

    <script>
        // Show/hide registration code field based on role selection
        document.getElementById('role').addEventListener('change', function() {
            const codeContainer = document.getElementById('codeContainer');
            const codeInput = document.getElementById('registration_code');

            if (this.value === '1' || this.value === '2') {
                codeContainer.style.display = 'block';
                codeInput.setAttribute('required', 'required');
            } else {
                codeContainer.style.display = 'none';
                codeInput.removeAttribute('required');
            }
        });
    </script>
</body>
</html>
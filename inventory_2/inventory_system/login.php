<?php
session_start();

// Check if the user is already logged in, and redirect to the appropriate dashboard
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['RoleID'] == 1 || $_SESSION['user']['RoleID'] == 2) {
        // Redirect to the Admin/Staff dashboard
        header("Location: dashboard.php");
    } elseif ($_SESSION['user']['RoleID'] == 3) {
        // Redirect to the Customer dashboard
        header("Location: customer_dashboard.php");
    }
    exit();
}

// Handle direct form submission (without AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Database connection
    $servername = "localhost";
    $db_username = "root";
    $db_password = ""; 
    $dbname = "inventory_db";
    
    // Create connection
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        $error = "Connection failed: " . $conn->connect_error;
    } else {
        // Prepare SQL statement to retrieve user data
        $stmt = $conn->prepare("SELECT u.UserID, u.Username, u.Password, u.RoleID, r.RoleName 
                               FROM users u 
                               JOIN roles r ON u.RoleID = r.RoleID 
                               WHERE u.Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['Password'])) {
                // Password is correct, create session
                $_SESSION['user'] = [
                    'UserID' => $user['UserID'],
                    'Username' => $user['Username'],
                    'RoleID' => $user['RoleID'],
                    'RoleName' => $user['RoleName']
                ];
                
                // ADD THE LOGIN HISTORY CODE RIGHT HERE
                $login_time = date('Y-m-d H:i:s');
                $stmt = $conn->prepare("INSERT INTO user_login_history (user_id, login_time) VALUES (?, ?)");
                $stmt->bind_param("is", $user['UserID'], $login_time);
                $stmt->execute();
                
                // Store the login history ID in the session
                $_SESSION['login_history_id'] = $conn->insert_id;
                
                // Redirect based on role
                if ($user['RoleID'] == 1 || $user['RoleID'] == 2) {
                    // Admin or Staff
                    header("Location: dashboard.php");
                } elseif ($user['RoleID'] == 3) {
                    // Customer
                    header("Location: customer_dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "Username not found";
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventory System</title>
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

        .login-container {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-header {
            margin-bottom: 1.5rem;
        }

        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
        }

        .login-header p {
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

        input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            color: var(--gray);
            outline: none;
            transition: border-color 0.2s;
        }

        input:focus {
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

        .login-footer {
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--gray);
        }

        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Welcome Back</h1>
            <p>Please sign in to continue</p>
        </div>

        <!-- Display error message if there is any -->
        <?php if (isset($error)): ?>
            <div class="error-message" style="color: red; margin-bottom: 1rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <div class="login-footer">
            <p>Don't have an account? <a href="register.php">Sign up</a></p>
        </div>
    </div>
</body>
</html>
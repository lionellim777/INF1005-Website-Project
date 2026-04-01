<?php
// Boot up the database connection
require_once __DIR__ . '/inc/bootstrap.php';

// The fixed password everyone will use for the demo
$demo_password = 'INF1005!!';

// Securely hash the password using your server's bcrypt algorithm
$hashed_password = password_hash($demo_password, PASSWORD_DEFAULT);

// 1. The Admin
$admin_stmt = $db_conn->prepare("INSERT IGNORE INTO users (fname, lname, email, password, role) VALUES (?, ?, ?, ?, ?)");
$fname = 'System'; $lname = 'Admin'; $email = 'admin@pomegranate.com'; $role = ROLE_ADMIN;
$admin_stmt->bind_param("sssss", $fname, $lname, $email, $hashed_password, $role);
$admin_stmt->execute();

// 2. The Employee
$emp_stmt = $db_conn->prepare("INSERT IGNORE INTO users (fname, lname, email, password, role) VALUES (?, ?, ?, ?, ?)");
$fname = 'Store'; $lname = 'Employee'; $email = 'employee1@pomegranate.com'; $role = ROLE_EMPLOYEE;
$emp_stmt->bind_param("sssss", $fname, $lname, $email, $hashed_password, $role);
$emp_stmt->execute();

// 3. The Customer
$user_stmt = $db_conn->prepare("INSERT IGNORE INTO users (fname, lname, email, password, role) VALUES (?, ?, ?, ?, ?)");
$fname = 'Chippy'; $lname = 'Jones'; $email = 'chippy@pomegranate.com'; $role = ROLE_USER;
$user_stmt->bind_param("sssss", $fname, $lname, $email, $hashed_password, $role);
$user_stmt->execute();

echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo "<h2>✅ Success! Demo Users Seeded</h2>";
echo "<p>The following accounts have been safely injected into your database and are ready to use.</p>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@pomegranate.com / Password1!</li>";
echo "<li><strong>Employee:</strong> employee1@pomegranate.com / Password1!</li>";
echo "<li><strong>Customer:</strong> johndoe@pomegranate.com / Password1!</li>";
echo "</ul>";
echo "<p style='color: red;'><strong>IMPORTANT:</strong> Delete this seed_users.php file from your server now!</p>";
echo "<a href='/login.php'>Go to Login Page</a>";
echo "</div>";
?>
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug Session Information</h2>";

echo "<h3>Session Status:</h3>";
echo "Session ID: " . (session_id() ? session_id() : 'No session') . "<br>";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active') . "<br>";

echo "<h3>Session Data:</h3>";
if (!empty($_SESSION)) {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "No session data found<br>";
}

echo "<h3>Login Check:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User is logged in<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Username: " . ($_SESSION['username'] ?? 'Not set') . "<br>";
    echo "Full Name: " . ($_SESSION['fullname'] ?? 'Not set') . "<br>";
    echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
    echo "Department: " . ($_SESSION['department'] ?? 'Not set') . "<br>";
    
    echo "<h3>Access Test:</h3>";
    echo "<a href='reports.php'>🔗 Try Access Reports Page</a><br>";
} else {
    echo "❌ User is not logged in<br>";
    echo "<h3>Login Required:</h3>";
    echo "<a href='../login.php'>🔗 Go to Login Page</a><br>";
}

echo "<h3>Server Information:</h3>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "<br>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "<br>";
echo "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'Not set') . "<br>";
?>
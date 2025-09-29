<?php
// Simple debug without auth
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== SIMPLE DEBUG ===<br>";

// Test path
echo "Current directory: " . __DIR__ . "<br>";
echo "Parent directory: " . dirname(__DIR__) . "<br>";

// Test config file
$config_path = dirname(__DIR__) . '/config.php';
echo "Config path: " . $config_path . "<br>";
echo "Config exists: " . (file_exists($config_path) ? 'YES' : 'NO') . "<br>";

if (file_exists($config_path)) {
    try {
        require_once $config_path;
        echo "Config loaded successfully<br>";
        
        // Test database
        if (isset($pdo)) {
            echo "PDO exists<br>";
            $test = $pdo->query("SELECT 1")->fetchColumn();
            echo "Database test: " . $test . "<br>";
        }
    } catch (Exception $e) {
        echo "Config error: " . $e->getMessage() . "<br>";
    }
}

// Test functions file
$functions_path = dirname(__DIR__) . '/includes/functions.php';
echo "<br>Functions path: " . $functions_path . "<br>";
echo "Functions exists: " . (file_exists($functions_path) ? 'YES' : 'NO') . "<br>";

if (file_exists($functions_path)) {
    try {
        require_once $functions_path;
        echo "Functions loaded successfully<br>";
    } catch (Exception $e) {
        echo "Functions error: " . $e->getMessage() . "<br>";
    }
}

echo "<br>=== END DEBUG ===<br>";
?>
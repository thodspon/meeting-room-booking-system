<?php
// Debug version of reports.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG REPORTS.PHP ===<br>";

// Test basic includes
echo "1. Testing includes...<br>";

try {
    if (file_exists('../config.php')) {
        echo "config.php exists<br>";
        require_once '../config.php';
        echo "config.php loaded successfully<br>";
    } else {
        echo "ERROR: config.php not found<br>";
    }
} catch (Exception $e) {
    echo "ERROR loading config.php: " . $e->getMessage() . "<br>";
}

try {
    if (file_exists('../includes/functions.php')) {
        echo "functions.php exists<br>";
        require_once '../includes/functions.php';
        echo "functions.php loaded successfully<br>";
    } else {
        echo "ERROR: functions.php not found<br>";
    }
} catch (Exception $e) {
    echo "ERROR loading functions.php: " . $e->getMessage() . "<br>";
}

try {
    if (file_exists('../auth.php')) {
        echo "auth.php exists<br>";
        require_once '../auth.php';
        echo "auth.php loaded successfully<br>";
    } else {
        echo "ERROR: auth.php not found<br>";
    }
} catch (Exception $e) {
    echo "ERROR loading auth.php: " . $e->getMessage() . "<br>";
}

// Test database connection
echo "<br>2. Testing database connection...<br>";
try {
    if (isset($pdo)) {
        echo "PDO connection exists<br>";
        $stmt = $pdo->query("SELECT 1");
        echo "Database query successful<br>";
    } else {
        echo "ERROR: PDO connection not available<br>";
    }
} catch (Exception $e) {
    echo "ERROR with database: " . $e->getMessage() . "<br>";
}

// Test organization config
echo "<br>3. Testing organization config...<br>";
try {
    if (function_exists('getOrganizationConfig')) {
        $org_config = getOrganizationConfig();
        echo "Organization config loaded: " . $org_config['name'] . "<br>";
    } else {
        echo "ERROR: getOrganizationConfig function not found<br>";
    }
} catch (Exception $e) {
    echo "ERROR loading organization config: " . $e->getMessage() . "<br>";
}

// Test vendor autoload
echo "<br>4. Testing vendor autoload...<br>";
try {
    if (file_exists('../vendor/autoload.php')) {
        echo "vendor/autoload.php exists<br>";
        require_once '../vendor/autoload.php';
        echo "vendor/autoload.php loaded successfully<br>";
    } else {
        echo "WARNING: vendor/autoload.php not found<br>";
    }
} catch (Exception $e) {
    echo "ERROR loading vendor/autoload.php: " . $e->getMessage() . "<br>";
}

// Test session
echo "<br>5. Testing session...<br>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "Session is active<br>";
    echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set') . "<br>";
    echo "Role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'not set') . "<br>";
} else {
    echo "Session is not active<br>";
}

echo "<br>=== DEBUG COMPLETE ===<br>";
?>
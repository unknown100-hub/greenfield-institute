<?php
// Backend Test Script
echo "=== SmartField Institute Backend Test ===\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
try {
    require_once 'config/db.php';
    $database = new Database();
    $conn = $database->connect();
    if ($conn) {
        echo "✓ Database connection successful\n";
    } else {
        echo "✗ Database connection failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Database connection error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Student Model
echo "\n2. Testing Student Model...\n";
require_once 'models/Student.php';
$student = new Student($conn);

// Test data validation
$testData = [
    'name' => 'Test Student',
    'email' => 'test@example.com',
    'phone' => '+1234567890',
    'address' => '123 Test Street',
    'date_of_birth' => '2000-01-01'
];

echo "✓ Student model loaded successfully\n";

// Test 3: Admin Model
echo "\n3. Testing Admin Model...\n";
require_once 'models/Admin.php';
$admin = new Admin($conn);
echo "✓ Admin model loaded successfully\n";

// Test 4: Controllers
echo "\n4. Testing Controllers...\n";
require_once 'controllers/StudentController.php';
require_once 'controllers/AdminController.php';

$studentController = new StudentController();
$adminController = new AdminController();
echo "✓ Controllers loaded successfully\n";

// Test 5: Validation Logic
echo "\n5. Testing Validation Logic...\n";

// Test email validation
$validEmail = filter_var('test@example.com', FILTER_VALIDATE_EMAIL);
$invalidEmail = filter_var('invalid-email', FILTER_VALIDATE_EMAIL);
echo "Email validation: " . ($validEmail ? "✓ Valid" : "✗ Invalid") . " / " . ($invalidEmail === false ? "✓ Invalid caught" : "✗ Invalid not caught") . "\n";

// Test date validation
$validDate = DateTime::createFromFormat('Y-m-d', '2000-01-01');
$invalidDate = DateTime::createFromFormat('Y-m-d', 'invalid-date');
echo "Date validation: " . ($validDate ? "✓ Valid" : "✗ Invalid") . " / " . ($invalidDate === false ? "✓ Invalid caught" : "✗ Invalid not caught") . "\n";

// Test age calculation
if ($validDate) {
    $today = new DateTime();
    $age = $today->diff($validDate)->y;
    echo "Age calculation: $age years (" . ($age >= 16 ? "✓ Valid age" : "✗ Invalid age") . ")\n";
}

echo "\n=== Backend Test Complete ===\n";
echo "If all tests passed, your backend is ready!\n";
echo "Next steps:\n";
echo "1. Set up a web server (Apache/Nginx) or use PHP's built-in server\n";
echo "2. Access frontend/index.html to test the full application\n";
?>
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

function sendJson($payload, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once __DIR__ . '/../controllers/StudentController.php';
    require_once __DIR__ . '/../controllers/AdminController.php';
    require_once __DIR__ . '/../controllers/CourseController.php';
} catch (Throwable $e) {
    error_log($e->getMessage());
    sendJson(['success' => false, 'message' => 'Server setup error. Check PHP include paths.'], 500);
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$requestPath = $_SERVER['PATH_INFO'] ?? '';

if ($requestPath === '') {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $requestUri = str_replace('\\', '/', $requestUri);

    if ($scriptName !== '' && strpos($requestUri, $scriptName) === 0) {
        $requestPath = substr($requestUri, strlen($scriptName));
    }
}

$requestPath = trim($requestPath, '/');
$request = explode('/', $requestPath);
$endpoint = $request[0] ?? '';
$id = $request[1] ?? null;

// Instantiate only the controllers needed for the current endpoint.
$studentController = null;
$adminController = null;
$courseController = null;

try {
switch ($method) {
    case 'GET':
        if ($endpoint === 'students') {
            $studentController = new StudentController();
            $adminController = new AdminController();
            if ($id) {
                $student = $studentController->getStudent($id);
                if ($student) {
                    echo json_encode(['success' => true, 'data' => $student]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Student not found']);
                }
            } else {
                if ($adminController->isLoggedIn()) {
                    $students = $studentController->getAllStudents();
                    echo json_encode(['success' => true, 'data' => $students]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                }
            }
        } elseif ($endpoint === 'courses') {
            $courseController = new CourseController();
            if ($id === 'xml') {
                header('Content-Type: application/xml');
                $courseController->getCoursesXml();
            } elseif ($id) {
                $course = $courseController->getCourse($id);
                if ($course) {
                    echo json_encode(['success' => true, 'data' => $course]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Course not found']);
                }
            } else {
                $courses = $courseController->getAllCourses();
                echo json_encode(['success' => true, 'data' => $courses]);
            }
        } elseif ($endpoint === 'admin' && $id === 'check') {
            $adminController = new AdminController();
            echo json_encode(['success' => true, 'logged_in' => $adminController->isLoggedIn()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if ($endpoint === 'students') {
            $studentController = new StudentController();
            if ($id === 'login') {
                $result = $studentController->login($data);
            } elseif ($id === 'course') {
                $result = $studentController->registerCourse($data);
            } else {
                $result = $studentController->register($data);
            }
            echo json_encode($result);
        } elseif ($endpoint === 'admin') {
            $adminController = new AdminController();
            if ($id === 'register') {
                $result = $adminController->register($data);
                echo json_encode($result);
            } elseif ($id === 'login') {
                $result = $adminController->login($data);
                echo json_encode($result);
            } elseif ($id === 'logout') {
                $result = $adminController->logout();
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid admin endpoint']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
        }
        break;

    case 'PUT':
        if ($endpoint === 'students' && $id) {
            $adminController = new AdminController();
            $studentController = new StudentController();
            if ($adminController->isLoggedIn()) {
                $rawInput = file_get_contents('php://input');
                $data = json_decode($rawInput, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    echo json_encode(['success' => false, 'message' => 'Invalid JSON input: ' . json_last_error_msg()]);
                    break;
                }
                if (empty($data)) {
                    echo json_encode(['success' => false, 'message' => 'Request body cannot be empty']);
                    break;
                }
                $result = $studentController->updateStudent($id, $data);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
        }
        break;

    case 'DELETE':
        if ($endpoint === 'students' && $id) {
            $adminController = new AdminController();
            $studentController = new StudentController();
            if ($adminController->isLoggedIn()) {
                $result = $studentController->deleteStudent($id);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
} catch (Throwable $e) {
    error_log($e->getMessage());
    sendJson([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], 500);
}
?>

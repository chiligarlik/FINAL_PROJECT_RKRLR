<?php
// api/index.php
require_once __DIR__ . '/config/headers.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/TicketController.php';
require_once __DIR__ . '/controllers/UserController.php';

// Parse request URI
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = explode('/', trim($request, '/'));

// API base is 'api'
if ($request[0] !== 'api') {
    http_response_code(404);
    echo json_encode(['message' => 'Not Found']);
    exit();
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Route the request
switch ($request[1]) {
    case 'auth':
        $auth = new AuthController($pdo);
        switch ($method) {
            case 'POST':
                if ($request[2] === 'login') {
                    $auth->login();
                } elseif ($request[2] === 'logout') {
                    $auth->logout();
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['message' => 'Method Not Allowed']);
        }
        break;
        
    case 'tickets':
        $ticket = new TicketController($pdo);
        $id = isset($request[2]) ? $request[2] : null;
        
        switch ($method) {
            case 'GET':
                if ($id) {
                    $ticket->getTicket($id);
                } else {
                    $ticket->getTickets();
                }
                break;
            case 'POST':
                $ticket->createTicket();
                break;
            case 'PUT':
                if ($id) {
                    $ticket->updateTicket($id);
                }
                break;
            case 'DELETE':
                if ($id) {
                    $ticket->deleteTicket($id);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['message' => 'Method Not Allowed']);
        }
        break;
        
    case 'users':
        $user = new UserController($pdo);
        $id = isset($request[2]) ? $request[2] : null;
        
        switch ($method) {
            case 'GET':
                if ($id) {
                    $user->getUser($id);
                } else {
                    $user->getUsers();
                }
                break;
            case 'POST':
                $user->createUser();
                break;
            case 'PUT':
                if ($id) {
                    $user->updateUser($id);
                }
                break;
            case 'DELETE':
                if ($id) {
                    $user->deleteUser($id);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['message' => 'Method Not Allowed']);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['message' => 'Not Found']);
}
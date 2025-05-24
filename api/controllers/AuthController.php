<?php
// api/controllers/AuthController.php
class AuthController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        session_start();
    }
    
    public function login() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->username) && !empty($data->password)) {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$data->username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($data->password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department'] = $user['department'];
                
                http_response_code(200);
                echo json_encode([
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role'],
                        'department' => $user['department']
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['message' => 'Login failed']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Missing username or password']);
        }
    }
    
    public function logout() {
        session_unset();
        session_destroy();
        http_response_code(200);
        echo json_encode(['message' => 'Logged out successfully']);
    }
}
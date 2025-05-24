<?php
// api/controllers/UserController.php
class UserController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->checkAuth();
    }
    
    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            exit();
        }
        
        // Only allow admins to manage users
        if ($_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden']);
            exit();
        }
    }
    
    public function getUsers() {
        $stmt = $this->pdo->query("SELECT id, username, full_name, email, role, department FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($users);
    }
    
    public function getUser($id) {
        $stmt = $this->pdo->prepare("SELECT id, username, full_name, email, role, department FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            http_response_code(200);
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'User not found']);
        }
    }
    
    public function createUser() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->username) && !empty($data->password) && !empty($data->full_name) && 
            !empty($data->email) && !empty($data->role) && !empty($data->department)) {
            
            // Check if username exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$data->username]);
            
            if ($stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(['message' => 'Username already exists']);
                return;
            }
            
            $password = password_hash($data->password, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("INSERT INTO users (username, password, full_name, email, role, department) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data->username,
                $password,
                $data->full_name,
                $data->email,
                $data->role,
                $data->department
            ]);
            
            http_response_code(201);
            echo json_encode(['message' => 'User created']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Missing required fields']);
        }
    }
    
    public function updateUser($id) {
        $data = json_decode(file_get_contents("php://input"));
        
        // First check if user exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['message' => 'User not found']);
            return;
        }
        
        // Update fields
        $updates = [];
        $params = [];
        
        if (!empty($data->username)) {
            // Check if new username is available
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$data->username, $id]);
            
            if ($stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(['message' => 'Username already taken']);
                return;
            }
            
            $updates[] = "username = ?";
            $params[] = $data->username;
        }
        
        if (!empty($data->password)) {
            $updates[] = "password = ?";
            $params[] = password_hash($data->password, PASSWORD_DEFAULT);
        }
        
        if (!empty($data->full_name)) {
            $updates[] = "full_name = ?";
            $params[] = $data->full_name;
        }
        
        if (!empty($data->email)) {
            $updates[] = "email = ?";
            $params[] = $data->email;
        }
        
        if (!empty($data->role)) {
            $updates[] = "role = ?";
            $params[] = $data->role;
        }
        
        if (!empty($data->department)) {
            $updates[] = "department = ?";
            $params[] = $data->department;
        }
        
        if (!empty($updates)) {
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            http_response_code(200);
            echo json_encode(['message' => 'User updated']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'No fields to update']);
        }
    }
    
    public function deleteUser($id) {
        // Prevent deleting yourself
        if ($id == $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['message' => 'Cannot delete your own account']);
            return;
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'User deleted']);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'User not found']);
        }
    }
}
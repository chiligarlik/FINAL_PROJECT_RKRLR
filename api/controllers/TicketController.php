<?php
// api/controllers/TicketController.php
class TicketController {
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
    }
    
    public function getTickets() {
        $department = $_SESSION['department'];
        $role = $_SESSION['role'];
        $user_id = $_SESSION['user_id'];
        
        $sql = "SELECT t.*, u1.username as created_by_username, u2.username as assigned_to_username 
                FROM tickets t
                LEFT JOIN users u1 ON t.created_by = u1.id
                LEFT JOIN users u2 ON t.assigned_to = u2.id
                WHERE t.department = ?";
        
        if ($role === 'junior_officer') {
            $sql .= " AND (t.severity != 'critical' OR t.assigned_to = ?)";
            $params = [$department, $user_id];
        } else {
            $params = [$department];
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($tickets);
    }
    
    public function getTicket($id) {
        $stmt = $this->pdo->prepare("SELECT t.*, u1.username as created_by_username, u2.username as assigned_to_username 
                                   FROM tickets t
                                   LEFT JOIN users u1 ON t.created_by = u1.id
                                   LEFT JOIN users u2 ON t.assigned_to = u2.id
                                   WHERE t.id = ?");
        $stmt->execute([$id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ticket) {
            // Check permissions
            if ($ticket['department'] !== $_SESSION['department']) {
                http_response_code(403);
                echo json_encode(['message' => 'Forbidden']);
                return;
            }
            
            if ($_SESSION['role'] === 'junior_officer' && $ticket['severity'] === 'critical' && $ticket['assigned_to'] !== $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['message' => 'Forbidden']);
                return;
            }
            
            // Get remarks
            $stmt = $this->pdo->prepare("SELECT r.*, u.username FROM remarks r JOIN users u ON r.user_id = u.id WHERE r.ticket_id = ? ORDER BY r.created_at DESC");
            $stmt->execute([$id]);
            $remarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $ticket['remarks'] = $remarks;
            
            http_response_code(200);
            echo json_encode($ticket);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Ticket not found']);
        }
    }
    
    public function createTicket() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->title) && !empty($data->description) && !empty($data->severity)) {
            $stmt = $this->pdo->prepare("INSERT INTO tickets (title, description, severity, department, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $data->title,
                $data->description,
                $data->severity,
                $_SESSION['department'],
                $_SESSION['user_id']
            ]);
            
            http_response_code(201);
            echo json_encode(['message' => 'Ticket created']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Missing required fields']);
        }
    }
    
    public function updateTicket($id) {
        $data = json_decode(file_get_contents("php://input"));
        
        // First check if ticket exists and user has permission
        $stmt = $this->pdo->prepare("SELECT * FROM tickets WHERE id = ?");
        $stmt->execute([$id]);
        $ticket = $stmt->fetch();
        
        if (!$ticket) {
            http_response_code(404);
            echo json_encode(['message' => 'Ticket not found']);
            return;
        }
        
        if ($ticket['department'] !== $_SESSION['department']) {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden']);
            return;
        }
        
        // Update fields
        $updates = [];
        $params = [];
        
        if (!empty($data->title)) {
            $updates[] = "title = ?";
            $params[] = $data->title;
        }
        
        if (!empty($data->description)) {
            $updates[] = "description = ?";
            $params[] = $data->description;
        }
        
        if (!empty($data->severity)) {
            $updates[] = "severity = ?";
            $params[] = $data->severity;
        }
        
        if (!empty($data->status)) {
            $updates[] = "status = ?";
            $params[] = $data->status;
        }
        
        if (!empty($data->assigned_to) && ($_SESSION['role'] === 'supervisor' || $_SESSION['role'] === 'admin')) {
            $updates[] = "assigned_to = ?";
            $params[] = $data->assigned_to;
        }
        
        if (!empty($updates)) {
            $sql = "UPDATE tickets SET " . implode(', ', $updates) . " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            http_response_code(200);
            echo json_encode(['message' => 'Ticket updated']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'No fields to update']);
        }
    }
    
    public function deleteTicket($id) {
        // Only allow admins to delete tickets
        if ($_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['message' => 'Forbidden']);
            return;
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM tickets WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Ticket deleted']);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Ticket not found']);
        }
    }
}
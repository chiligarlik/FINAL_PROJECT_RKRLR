<?php
require_once '../helpdesk-system/api/config/database.php';
require_once 'includes/auth.php';

redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$department = $_SESSION['department'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
        .menu { display: flex; gap: 15px; margin-bottom: 20px; }
        .menu a { padding: 8px 15px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; }
        .menu a:hover { background: #45a049; }
        .ticket-list { display: grid; gap: 15px; }
        .ticket { padding: 15px; border: 1px solid #ddd; border-radius: 5px; background: white; }
        .ticket h3 { margin: 0 0 10px 0; color: #333; }
        .ticket-meta { display: flex; gap: 15px; font-size: 14px; color: #666; }
        .view-btn { display: inline-block; margin-top: 10px; padding: 5px 10px; background: #2196F3; color: white; text-decoration: none; border-radius: 3px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h2>
            <div>
                <span style="color:#666;"><?= ucfirst(str_replace('_', ' ', $role)) ?></span> | 
                <a href="includes/logout.php" style="color:#4CAF50;">Logout</a>
            </div>
        </div>
        
        <div class="menu">
            <a href="tickets/create.php">Create Ticket</a>
            <a href="tickets/view.php">View Tickets</a>
            <?php if ($role === 'admin' || $role === 'supervisor'): ?>
                <a href="users/">Manage Users</a>
            <?php endif; ?>
        </div>
        
        <h3>Recent Tickets</h3>
        <div class="ticket-list">
            <?php
            $query = "SELECT t.*, u.username as created_by_username 
                     FROM tickets t
                     JOIN users u ON t.created_by = u.id
                     WHERE t.department = ?";
            
            if ($role === 'junior_officer') {
                $query .= " AND (t.severity != 'critical' OR t.assigned_to = ?)";
                $params = [$department, $user_id];
            } else {
                $params = [$department];
            }
            
            $query .= " ORDER BY t.created_at DESC LIMIT 5";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            while ($ticket = $stmt->fetch()):
            ?>
                <div class="ticket">
                    <h3><?= htmlspecialchars($ticket['title']) ?></h3>
                    <p><?= htmlspecialchars($ticket['description']) ?></p>
                    <div class="ticket-meta">
                        <span>Status: <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span>
                        <span>Severity: <?= ucfirst($ticket['severity']) ?></span>
                        <span>Created by: <?= htmlspecialchars($ticket['created_by_username']) ?></span>
                    </div>
                    <a href="tickets/manage.php?id=<?= $ticket['id'] ?>" class="view-btn">View Details</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
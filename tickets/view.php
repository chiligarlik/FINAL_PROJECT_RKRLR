<?php
require_once '../api/config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

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

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Tickets</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h2 {
            margin-top: 0;
            color: #333;
        }
        .btn {
            padding: 8px 15px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 15px;
        }
        .btn:hover {
            background: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f8f8;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .severity-low { color: #2ecc71; }
        .severity-medium { color: #f39c12; }
        .severity-high { color: #e74c3c; }
        .severity-critical { color: #c0392b; font-weight: bold; }
        .action-link {
            color: #3498db;
            text-decoration: none;
        }
        .action-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Tickets in <?= htmlspecialchars($department) ?> Department</h2>
        <a href="create.php" class="btn">Create New Ticket</a>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Assigned To</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td><?= $ticket['id'] ?></td>
                    <td><?= htmlspecialchars($ticket['title']) ?></td>
                    <td class="severity-<?= $ticket['severity'] ?>"><?= ucfirst($ticket['severity']) ?></td>
                    <td><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></td>
                    <td><?= htmlspecialchars($ticket['created_by_username']) ?></td>
                    <td><?= $ticket['assigned_to_username'] ? htmlspecialchars($ticket['assigned_to_username']) : 'Unassigned' ?></td>
                    <td>
                        <a href="manage.php?id=<?= $ticket['id'] ?>" class="action-link">Manage</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
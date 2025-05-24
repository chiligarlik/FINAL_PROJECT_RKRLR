<?php
require_once '../api/config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) {
    header('Location: view.php');
    exit();
}

// Get ticket details
$stmt = $pdo->prepare("SELECT t.*, u1.username as created_by_username, u2.username as assigned_to_username 
                       FROM tickets t
                       LEFT JOIN users u1 ON t.created_by = u1.id
                       LEFT JOIN users u2 ON t.assigned_to = u2.id
                       WHERE t.id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

// Check if ticket exists
if (!$ticket) {
    header('Location: view.php?error=Ticket not found');
    exit();
}

// Check if user has access to this ticket
if ($ticket['department'] !== $_SESSION['department']) {
    header('Location: view.php?error=Access denied');
    exit();
}

// Check if junior officer is trying to access critical ticket
if ($_SESSION['role'] === 'junior_officer' && $ticket['severity'] === 'critical' && $ticket['assigned_to'] !== $_SESSION['user_id']) {
    header('Location: view.php?error=Access denied');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_remark'])) {
        // Add remark
        $remark = $_POST['remark'];
        $stmt = $pdo->prepare("INSERT INTO remarks (ticket_id, user_id, remark) VALUES (?, ?, ?)");
        $stmt->execute([$ticket_id, $_SESSION['user_id'], $remark]);
    } elseif (isset($_POST['update_status'])) {
        // Update status
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        $stmt->execute([$status, $ticket_id]);
    } elseif (isset($_POST['assign_ticket']) && ($_SESSION['role'] === 'supervisor' || $_SESSION['role'] === 'admin')) {
        // Assign ticket
        $assigned_to = $_POST['assigned_to'];
        $stmt = $pdo->prepare("UPDATE tickets SET assigned_to = ? WHERE id = ?");
        $stmt->execute([$assigned_to, $ticket_id]);
    }
    
    // Refresh ticket data
    header("Location: manage.php?id=$ticket_id");
    exit();
}

// Get remarks for this ticket
$stmt = $pdo->prepare("SELECT r.*, u.username FROM remarks r JOIN users u ON r.user_id = u.id WHERE r.ticket_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$ticket_id]);
$remarks = $stmt->fetchAll();

// Get users for assignment (only for supervisors/admins)
$users = [];
if ($_SESSION['role'] === 'supervisor' || $_SESSION['role'] === 'admin') {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE department = ?");
    $stmt->execute([$_SESSION['department']]);
    $users = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Ticket #<?= htmlspecialchars($ticket_id) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .ticket-info {
            margin-bottom: 20px;
        }
        .ticket-info p {
            margin: 5px 0;
        }
        .section {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .section h3 {
            margin-top: 0;
            color: #444;
        }
        .remark {
            margin-bottom: 15px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .remark-meta {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            height: 100px;
            resize: vertical;
        }
        select, input[type="text"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 8px 15px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn:hover {
            background: #45a049;
        }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .severity-critical {
            color: #c0392b;
            font-weight: bold;
        }
        .severity-high {
            color: #e74c3c;
        }
        .severity-medium {
            color: #f39c12;
        }
        .severity-low {
            color: #2ecc71;
        }
        .error-message {
            color: #e74c3c;
            padding: 10px;
            background: #fdecea;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Ticket #<?= htmlspecialchars($ticket_id) ?>: <?= htmlspecialchars($ticket['title']) ?></h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>
        
        <div class="ticket-info">
            <p><strong>Description:</strong> <?= htmlspecialchars($ticket['description']) ?></p>
            <p><strong>Severity:</strong> <span class="severity-<?= $ticket['severity'] ?>"><?= ucfirst($ticket['severity']) ?></span></p>
            <p><strong>Status:</strong> <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></p>
            <p><strong>Created By:</strong> <?= htmlspecialchars($ticket['created_by_username']) ?></p>
            <p><strong>Assigned To:</strong> <?= $ticket['assigned_to_username'] ? htmlspecialchars($ticket['assigned_to_username']) : 'Unassigned' ?></p>
        </div>
        
        <div class="section">
            <h3>Remarks</h3>
            <?php if (empty($remarks)): ?>
                <p>No remarks yet.</p>
            <?php else: ?>
                <?php foreach ($remarks as $remark): ?>
                <div class="remark">
                    <div class="remark-meta">
                        <strong><?= htmlspecialchars($remark['username']) ?></strong> on <?= $remark['created_at'] ?>
                    </div>
                    <p><?= htmlspecialchars($remark['remark']) ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <h3>Add Remark</h3>
            <form method="post">
                <textarea name="remark" placeholder="Enter your remark here..." required></textarea>
                <button type="submit" name="add_remark" class="btn">Add Remark</button>
            </form>
        </div>
        
        <div class="section">
            <h3>Update Status</h3>
            <form method="post">
                <select name="status">
                    <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                    <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                </select>
                <button type="submit" name="update_status" class="btn">Update Status</button>
            </form>
        </div>
        
        <?php if ($_SESSION['role'] === 'supervisor' || $_SESSION['role'] === 'admin'): ?>
        <div class="section">
            <h3>Assign Ticket</h3>
            <form method="post">
                <select name="assigned_to">
                    <option value="">Unassign</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?= $user['id'] ?>" <?= $ticket['assigned_to'] == $user['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['username']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="assign_ticket" class="btn">Assign</button>
            </form>
        </div>
        <?php endif; ?>
        
        <a href="view.php" class="back-link">← Back to Tickets</a>
    </div>
</body>
</html>
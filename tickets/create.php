<?php
require_once '../api/config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $severity = $_POST['severity'];
    $department = $_SESSION['department'];
    $created_by = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("INSERT INTO tickets (title, description, severity, department, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $severity, $department, $created_by]);
    
    header('Location: view.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Ticket</title>
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
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group textarea {
            height: 150px;
            resize: vertical;
        }
        .btn {
            padding: 10px 15px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #45a049;
        }
        .back-link {
            margin-top: 15px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Create New Ticket</h2>
        <form method="post">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" placeholder="Enter ticket title" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Describe the issue in detail" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="severity">Severity</label>
                <select id="severity" name="severity" required>
                    <option value="">Select severity level</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            
            <button type="submit" class="btn">Create Ticket</button>
            <a href="view.php" class="back-link">Cancel</a>
        </form>
    </div>
</body>
</html>
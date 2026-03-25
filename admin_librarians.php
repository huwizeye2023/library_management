<?php
require_once 'config.php';
requireRole('admin');

$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid request';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action == 'add') {
            $names = sanitize($_POST['names']);
            $email = sanitize($_POST['email']);
            $telephone = sanitize($_POST['telephone']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO librarians (names, email, telephone, password) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$names, $email, $telephone, $password])) {
                    $message = 'Librarian added successfully!';
                }
            } catch(PDOException $e) {
                $message = 'Error: Email already exists';
            }
        } elseif ($action == 'toggle_status') {
            $librarian_id = intval($_POST['librarian_id']);
            $current_status = $_POST['current_status'];
            $new_status = ($current_status == 'active') ? 'inactive' : 'active';
            
            $stmt = $pdo->prepare("UPDATE librarians SET status = ? WHERE librarian_id = ?");
            if ($stmt->execute([$new_status, $librarian_id])) {
                $message = 'Librarian status updated!';
            }
        } elseif ($action == 'delete') {
            $librarian_id = intval($_POST['librarian_id']);
            $stmt = $pdo->prepare("DELETE FROM librarians WHERE librarian_id = ?");
            if ($stmt->execute([$librarian_id])) {
                $message = 'Librarian deleted successfully!';
            }
        }
    }
}

// Fetch all librarians
$stmt = $pdo->query("SELECT * FROM librarians ORDER BY librarian_id DESC");
$librarians = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarians Management - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; }
        .nav-links { display: flex; gap: 15px; }
        .nav-links a { color: white; text-decoration: none; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 5px; }
        .container { padding: 40px; max-width: 1400px; margin: 0 auto; }
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .content-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .form-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); height: fit-content; }
        .form-card h3 { margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #5568d3; }
        .table-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table-card h3 { margin-bottom: 20px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-inactive { color: #dc3545; font-weight: bold; }
        .btn-small { padding: 5px 10px; background: #667eea; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Librarians Management - Admin</h1>
        <div class="nav-links">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="form-card">
                <h3>Add New Librarian</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="names" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Telephone</label>
                        <input type="text" name="telephone" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required minlength="6">
                    </div>
                    <button type="submit" class="btn">Add Librarian</button>
                </form>
            </div>

            <div class="table-card">
                <h3>All Librarians</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($librarians as $librarian): ?>
                        <tr>
                            <td><?php echo $librarian['librarian_id']; ?></td>
                            <td><?php echo htmlspecialchars($librarian['names']); ?></td>
                            <td><?php echo htmlspecialchars($librarian['email']); ?></td>
                            <td><?php echo htmlspecialchars($librarian['telephone']); ?></td>
                            <td class="status-<?php echo $librarian['status']; ?>"><?php echo ucfirst($librarian['status']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($librarian['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="librarian_id" value="<?php echo $librarian['librarian_id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $librarian['status']; ?>">
                                    <button type="submit" class="btn-small <?php echo $librarian['status'] == 'active' ? 'btn-danger' : 'btn-success'; ?>">
                                        <?php echo $librarian['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="librarian_id" value="<?php echo $librarian['librarian_id']; ?>">
                                    <button type="submit" class="btn-small btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

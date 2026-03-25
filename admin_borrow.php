<?php
require_once 'config.php';
requireRole('admin');

$message = '';

// Handle approve/deny actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid request';
    } else {
        $action = $_POST['action'] ?? '';
        $borrow_id = intval($_POST['borrow_id']);
        
        if ($action == 'approve') {
            $stmt = $pdo->prepare("SELECT book_id FROM borrow WHERE borrow_id = ?");
            $stmt->execute([$borrow_id]);
            $borrow = $stmt->fetch();
            
            if ($borrow) {
                $stmt = $pdo->prepare("SELECT available_quantity FROM books WHERE book_id = ?");
                $stmt->execute([$borrow['book_id']]);
                $book = $stmt->fetch();
                
                if ($book && $book['available_quantity'] > 0) {
                    $stmt = $pdo->prepare("UPDATE borrow SET status = 'borrowed' WHERE borrow_id = ?");
                    $stmt->execute([$borrow_id]);
                    
                    $stmt = $pdo->prepare("UPDATE books SET available_quantity = available_quantity - 1 WHERE book_id = ?");
                    $stmt->execute([$borrow['book_id']]);
                    
                    $message = 'Borrow request approved!';
                } else {
                    $message = 'Book is not available!';
                }
            }
        } elseif ($action == 'deny') {
            $stmt = $pdo->prepare("UPDATE borrow SET status = 'denied' WHERE borrow_id = ?");
            $stmt->execute([$borrow_id]);
            $message = 'Borrow request denied!';
        }
    }
}

// Fetch pending requests
$stmt = $pdo->query("
    SELECT b.*, m.name as member_name, m.email as member_email, 
           bk.title as book_title, bk.author
    FROM borrow b
    JOIN members m ON b.member_id = m.member_id
    JOIN books bk ON b.book_id = bk.book_id
    WHERE b.status = 'pending'
    ORDER BY b.created_at DESC
");
$pending_requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Requests - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; }
        .nav-links { display: flex; gap: 15px; }
        .nav-links a { color: white; text-decoration: none; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 5px; }
        .container { padding: 40px; max-width: 1400px; margin: 0 auto; }
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .table-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table-card h3 { margin-bottom: 20px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .btn-small { padding: 5px 10px; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; }
        .btn-approve { background: #28a745; }
        .btn-deny { background: #dc3545; }
        .empty-state { text-align: center; padding: 40px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Borrow Requests - Admin</h1>
        <div class="nav-links">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="table-card">
            <h3>Pending Requests (<?php echo count($pending_requests); ?>)</h3>
            
            <?php if (empty($pending_requests)): ?>
                <div class="empty-state">
                    <p>No pending requests</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Member</th>
                            <th>Book</th>
                            <th>Borrow Date</th>
                            <th>Return Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_requests as $request): ?>
                        <tr>
                            <td><?php echo $request['borrow_id']; ?></td>
                            <td><?php echo htmlspecialchars($request['member_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['book_title']); ?></td>
                            <td><?php echo $request['borrow_date']; ?></td>
                            <td><?php echo $request['return_date']; ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="borrow_id" value="<?php echo $request['borrow_id']; ?>">
                                    <button type="submit" class="btn-small btn-approve">Approve</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="action" value="deny">
                                    <input type="hidden" name="borrow_id" value="<?php echo $request['borrow_id']; ?>">
                                    <button type="submit" class="btn-small btn-deny">Deny</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

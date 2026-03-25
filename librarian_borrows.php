<?php
require_once 'config.php';
requireRole('librarian');

$message = '';

// Handle return processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid request';
    } else {
        $action = $_POST['action'] ?? '';
        $borrow_id = intval($_POST['borrow_id']);
        
        if ($action == 'return') {
            $stmt = $pdo->prepare("SELECT book_id, return_date FROM borrow WHERE borrow_id = ?");
            $stmt->execute([$borrow_id]);
            $borrow = $stmt->fetch();
            
            if ($borrow) {
                $today = date('Y-m-d');
                $is_overdue = strtotime($borrow['return_date']) < strtotime($today);
                $status = $is_overdue ? 'overdue' : 'returned';
                
                $stmt = $pdo->prepare("UPDATE borrow SET status = ?, actual_return_date = ? WHERE borrow_id = ?");
                $stmt->execute([$status, $today, $borrow_id]);
                
                $stmt = $pdo->prepare("UPDATE books SET available_quantity = available_quantity + 1 WHERE book_id = ?");
                $stmt->execute([$borrow['book_id']]);
                
                $message = $is_overdue ? 'Book returned but was overdue!' : 'Book returned successfully!';
            }
        }
    }
}

// Fetch all borrows
$stmt = $pdo->query("
    SELECT b.*, m.name as member_name, m.email as member_email, 
           bk.title as book_title, bk.author
    FROM borrow b
    JOIN members m ON b.member_id = m.member_id
    JOIN books bk ON b.book_id = bk.book_id
    ORDER BY b.created_at DESC
");
$borrows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Borrows - Librarian</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; }
        .nav-links { display: flex; gap: 15px; }
        .nav-links a { color: white; text-decoration: none; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 5px; }
        .container { padding: 40px; max-width: 1400px; margin: 0 auto; }
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .table-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table-card h3 { margin-bottom: 20px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        th { background: #28a745; color: white; }
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-approved { color: #17a2b8; font-weight: bold; }
        .status-denied { color: #dc3545; font-weight: bold; }
        .status-borrowed { color: #28a745; font-weight: bold; }
        .status-returned { color: #6c757d; font-weight: bold; }
        .status-overdue { color: #dc3545; font-weight: bold; }
        .btn-small { padding: 5px 10px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>All Borrows - Librarian</h1>
        <div class="nav-links">
            <a href="librarian_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="table-card">
            <h3>All Borrow Records</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Member</th>
                        <th>Book</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Actual Return</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($borrows as $borrow): ?>
                    <tr>
                        <td><?php echo $borrow['borrow_id']; ?></td>
                        <td><?php echo htmlspecialchars($borrow['member_name']); ?></td>
                        <td><?php echo htmlspecialchars($borrow['book_title']); ?></td>
                        <td><?php echo $borrow['borrow_date']; ?></td>
                        <td><?php echo $borrow['return_date']; ?></td>
                        <td><?php echo $borrow['actual_return_date'] ?? '-'; ?></td>
                        <td class="status-<?php echo $borrow['status']; ?>"><?php echo ucfirst($borrow['status']); ?></td>
                        <td>
                            <?php if ($borrow['status'] == 'borrowed'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="action" value="return">
                                    <input type="hidden" name="borrow_id" value="<?php echo $borrow['borrow_id']; ?>">
                                    <button type="submit" class="btn-small">Return</button>
                                </form>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

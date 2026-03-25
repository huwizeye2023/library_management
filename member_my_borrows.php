<?php
require_once 'config.php';
requireRole('member');

$member_id = getUserId();

// Fetch member's borrow records
$stmt = $pdo->prepare("
    SELECT b.*, bk.title as book_title, bk.author
    FROM borrow b
    JOIN books bk ON b.book_id = bk.book_id
    WHERE b.member_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$member_id]);
$borrows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Borrows - Member</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        .header { background: linear-gradient(135deg, #007bff 0%, #6610f2 100%); color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; }
        .nav-links { display: flex; gap: 15px; }
        .nav-links a { color: white; text-decoration: none; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 5px; }
        .container { padding: 40px; max-width: 1400px; margin: 0 auto; }
        .table-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table-card h3 { margin-bottom: 20px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-approved { color: #17a2b8; font-weight: bold; }
        .status-denied { color: #dc3545; font-weight: bold; }
        .status-borrowed { color: #28a745; font-weight: bold; }
        .status-returned { color: #6c757d; font-weight: bold; }
        .status-overdue { color: #dc3545; font-weight: bold; }
        .empty-state { text-align: center; padding: 40px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>My Borrows - Member</h1>
        <div class="nav-links">
            <a href="member_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="table-card">
            <h3>My Borrow History</h3>
            
            <?php if (empty($borrows)): ?>
                <div class="empty-state">
                    <p>You haven't borrowed any books yet</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Book</th>
                            <th>Author</th>
                            <th>Borrow Date</th>
                            <th>Return Date</th>
                            <th>Actual Return</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrows as $borrow): ?>
                        <tr>
                            <td><?php echo $borrow['borrow_id']; ?></td>
                            <td><?php echo htmlspecialchars($borrow['book_title']); ?></td>
                            <td><?php echo htmlspecialchars($borrow['author']); ?></td>
                            <td><?php echo $borrow['borrow_date']; ?></td>
                            <td><?php echo $borrow['return_date']; ?></td>
                            <td><?php echo $borrow['actual_return_date'] ?? '-'; ?></td>
                            <td class="status-<?php echo $borrow['status']; ?>"><?php echo ucfirst($borrow['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

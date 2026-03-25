<?php
require_once 'config.php';
requireRole('librarian');

$message = '';
$librarian_id = getUserId();

// Handle return processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid request';
    } else {
        $action = $_POST['action'] ?? '';
        $borrow_id = intval($_POST['borrow_id']);
        
        if ($action == 'return') {
            // Get book_id first
            $stmt = $pdo->prepare("SELECT book_id, return_date FROM borrow WHERE borrow_id = ?");
            $stmt->execute([$borrow_id]);
            $borrow = $stmt->fetch();
            
            if ($borrow) {
                $today = date('Y-m-d');
                $is_overdue = strtotime($borrow['return_date']) < strtotime($today);
                $status = $is_overdue ? 'overdue' : 'returned';
                
                // Update borrow record
                $stmt = $pdo->prepare("UPDATE borrow SET status = ?, actual_return_date = ? WHERE borrow_id = ?");
                $stmt->execute([$status, $today, $borrow_id]);
                
                // Increase available quantity
                $stmt = $pdo->prepare("UPDATE books SET available_quantity = available_quantity + 1 WHERE book_id = ?");
                $stmt->execute([$borrow['book_id']]);
                
                if ($is_overdue) {
                    $message = 'Book returned but was overdue!';
                } else {
                    $message = 'Book returned successfully!';
                }
            }
        }
    }
}

// Fetch borrowed books
$stmt = $pdo->query("
    SELECT b.*, m.name as member_name, m.email as member_email, 
           bk.title as book_title, bk.author
    FROM borrow b
    JOIN members m ON b.member_id = m.member_id
    JOIN books bk ON b.book_id = bk.book_id
    WHERE b.status = 'borrowed'
    ORDER BY b.return_date ASC
");
$borrowed_books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Returns - Librarian</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; }
        .nav-links { display: flex; gap: 15px; }
        .nav-links a { color: white; text-decoration: none; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 5px; }
        .container { padding: 40px; max-width: 1400px; margin: 0 auto; }
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .table-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table-card h3 { margin-bottom: 20px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #28a745; color: white; }
        .overdue { color: #dc3545; font-weight: bold; }
        .due-soon { color: #ffc107; font-weight: bold; }
        .btn-small { padding: 5px 10px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; }
        .empty-state { text-align: center; padding: 40px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Process Returns - Librarian</h1>
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
            <h3>Books Currently Borrowed (<?php echo count($borrowed_books); ?>)</h3>
            
            <?php if (empty($borrowed_books)): ?>
                <div class="empty-state">
                    <p>No books currently borrowed</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Member</th>
                            <th>Book</th>
                            <th>Author</th>
                            <th>Borrow Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrowed_books as $borrow): 
                            $today = date('Y-m-d');
                            $is_overdue = strtotime($borrow['return_date']) < strtotime($today);
                            $is_due_soon = !$is_overdue && (strtotime($borrow['return_date']) - strtotime($today)) <= (3 * 24 * 60 * 60);
                        ?>
                        <tr>
                            <td><?php echo $borrow['borrow_id']; ?></td>
                            <td><?php echo htmlspecialchars($borrow['member_name']); ?></td>
                            <td><?php echo htmlspecialchars($borrow['book_title']); ?></td>
                            <td><?php echo htmlspecialchars($borrow['author']); ?></td>
                            <td><?php echo $borrow['borrow_date']; ?></td>
                            <td class="<?php echo $is_overdue ? 'overdue' : ($is_due_soon ? 'due-soon' : ''); ?>">
                                <?php echo $borrow['return_date']; ?>
                            </td>
                            <td class="<?php echo $is_overdue ? 'overdue' : ''; ?>">
                                <?php echo $is_overdue ? 'OVERDUE' : 'Active'; ?>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="action" value="return">
                                    <input type="hidden" name="borrow_id" value="<?php echo $borrow['borrow_id']; ?>">
                                    <button type="submit" class="btn-small" onclick="return confirm('Process return for this book?');">Process Return</button>
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

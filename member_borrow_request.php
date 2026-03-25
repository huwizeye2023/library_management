<?php
require_once 'config.php';
requireRole('member');

$member_id = getUserId();
$message = '';
$selected_book = null;

// Get available books
$stmt = $pdo->query("SELECT * FROM books WHERE status = 'active' AND available_quantity > 0 ORDER BY title");
$books = $stmt->fetchAll();

// If book_id is provided, get the selected book
if (isset($_GET['book_id'])) {
    $book_id = intval($_GET['book_id']);
    $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ? AND status = 'active' AND available_quantity > 0");
    $stmt->execute([$book_id]);
    $selected_book = $stmt->fetch();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid request';
    } else {
        $book_id = intval($_POST['book_id']);
        $borrow_date = $_POST['borrow_date'];
        $return_date = $_POST['return_date'];
        $notes = sanitize($_POST['notes']);
        
        // Validation
        if (empty($book_id) || empty($borrow_date) || empty($return_date)) {
            $message = 'All fields are required';
        } elseif (strtotime($return_date) <= strtotime($borrow_date)) {
            $message = 'Return date must be after borrow date';
        } else {
            // Check if member already has this book borrowed
            $stmt = $pdo->prepare("SELECT * FROM borrow WHERE member_id = ? AND book_id = ? AND status IN ('pending', 'borrowed')");
            $stmt->execute([$member_id, $book_id]);
            if ($stmt->fetch()) {
                $message = 'You already have a pending or active request for this book';
            } else {
                // Insert borrow request
                $stmt = $pdo->prepare("INSERT INTO borrow (member_id, book_id, borrow_date, return_date, request_notes) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$member_id, $book_id, $borrow_date, $return_date, $notes])) {
                    $message = 'Borrow request submitted successfully! Waiting for librarian approval.';
                } else {
                    $message = 'Failed to submit request';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Borrow - Member</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        .header { background: linear-gradient(135deg, #007bff 0%, #6610f2 100%); color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; }
        .nav-links { display: flex; gap: 15px; }
        .nav-links a { color: white; text-decoration: none; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 5px; }
        .container { padding: 40px; max-width: 600px; margin: 0 auto; }
        .form-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-card h2 { margin-bottom: 20px; color: #333; }
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 12px 24px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #0056b3; }
        .book-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .book-info h4 { color: #333; margin-bottom: 5px; }
        .book-info p { color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Request Borrow - Member</h1>
        <div class="nav-links">
            <a href="member_dashboard.php">Dashboard</a>
            <a href="member_books.php">Back to Books</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="form-card">
            <h2>Request to Borrow a Book</h2>
            
            <?php if ($message): ?>
                <div class="<?php echo strpos($message, 'success') !== false ? 'message' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($selected_book): ?>
                <div class="book-info">
                    <h4><?php echo htmlspecialchars($selected_book['title']); ?></h4>
                    <p>by <?php echo htmlspecialchars($selected_book['author']); ?> (<?php echo $selected_book['year_published']; ?>)</p>
                    <p>Available: <?php echo $selected_book['available_quantity']; ?> / <?php echo $selected_book['quantity']; ?></p>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                
                <div class="form-group">
                    <label>Select Book</label>
                    <select name="book_id" required>
                        <option value="">-- Select a Book --</option>
                        <?php foreach ($books as $book): ?>
                            <option value="<?php echo $book['book_id']; ?>" <?php echo $selected_book && $selected_book['book_id'] == $book['book_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($book['title']); ?> by <?php echo htmlspecialchars($book['author']); ?> (Available: <?php echo $book['available_quantity']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Borrow Date</label>
                    <input type="date" name="borrow_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Return Date</label>
                    <input type="date" name="return_date" required>
                </div>

                <div class="form-group">
                    <label>Notes (Optional)</label>
                    <textarea name="notes" rows="3" placeholder="Any special requests or notes..."></textarea>
                </div>

                <button type="submit" class="btn">Submit Request</button>
            </form>
        </div>
    </div>
</body>
</html>

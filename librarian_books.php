<?php
require_once 'config.php';
requireRole('librarian');

$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid request';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action == 'add') {
            $title = sanitize($_POST['title']);
            $author = sanitize($_POST['author']);
            $year = intval($_POST['year_published']);
            $quantity = intval($_POST['quantity']);
            
            $stmt = $pdo->prepare("INSERT INTO books (title, author, year_published, quantity, available_quantity) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $author, $year, $quantity, $quantity])) {
                $message = 'Book added successfully!';
            } else {
                $message = 'Failed to add book';
            }
        } elseif ($action == 'update') {
            $book_id = intval($_POST['book_id']);
            $title = sanitize($_POST['title']);
            $author = sanitize($_POST['author']);
            $year = intval($_POST['year_published']);
            $quantity = intval($_POST['quantity']);
            $status = $_POST['status'];
            
            $stmt = $pdo->prepare("UPDATE books SET title = ?, author = ?, year_published = ?, quantity = ?, available_quantity = ?, status = ? WHERE book_id = ?");
            if ($stmt->execute([$title, $author, $year, $quantity, $quantity, $status, $book_id])) {
                $message = 'Book updated successfully!';
            } else {
                $message = 'Failed to update book';
            }
        } elseif ($action == 'delete') {
            $book_id = intval($_POST['book_id']);
            $stmt = $pdo->prepare("UPDATE books SET status = 'archived' WHERE book_id = ?");
            if ($stmt->execute([$book_id])) {
                $message = 'Book deleted successfully!';
            }
        } elseif ($action == 'toggle_status') {
            $book_id = intval($_POST['book_id']);
            $current_status = $_POST['current_status'];
            $new_status = ($current_status == 'active') ? 'inactive' : 'active';
            
            $stmt = $pdo->prepare("UPDATE books SET status = ? WHERE book_id = ?");
            if ($stmt->execute([$new_status, $book_id])) {
                $message = 'Book status updated!';
            }
        }
    }
}

// Fetch all books
$stmt = $pdo->query("SELECT * FROM books WHERE status != 'archived' ORDER BY book_id DESC");
$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books Management - Librarian</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
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
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #218838; }
        .table-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table-card h3 { margin-bottom: 20px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #28a745; color: white; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-inactive { color: #dc3545; font-weight: bold; }
        .btn-small { padding: 5px 10px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Books Management - Librarian</h1>
        <div class="nav-links">
            <a href="librarian_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="form-card">
                <h3>Add New Book</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Author</label>
                        <input type="text" name="author" required>
                    </div>
                    <div class="form-group">
                        <label>Year Published</label>
                        <input type="number" name="year_published" required>
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" required min="1" value="1">
                    </div>
                    <button type="submit" class="btn">Add Book</button>
                </form>
            </div>

            <div class="table-card">
                <h3>All Books</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Year</th>
                            <th>Qty</th>
                            <th>Available</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                        <tr>
                            <td><?php echo $book['book_id']; ?></td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo $book['year_published']; ?></td>
                            <td><?php echo $book['quantity']; ?></td>
                            <td><?php echo $book['available_quantity']; ?></td>
                            <td class="status-<?php echo $book['status']; ?>"><?php echo ucfirst($book['status']); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $book['status']; ?>">
                                    <button type="submit" class="btn-small <?php echo $book['status'] == 'active' ? 'btn-danger' : 'btn-success'; ?>">
                                        <?php echo $book['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
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

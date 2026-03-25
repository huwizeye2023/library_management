<?php
require_once 'config.php';
requireRole('member');

// Fetch available books
$stmt = $pdo->query("SELECT * FROM books WHERE status = 'active' AND available_quantity > 0 ORDER BY title");
$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Books - Member</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        .header { background: linear-gradient(135deg, #007bff 0%, #6610f2 100%); color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; }
        .nav-links { display: flex; gap: 15px; }
        .nav-links a { color: white; text-decoration: none; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 5px; }
        .container { padding: 40px; max-width: 1400px; margin: 0 auto; }
        .books-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .book-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .book-card h3 { color: #333; margin-bottom: 10px; }
        .book-card .author { color: #666; margin-bottom: 10px; }
        .book-card .year { color: #999; font-size: 14px; margin-bottom: 15px; }
        .book-card .available { color: #28a745; font-weight: bold; margin-bottom: 15px; }
        .book-card .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .book-card .btn:hover { background: #0056b3; }
        .empty-state { text-align: center; padding: 40px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Browse Books - Library</h1>
        <div class="nav-links">
            <a href="member_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2 style="margin-bottom: 30px; color: #333;">Available Books (<?php echo count($books); ?>)</h2>
        
        <?php if (empty($books)): ?>
            <div class="empty-state">
                <p>No books available at the moment</p>
            </div>
        <?php else: ?>
            <div class="books-grid">
                <?php foreach ($books as $book): ?>
                <div class="book-card">
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                    <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
                    <p class="year">Published: <?php echo $book['year_published']; ?></p>
                    <p class="available">Available: <?php echo $book['available_quantity']; ?> / <?php echo $book['quantity']; ?></p>
                    <a href="member_borrow_request.php?book_id=<?php echo $book['book_id']; ?>" class="btn">Request to Borrow</a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

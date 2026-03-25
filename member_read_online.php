<?php
require_once 'config.php';
requireRole('member');

$selected_book = null;

// Get books with content
$stmt = $pdo->query("SELECT book_id, title, author, year_published FROM books WHERE status = 'active' AND content IS NOT NULL AND content != '' ORDER BY title");
$books = $stmt->fetchAll();

// If book_id is provided, get the selected book
if (isset($_GET['book_id'])) {
    $book_id = intval($_GET['book_id']);
    $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ? AND status = 'active'");
    $stmt->execute([$book_id]);
    $selected_book = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Read Online - Member</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        .header { background: linear-gradient(135deg, #007bff 0%, #6610f2 100%); color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; }
        .nav-links { display: flex; gap: 15px; }
        .nav-links a { color: white; text-decoration: none; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 5px; }
        .container { padding: 40px; max-width: 1400px; margin: 0 auto; }
        .content-grid { display: grid; grid-template-columns: 300px 1fr; gap: 30px; }
        .book-list { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .book-list h3 { margin-bottom: 15px; color: #333; }
        .book-item { padding: 12px; border-bottom: 1px solid #eee; }
        .book-item:last-child { border-bottom: none; }
        .book-item a { color: #333; text-decoration: none; display: block; }
        .book-item a:hover { color: #007bff; }
        .book-item .title { font-weight: 500; }
        .book-item .author { font-size: 12px; color: #666; }
        .reader { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .reader h2 { color: #333; margin-bottom: 10px; }
        .reader .meta { color: #666; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .reader .content { line-height: 1.8; color: #333; white-space: pre-wrap; }
        .empty-state { text-align: center; padding: 40px; color: #666; }
        .no-content { text-align: center; padding: 40px; color: #666; background: white; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Read Books Online - Library</h1>
        <div class="nav-links">
            <a href="member_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="content-grid">
            <div class="book-list">
                <h3>Available for Online Reading</h3>
                <?php if (empty($books)): ?>
                    <div class="empty-state">
                        <p>No books available for online reading</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($books as $book): ?>
                        <div class="book-item">
                            <a href="?book_id=<?php echo $book['book_id']; ?>">
                                <div class="title"><?php echo htmlspecialchars($book['title']); ?></div>
                                <div class="author">by <?php echo htmlspecialchars($book['author']); ?></div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="reader">
                <?php if ($selected_book): ?>
                    <h2><?php echo htmlspecialchars($selected_book['title']); ?></h2>
                    <p class="meta">by <?php echo htmlspecialchars($selected_book['author']); ?> (<?php echo $selected_book['year_published']; ?>)</p>
                    <div class="content"><?php echo htmlspecialchars($selected_book['content']); ?></div>
                <?php else: ?>
                    <div class="no-content">
                        <h3>Select a book to read</h3>
                        <p>Click on a book from the list to start reading online</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

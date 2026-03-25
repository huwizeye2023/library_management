<?php
require_once 'config.php';
requireRole('librarian');

$username = $_SESSION['username'];
$email = $_SESSION['email'];
$message = '';
$librarian_id = getUserId();

// Handle book entry form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_book') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid request';
    } else {
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
    }
}

// Handle member form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_member') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid request';
    } else {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO members (name, email, phone, password) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $phone, $password])) {
                $message = 'Member added successfully!';
            }
        } catch(PDOException $e) {
            $message = 'Error: Email already exists';
        }
    }
}

// Handle book update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_book') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid request';
    } else {
        $book_id = intval($_POST['book_id']);
        $title = sanitize($_POST['title']);
        $author = sanitize($_POST['author']);
        $year = intval($_POST['year_published']);
        $quantity = intval($_POST['quantity']);
        
        $stmt = $pdo->prepare("UPDATE books SET title = ?, author = ?, year_published = ?, quantity = ?, available_quantity = ? WHERE book_id = ?");
        if ($stmt->execute([$title, $author, $year, $quantity, $quantity, $book_id])) {
            $message = 'Book updated successfully!';
        } else {
            $message = 'Failed to update book';
        }
    }
}

// Handle borrow request actions (approve/deny)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['approve', 'deny'])) {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid request';
    } else {
        $action = $_POST['action'];
        $borrow_id = intval($_POST['borrow_id']);
        
        if ($action == 'approve') {
            $stmt = $pdo->prepare("SELECT book_id, available_quantity FROM books WHERE book_id = (SELECT book_id FROM borrow WHERE borrow_id = ?)");
            $stmt->execute([$borrow_id]);
            $book = $stmt->fetch();
            
            if ($book && $book['available_quantity'] > 0) {
                $stmt = $pdo->prepare("UPDATE borrow SET status = 'borrowed', librarian_id = ? WHERE borrow_id = ?");
                $stmt->execute([$librarian_id, $borrow_id]);
                
                $stmt = $pdo->prepare("UPDATE books SET available_quantity = available_quantity - 1 WHERE book_id = ?");
                $stmt->execute([$book['book_id']]);
                
                $message = 'Borrow request approved!';
            } else {
                $message = 'Book is not available!';
            }
        } elseif ($action == 'deny') {
            $stmt = $pdo->prepare("UPDATE borrow SET status = 'denied', librarian_id = ? WHERE borrow_id = ?");
            $stmt->execute([$librarian_id, $borrow_id]);
            $message = 'Borrow request denied!';
        }
    }
}

// Handle return processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'process_return') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid request';
    } else {
        $borrow_id = intval($_POST['borrow_id']);
        $book_id = intval($_POST['book_id']);
        
        $stmt = $pdo->prepare("UPDATE borrow SET status = 'returned', actual_return_date = CURDATE() WHERE borrow_id = ?");
        $stmt->execute([$borrow_id]);
        
        $stmt = $pdo->prepare("UPDATE books SET available_quantity = available_quantity + 1 WHERE book_id = ?");
        $stmt->execute([$book_id]);
        
        $message = 'Book returned successfully!';
    }
}

// Get statistics
$stats = [];

// Total books
$stmt = $pdo->query("SELECT COUNT(*) as total FROM books WHERE status = 'active'");
$stats['books'] = $stmt->fetch()['total'];

// Available books
$stmt = $pdo->query("SELECT SUM(available_quantity) as total FROM books WHERE status = 'active'");
$stats['available_books'] = $stmt->fetch()['total'] ?? 0;

// Total members
$stmt = $pdo->query("SELECT COUNT(*) as total FROM members WHERE status = 'active'");
$stats['members'] = $stmt->fetch()['total'];

// Pending borrow requests
$stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow WHERE status = 'pending'");
$stats['pending_requests'] = $stmt->fetch()['total'];

// Active borrows
$stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow WHERE status = 'borrowed'");
$stats['active_borrows'] = $stmt->fetch()['total'];

// Overdue books
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrow WHERE status = 'borrowed' AND return_date < ?");
$stmt->execute([$today]);
$stats['overdue'] = $stmt->fetch()['total'];

// Fetch pending borrow requests with member and book details
$stmt = $pdo->query("
    SELECT b.*, m.name as member_name, m.email as member_email, 
           bk.title as book_title, bk.author, bk.available_quantity
    FROM borrow b
    JOIN members m ON b.member_id = m.member_id
    JOIN books bk ON b.book_id = bk.book_id
    WHERE b.status = 'pending'
    ORDER BY b.created_at DESC
");
$pending_requests = $stmt->fetchAll();

// Fetch all borrowed books with details
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

// Fetch all members
$stmt = $pdo->query("SELECT * FROM members WHERE status = 'active' ORDER BY member_id DESC");
$all_members = $stmt->fetchAll();

// Fetch all books
$stmt = $pdo->query("SELECT * FROM books WHERE status = 'active' ORDER BY book_id DESC");
$all_books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard - Library System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; }
        .user-info { display: flex; align-items: center; gap: 20px; }
        .user-info span { font-size: 14px; }
        .logout-btn { background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px; }
        .logout-btn:hover { background: rgba(255,255,255,0.3); }
        .container { padding: 40px; max-width: 1600px; margin: 0 auto; }
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-card h3 { color: #666; font-size: 14px; margin-bottom: 10px; }
        .stat-card .number { font-size: 32px; font-weight: bold; color: #333; }
        .stat-card.pending h3 { color: #ffc107; }
        .stat-card.overdue h3 { color: #dc3545; }
        
        .dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px; }
        .section-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section-card h2 { color: #333; margin-bottom: 20px; font-size: 20px; border-bottom: 2px solid #28a745; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-group input:focus { outline: none; border-color: #28a745; }
        .btn { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #218838; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        th { background: #28a745; color: white; white-space: nowrap; }
        tr:hover { background: #f8f9fa; }
        
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-borrowed { color: #28a745; font-weight: bold; }
        .status-returned { color: #17a2b8; font-weight: bold; }
        .status-overdue { color: #dc3545; font-weight: bold; }
        .status-denied { color: #6c757d; font-weight: bold; }
        
        .btn-small { padding: 5px 10px; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; margin-right: 5px; }
        .btn-approve { background: #28a745; }
        .btn-deny { background: #dc3545; }
        .btn-return { background: #17a2b8; }
        .btn-small:hover { opacity: 0.9; }
        
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .menu-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .menu-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .menu-card h3 { color: #333; margin-bottom: 10px; font-size: 16px; }
        .menu-card p { color: #666; margin-bottom: 15px; font-size: 13px; }
        .menu-card a { display: inline-block; padding: 8px 16px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; font-size: 13px; }
        .menu-card a:hover { background: #218838; }
        
        .empty-state { text-align: center; padding: 30px; color: #666; }
        
        @media (max-width: 1200px) { .dashboard-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>Librarian Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
            <span>(<?php echo htmlspecialchars($email); ?>)</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message<?php echo strpos($message, 'Failed') !== false || strpos($message, 'not available') !== false ? ' error' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Books</h3>
                <div class="number"><?php echo $stats['books']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Available Books</h3>
                <div class="number"><?php echo $stats['available_books']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Members</h3>
                <div class="number"><?php echo $stats['members']; ?></div>
            </div>
            <div class="stat-card pending">
                <h3>Pending Requests</h3>
                <div class="number"><?php echo $stats['pending_requests']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Active Borrows</h3>
                <div class="number"><?php echo $stats['active_borrows']; ?></div>
            </div>
            <div class="stat-card overdue">
                <h3>Overdue Books</h3>
                <div class="number"><?php echo $stats['overdue']; ?></div>
            </div>
        </div>

        <!-- Book Entry Form and Member Form -->
        <div class="dashboard-grid">
            <div class="section-card">
                <h2>Add New Book</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="add_book">
                    <div class="form-group"><label>Book Title</label><input type="text" name="title" required placeholder="Enter book title"></div>
                    <div class="form-group"><label>Author</label><input type="text" name="author" required placeholder="Enter author name"></div>
                    <div class="form-group"><label>Year Published</label><input type="number" name="year_published" required placeholder="e.g., 2024" min="1900" max="2100"></div>
                    <div class="form-group"><label>Quantity</label><input type="number" name="quantity" required min="1" value="1" placeholder="Number of copies"></div>
                    <button type="submit" class="btn">Add Book</button>
                </form>
            </div>

            <div class="section-card">
                <h2>Add New Member</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="add_member">
                    <div class="form-group"><label>Full Name</label><input type="text" name="name" required placeholder="Enter full name"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" required placeholder="Enter email"></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone" required placeholder="Enter phone number"></div>
                    <div class="form-group"><label>Password</label><input type="password" name="password" required minlength="6" placeholder="Min 6 characters"></div>
                    <button type="submit" class="btn">Add Member</button>
                </form>
            </div>
        </div>

        <!-- Borrow Requests Table -->
        <div class="section-card" style="margin-bottom: 40px;">
            <h2>Borrow Requests - Approve/Deny (<?php echo count($pending_requests); ?>)</h2>
            <div class="table-container">
                <?php if (empty($pending_requests)): ?>
                    <div class="empty-state"><p>No pending borrow requests</p></div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Book</th>
                                <th>Author</th>
                                <th>Borrow Date</th>
                                <th>Return Date</th>
                                <th>Available</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['member_name']); ?><br><small><?php echo htmlspecialchars($request['member_email']); ?></small></td>
                                <td><?php echo htmlspecialchars($request['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($request['author']); ?></td>
                                <td><?php echo $request['borrow_date']; ?></td>
                                <td><?php echo $request['return_date']; ?></td>
                                <td><?php echo $request['available_quantity']; ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="borrow_id" value="<?php echo $request['borrow_id']; ?>">
                                        <button type="submit" class="btn-small btn-approve" onclick="return confirm('Approve this borrow request?');">Approve</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="action" value="deny">
                                        <input type="hidden" name="borrow_id" value="<?php echo $request['borrow_id']; ?>">
                                        <button type="submit" class="btn-small btn-deny" onclick="return confirm('Deny this borrow request?');">Deny</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Borrowed Books Table -->
        <div class="section-card" style="margin-bottom: 40px;">
            <h2>Currently Borrowed Books - Return Processing</h2>
            <div class="table-container">
                <?php if (empty($borrowed_books)): ?>
                    <div class="empty-state"><p>No books currently borrowed</p></div>
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
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowed_books as $borrow): ?>
                            <tr>
                                <td><?php echo $borrow['borrow_id']; ?></td>
                                <td><?php echo htmlspecialchars($borrow['member_name']); ?><br><small><?php echo htmlspecialchars($borrow['member_email']); ?></small></td>
                                <td><?php echo htmlspecialchars($borrow['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($borrow['author']); ?></td>
                                <td><?php echo $borrow['borrow_date']; ?></td>
                                <td><?php echo $borrow['return_date']; ?></td>
                                <td class="status-<?php echo $borrow['status']; ?>"><?php echo ucfirst($borrow['status']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="action" value="process_return">
                                        <input type="hidden" name="borrow_id" value="<?php echo $borrow['borrow_id']; ?>">
                                        <input type="hidden" name="book_id" value="<?php echo $borrow['book_id']; ?>">
                                        <button type="submit" class="btn-small btn-return" onclick="return confirm('Process return for this book?');">Mark Returned</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Members Table -->
        <div class="section-card" style="margin-bottom: 40px;">
            <h2>All Members</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Joined</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_members as $member): ?>
                        <tr>
                            <td><?php echo $member['member_id']; ?></td>
                            <td><?php echo htmlspecialchars($member['name']); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo htmlspecialchars($member['phone']); ?></td>
                            <td class="status-<?php echo $member['status']; ?>"><?php echo ucfirst($member['status']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($member['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Books Table with Update -->
        <div class="section-card" style="margin-bottom: 40px;">
            <h2>All Books - Update</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Title</th><th>Author</th><th>Year</th><th>Qty</th><th>Available</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_books as $book): ?>
                        <tr>
                            <td><?php echo $book['book_id']; ?></td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo $book['year_published']; ?></td>
                            <td><?php echo $book['quantity']; ?></td>
                            <td><?php echo $book['available_quantity']; ?></td>
                            <td class="status-<?php echo $book['status']; ?>"><?php echo ucfirst($book['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section-card">
            <h2>Quick Actions</h2>
            <div class="menu-grid">
                <div class="menu-card"><h3>Books Management</h3><p>View all books</p><a href="librarian_books.php">Manage Books</a></div>
                <div class="menu-card"><h3>Members Management</h3><p>View members</p><a href="librarian_members.php">Manage Members</a></div>
                <div class="menu-card"><h3>All Borrows</h3><p>View all records</p><a href="librarian_borrows.php">View All</a></div>
                <div class="menu-card"><h3>Return Books</h3><p>Process returns</p><a href="librarian_returns.php">Process Returns</a></div>
            </div>
        </div>
    </div>
</body>
</html>


<?php
$host = "localhost";
$dbname = "library";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $names = $_POST["names"];
    $telephone = $_POST["telephone"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "INSERT INTO librarians (borrow_id,phone_id, book_id,librarian_id,borrow_date,return_date,status,request,request_note)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if($stmt->execute([$names, $telephone, $email, $password])){
        $message = "Librarian registered successfully!";
    } else {
        $message = "Error registering librarian!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Librarian Registration</title>
    <style>
        body{
            font-family: Arial;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container{
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
            width: 400px;
        }

        h2{
            text-align: center;
        }

        input{
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input[type="submit"]{
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        input[type="submit"]:hover{
            background: #0056b3;
        }

        .message{
            text-align: center;
            color: green;
        }

        @media(max-width: 500px){
            .container{
                width: 90%;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Librarian Registration</h2>

    <?php if(isset($message)) echo "<p class='message'>$message</p>"; ?>

    <form method="POST">
        <label>Full Name</label>
        <input type="text" name="borrow_id" required>

        <label>book_id</label>
        <input type="number" name="book_id" required>

        <label>librarian</label>
        <input type="librarian_id" name="librarian_id" required>

        <label>Telephone</label>
        <input type="phone_id" name="phone_id" required>

        <label>return</label>
        <input type="return_date" name="return_date" required>

         <label>status</label>
        <input type="status" name="status" required>

         <label>request</label>
        <input type="request" name="request" required>

         <label>request_note</label>
        <input type="request_note" name="request_note" required>

        <input type="submit" value="Register Librarian">
    </form>
</div>

</body>
</html>
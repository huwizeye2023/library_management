<?php
require_once 'config.php';
requireRole('admin');

$username = $_SESSION['username'];
$email = $_SESSION['email'];
$message = '';

// Handle book form
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

// Handle librarian form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_librarian') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid request';
    } else {
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

// Total librarians
$stmt = $pdo->query("SELECT COUNT(*) as total FROM librarians WHERE status = 'active'");
$stats['librarians'] = $stmt->fetch()['total'];

// Pending borrow requests
$stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow WHERE status = 'pending'");
$stats['pending_requests'] = $stmt->fetch()['total'];

// Active borrows
$stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow WHERE status = 'borrowed'");
$stats['active_borrows'] = $stmt->fetch()['total'];

// Overdue books
$stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow WHERE status = 'overdue'");
$stats['overdue'] = $stmt->fetch()['total'];

// Fetch all borrows with member and book details
$stmt = $pdo->query("
    SELECT b.*, m.name as member_name, m.email as member_email, 
           bk.title as book_title, bk.author, l.names as librarian_name
    FROM borrow b
    JOIN members m ON b.member_id = m.member_id
    JOIN books bk ON b.book_id = bk.book_id
    LEFT JOIN librarians l ON b.librarian_id = l.librarian_id
    ORDER BY b.created_at DESC
    LIMIT 50
");
$all_borrows = $stmt->fetchAll();

// Fetch members for table
$stmt = $pdo->query("SELECT * FROM members ORDER BY member_id DESC LIMIT 20");
$dashboard_members = $stmt->fetchAll();

// Fetch librarians for table
$stmt = $pdo->query("SELECT * FROM librarians ORDER BY librarian_id DESC LIMIT 20");
$dashboard_librarians = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Library System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
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
        
        .dashboard-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-bottom: 40px; }
        .section-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section-card h2 { color: #333; margin-bottom: 20px; font-size: 18px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 4px; color: #333; font-weight: 500; font-size: 13px; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .btn { padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; width: 100%; }
        .btn:hover { background: #5568d3; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 13px; }
        th { background: #667eea; color: white; white-space: nowrap; }
        tr:hover { background: #f8f9fa; }
        
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-approved { color: #17a2b8; font-weight: bold; }
        .status-borrowed { color: #28a745; font-weight: bold; }
        .status-returned { color: #17a2b8; font-weight: bold; }
        .status-overdue { color: #dc3545; font-weight: bold; }
        .status-denied { color: #6c757d; font-weight: bold; }
        
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; }
        .menu-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .menu-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .menu-card h3 { color: #333; margin-bottom: 10px; font-size: 15px; }
        .menu-card p { color: #666; margin-bottom: 15px; font-size: 12px; }
        .menu-card a { display: inline-block; padding: 8px 16px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; }
        .menu-card a:hover { background: #5568d3; }
        
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab-btn { padding: 10px 20px; background: #e9ecef; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .tab-btn.active { background: #667eea; color: white; }
        
        .btn-small { padding: 5px 10px; background: #667eea; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; text-decoration: none; }
        
        @media (max-width: 1200px) { .dashboard-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>Admin Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
            <span>(<?php echo htmlspecialchars($email); ?>)</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message<?php echo strpos($message, 'Failed') !== false || strpos($message, 'Error') !== false ? ' error' : ''; ?>">
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
            <div class="stat-card">
                <h3>Librarians</h3>
                <div class="number"><?php echo $stats['librarians']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Requests</h3>
                <div class="number"><?php echo $stats['pending_requests']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Active Borrows</h3>
                <div class="number"><?php echo $stats['active_borrows']; ?></div>
            </div>
        </div>

        <!-- Control Forms -->
        <div class="dashboard-grid">
            <div class="section-card">
                <h2>Add New Book</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="add_book">
                    <div class="form-group"><label>Book Title</label><input type="text" name="title" required placeholder="Enter title"></div>
                    <div class="form-group"><label>Author</label><input type="text" name="author" required placeholder="Enter author"></div>
                    <div class="form-group"><label>Year Published</label><input type="number" name="year_published" required placeholder="e.g., 2024" min="1900" max="2100"></div>
                    <div class="form-group"><label>Quantity</label><input type="number" name="quantity" required min="1" value="1"></div>
                    <button type="submit" class="btn">Add Book</button>
                </form>
            </div>

            <div class="section-card">
                <h2>Add New Member</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="add_member">
                    <div class="form-group"><label>Full Name</label><input type="text" name="name" required placeholder="Enter name"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" required placeholder="Enter email"></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone" required placeholder="Enter phone"></div>
                    <div class="form-group"><label>Password</label><input type="password" name="password" required minlength="6" placeholder="Min 6 characters"></div>
                    <button type="submit" class="btn">Add Member</button>
                </form>
            </div>

            <div class="section-card">
                <h2>Add New Librarian</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="add_librarian">
                    <div class="form-group"><label>Full Name</label><input type="text" name="names" required placeholder="Enter name"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" required placeholder="Enter email"></div>
                    <div class="form-group"><label>Telephone</label><input type="text" name="telephone" required placeholder="Enter phone"></div>
                    <div class="form-group"><label>Password</label><input type="password" name="password" required minlength="6" placeholder="Min 6 characters"></div>
                    <button type="submit" class="btn">Add Librarian</button>
                </form>
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
                        <?php foreach ($dashboard_members as $member): ?>
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

        <!-- Borrow Records Table -->
        <div class="section-card" style="margin-bottom: 40px;">
            <h2>All Borrow Records (with Dates)</h2>
            <div class="table-container">
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
                            <th>Processed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_borrows as $borrow): ?>
                        <tr>
                            <td><?php echo $borrow['borrow_id']; ?></td>
                            <td><?php echo htmlspecialchars($borrow['member_name']); ?></td>
                            <td><?php echo htmlspecialchars($borrow['book_title']); ?></td>
                            <td><?php echo $borrow['borrow_date']; ?></td>
                            <td><?php echo $borrow['return_date']; ?></td>
                            <td><?php echo $borrow['actual_return_date'] ?? '-'; ?></td>
                            <td class="status-<?php echo $borrow['status']; ?>"><?php echo ucfirst($borrow['status']); ?></td>
                            <td><?php echo $borrow['librarian_name'] ?? 'Pending'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Librarians Table -->
        <div class="section-card" style="margin-bottom: 40px;">
            <h2>All Librarians</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Joined</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dashboard_librarians as $librarian): ?>
                        <tr>
                            <td><?php echo $librarian['librarian_id']; ?></td>
                            <td><?php echo htmlspecialchars($librarian['names']); ?></td>
                            <td><?php echo htmlspecialchars($librarian['email']); ?></td>
                            <td><?php echo htmlspecialchars($librarian['telephone']); ?></td>
                            <td class="status-<?php echo $librarian['status']; ?>"><?php echo ucfirst($librarian['status']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($librarian['created_at'])); ?></td>
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
                <div class="menu-card"><h3>Books Management</h3><p>View all books</p><a href="admin_books.php">Manage Books</a></div>
                <div class="menu-card"><h3>Members Management</h3><p>View members</p><a href="admin_members.php">Manage Members</a></div>
                <div class="menu-card"><h3>Librarians Management</h3><p>Manage librarians</p><a href="admin_librarians.php">Manage Librarians</a></div>
                <div class="menu-card"><h3>Borrow Requests</h3><p>View requests</p><a href="admin_borrow.php">View Requests</a></div>
                <div class="menu-card"><h3>All Borrows</h3><p>View records</p><a href="admin_all_borrows.php">View All</a></div>
            </div>
        </div>
    </div>
</body>
</html>
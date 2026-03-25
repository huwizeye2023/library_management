<?php
require_once 'config.php';
requireRole('member');

$username = $_SESSION['username'];
$email = $_SESSION['email'];
$member_id = getUserId();

// Get member's statistics
$stats = [];

// Available books
$stmt = $pdo->query("SELECT COUNT(*) as total FROM books WHERE status = 'active' AND available_quantity > 0");
$stats['available_books'] = $stmt->fetch()['total'];

// My borrow requests pending
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrow WHERE member_id = ? AND status = 'pending'");
$stmt->execute([$member_id]);
$stats['my_pending'] = $stmt->fetch()['total'];

// My active borrows
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrow WHERE member_id = ? AND status = 'borrowed'");
$stmt->execute([$member_id]);
$stats['my_active'] = $stmt->fetch()['total'];

// My overdue
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrow WHERE member_id = ? AND status = 'overdue'");
$stmt->execute([$member_id]);
$stats['my_overdue'] = $stmt->fetch()['total'];

// My returned history
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrow WHERE member_id = ? AND status = 'returned'");
$stmt->execute([$member_id]);
$stats['my_returned'] = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - Library System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
        }
        .header {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 24px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .user-info span {
            font-size: 14px;
        }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .container {
            padding: 40px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        .stat-card.warning .number {
            color: #dc3545;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .menu-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .menu-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .menu-card p {
            color: #666;
            margin-bottom: 20px;
        }
        .menu-card a {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .menu-card a:hover {
            background: #0056b3;
        }
        .menu-card.primary a {
            background: #6610f2;
        }
        .menu-card.primary a:hover {
            background: #520dc2;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Member Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
            <span>(<?php echo htmlspecialchars($email); ?>)</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Available Books</h3>
                <div class="number"><?php echo $stats['available_books']; ?></div>
            </div>
            <div class="stat-card">
                <h3>My Pending Requests</h3>
                <div class="number"><?php echo $stats['my_pending']; ?></div>
            </div>
            <div class="stat-card">
                <h3>My Active Borrows</h3>
                <div class="number"><?php echo $stats['my_active']; ?></div>
            </div>
            <div class="stat-card warning">
                <h3>My Overdue Books</h3>
                <div class="number"><?php echo $stats['my_overdue']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Books Returned</h3>
                <div class="number"><?php echo $stats['my_returned']; ?></div>
            </div>
        </div>

        <div class="menu-grid">
            <div class="menu-card primary">
                <h3>Browse Books</h3>
                <p>View all available books in the library</p>
                <a href="member_books.php">View Books</a>
            </div>
            <div class="menu-card">
                <h3>Request Borrow</h3>
                <p>Request to borrow a book</p>
                <a href="member_borrow_request.php">Request Book</a>
            </div>
            <div class="menu-card">
                <h3>My Borrows</h3>
                <p>View my borrowing history and status</p>
                <a href="member_my_borrows.php">My Borrows</a>
            </div>
            <div class="menu-card primary">
                <h3>Read Online</h3>
                <p>Read books online</p>
                <a href="member_read_online.php">Read Books</a>
            </div>
            <div class="menu-card">
                <h3>My Profile</h3>
                <p>View and edit my profile</p>
                <a href="member_profile.php">My Profile</a>
            </div>
        </div>
    </div>
</body>
</html>

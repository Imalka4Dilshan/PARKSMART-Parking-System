<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$_SESSION['user_name'] = $_SESSION['user_name'] ?? 'User';

// Log this access
$logQuery = "INSERT INTO user_access_logs (user_id, action, details, ip_address, timestamp) VALUES (?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($logQuery);
$ipAddress = $_SERVER['REMOTE_ADDR'];
$action = "view_access_logs";
$details = "User accessed booking access logs";
$stmt->bind_param("isss", $userId, $action, $details, $ipAddress);
$stmt->execute();
$stmt->close();

// Get user info
$userQuery = "SELECT username, email FROM users WHERE id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'timestamp';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query based on filter
$baseQuery = "SELECT bal.*, b.slot_number, b.vehicle_type, b.booking_date, b.booking_time, b.duration_hours 
             FROM booking_access_logs bal 
             JOIN booking b ON bal.booking_id = b.booking_id 
             WHERE bal.user_id = ?";

if ($filter !== 'all') {
    $baseQuery .= " AND bal.action = ?";
}

$countQuery = str_replace("SELECT bal.*, b.slot_number, b.vehicle_type, b.booking_date, b.booking_time, b.duration_hours", "SELECT COUNT(*) as total", $baseQuery);

$baseQuery .= " ORDER BY " . ($sortBy === 'timestamp' ? 'bal.timestamp' : 'bal.action') . " " . $order . " LIMIT ? OFFSET ?";

// Count total records
$stmt = $conn->prepare($countQuery);
if ($filter !== 'all') {
    $stmt->bind_param("is", $userId, $filter);
} else {
    $stmt->bind_param("i", $userId);
}
$stmt->execute();
$countResult = $stmt->get_result();
$countRow = $countResult->fetch_assoc();
$totalRecords = $countRow['total'];
$totalPages = ceil($totalRecords / $perPage);

// Get access logs
$stmt = $conn->prepare($baseQuery);
if ($filter !== 'all') {
    $stmt->bind_param("isii", $userId, $filter, $perPage, $offset);
} else {
    $stmt->bind_param("iii", $userId, $perPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$accessLogs = [];
while ($row = $result->fetch_assoc()) {
    $accessLogs[] = $row;
}

// Get user activity summary
$summaryQuery = "SELECT action, COUNT(*) as count FROM booking_access_logs WHERE user_id = ? GROUP BY action";
$stmt = $conn->prepare($summaryQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$summaryResult = $stmt->get_result();
$activitySummary = [];
while ($row = $summaryResult->fetch_assoc()) {
    $activitySummary[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Logs - ParkSmart</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            padding-top: 80px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        h2 {
            color: #667eea;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 12px;
            color: white;
            text-align: center;
        }

        .summary-card h3 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-card .count {
            font-size: 32px;
            font-weight: 700;
        }

        .controls {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filter-group label {
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .filter-group select {
            padding: 10px 15px;
            border: 2px solid #667eea;
            border-radius: 8px;
            font-family: inherit;
            cursor: pointer;
            background: white;
            color: #333;
        }

        .sort-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .sort-group a {
            padding: 10px 15px;
            background: #f0f0f0;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 13px;
        }

        .sort-group a:hover {
            background: #667eea;
            color: white;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .logs-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .logs-table th {
            padding: 15px;
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .logs-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            color: #333;
        }

        .logs-table tbody tr {
            transition: background 0.3s ease;
        }

        .logs-table tbody tr:hover {
            background: #f8f9fa;
        }

        .action-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .action-created {
            background: #d4edda;
            color: #155724;
        }

        .action-updated {
            background: #cce5ff;
            color: #004085;
        }

        .action-deleted {
            background: #f8d7da;
            color: #721c24;
        }

        .action-viewed {
            background: #e7d4f5;
            color: #5a1f7d;
        }

        .timestamp {
            color: #666;
            font-size: 13px;
        }

        .value-change {
            font-size: 12px;
            color: #666;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            border-left: 3px solid #667eea;
        }

        .pagination {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            border: 2px solid #667eea;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
        }

        .pagination .active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 100px;
            }
            
            .container {
                padding: 20px;
            }

            .controls {
                flex-direction: column;
            }

            .logs-table {
                font-size: 12px;
            }

            .logs-table th, .logs-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <?php include 'user_nav.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <h2>My Booking Access Logs</h2>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card">
                <h3>Total Actions</h3>
                <div class="count"><?php echo $totalRecords; ?></div>
            </div>
            <?php foreach ($activitySummary as $summary): ?>
            <div class="summary-card">
                <h3><?php echo ucfirst(str_replace('_', ' ', $summary['action'])); ?></h3>
                <div class="count"><?php echo $summary['count']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filter and Sort Controls -->
        <div class="controls">
            <div class="filter-group">
                <label>Filter by Action:</label>
                <form method="GET" style="display: flex; gap: 10px;">
                    <select name="filter" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Actions</option>
                        <option value="created" <?php echo $filter === 'created' ? 'selected' : ''; ?>>Created</option>
                        <option value="updated" <?php echo $filter === 'updated' ? 'selected' : ''; ?>>Updated</option>
                        <option value="deleted" <?php echo $filter === 'deleted' ? 'selected' : ''; ?>>Deleted</option>
                        <option value="viewed" <?php echo $filter === 'viewed' ? 'selected' : ''; ?>>Viewed</option>
                    </select>
                </form>
            </div>

            <div class="sort-group">
                <label>Sort by:</label>
                <a href="?filter=<?php echo $filter; ?>&sort=timestamp&order=<?php echo $order === 'DESC' ? 'ASC' : 'DESC'; ?>">
                    📅 Latest <?php echo $order === 'DESC' ? '↓' : '↑'; ?>
                </a>
                <a href="?filter=<?php echo $filter; ?>&sort=action&order=<?php echo $order === 'DESC' ? 'ASC' : 'DESC'; ?>">
                    🏷️ Action <?php echo $order === 'DESC' ? '↓' : '↑'; ?>
                </a>
            </div>
        </div>

        <!-- Access Logs Table -->
        <?php if (!empty($accessLogs)): ?>
        <table class="logs-table">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Slot</th>
                    <th>Vehicle Type</th>
                    <th>Action</th>
                    <th>Changes</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accessLogs as $log): 
                    $actionClass = 'action-' . strtolower($log['action']);
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($log['booking_id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($log['slot_number']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($log['vehicle_type'])); ?></td>
                    <td><span class="action-badge <?php echo $actionClass; ?>"><?php echo htmlspecialchars(ucfirst($log['action'])); ?></span></td>
                    <td>
                        <?php if ($log['old_value'] || $log['new_value']): ?>
                        <div class="value-change">
                            <?php if ($log['old_value']) echo '<strong>From:</strong> ' . htmlspecialchars($log['old_value']) . '<br>'; ?>
                            <?php if ($log['new_value']) echo '<strong>To:</strong> ' . htmlspecialchars($log['new_value']); ?>
                        </div>
                        <?php else: ?>
                        <span style="color: #999;">No changes recorded</span>
                        <?php endif; ?>
                    </td>
                    <td class="timestamp"><?php echo date('M d, Y<br>H:i:s', strtotime($log['timestamp'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?filter=<?php echo $filter; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $order; ?>&page=1">« First</a>
            <a href="?filter=<?php echo $filter; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $order; ?>&page=<?php echo $page - 1; ?>">‹ Previous</a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php if ($i === $page): ?>
            <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
            <a href="?filter=<?php echo $filter; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $order; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
            <a href="?filter=<?php echo $filter; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $order; ?>&page=<?php echo $page + 1; ?>">Next ›</a>
            <a href="?filter=<?php echo $filter; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $order; ?>&page=<?php echo $totalPages; ?>">Last »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <h3>No Access Logs Found</h3>
            <p>Start making bookings to see your access history here.</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

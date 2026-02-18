<?php
$current = 'reviews.php';

// Set timezone
date_default_timezone_set('Asia/Colombo');

// DB connection
$conn = new mysqli("localhost", "root", "", "parking_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get filter parameters
$filter_recommend = isset($_GET['recommend']) ? $_GET['recommend'] : 'all';
$filter_rating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
$view = isset($_GET['view']) ? $_GET['view'] : 'all';

// Apply view-based filters
if ($view === 'positive') {
    $filter_recommend = 'yes';
} elseif ($view === 'negative') {
    $filter_recommend = 'no';
} elseif ($view === 'recent') {
    // Will be handled in ORDER BY
} elseif ($view === 'low-rated') {
    $filter_rating = 0; // Override to show all, then filter below
}

// Build query with filters
$whereClause = "1=1";
if ($filter_recommend !== 'all') {
    $whereClause .= " AND r.recommend = '" . $conn->real_escape_string($filter_recommend) . "'";
}
if ($view === 'low-rated') {
    $whereClause .= " AND r.overall_rating <= 2";
} elseif ($filter_rating > 0) {
    $whereClause .= " AND r.overall_rating >= " . $filter_rating;
}

// Order by clause based on view
$orderClause = "ORDER BY r.created_at DESC";
if ($view === 'recent') {
    $orderClause = "ORDER BY r.created_at DESC";
}

// Get total count
$countSql = "SELECT COUNT(*) as total FROM reviews r WHERE $whereClause";
$countResult = $conn->query($countSql);
$totalReviews = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalReviews / $perPage);

// Get reviews with user information
$sql = "SELECT r.*, u.name, u.email 
        FROM reviews r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE $whereClause
        $orderClause 
        LIMIT $perPage OFFSET $offset";
$result = $conn->query($sql);

// Get statistics
$statsSql = "SELECT 
    COUNT(*) as total,
    AVG(overall_rating) as avg_rating,
    SUM(CASE WHEN recommend = 'yes' THEN 1 ELSE 0 END) as recommend_count,
    SUM(CASE WHEN recommend = 'no' THEN 1 ELSE 0 END) as not_recommend_count,
    AVG(slot_condition) as avg_slot,
    AVG(safety_rating) as avg_safety,
    AVG(cleanliness) as avg_clean,
    AVG(staff_service) as avg_staff
FROM reviews";
$statsResult = $conn->query($statsSql);
$stats = $statsResult->fetch_assoc();

// Get counts for navigation tabs
$allCount = $stats['total'];
$positiveCount = $stats['recommend_count'];
$negativeCount = $stats['not_recommend_count'];
$recentCount = $conn->query("SELECT COUNT(*) as cnt FROM reviews WHERE DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['cnt'];
$lowRatedCount = $conn->query("SELECT COUNT(*) as cnt FROM reviews WHERE overall_rating <= 2")->fetch_assoc()['cnt'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Reviews - ParkSmart Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-bottom: 30px;
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 24px;
        }

        .page-header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            color: #2c3e50;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: #7f8c8d;
            font-size: 14px;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.2);
        }

        .stat-card.green {
            border-left-color: #27ae60;
        }

        .stat-card.red {
            border-left-color: #e74c3c;
        }

        .stat-card.orange {
            border-left-color: #f39c12;
        }

        .stat-label {
            font-size: 13px;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: #2c3e50;
        }

        .stat-subtext {
            font-size: 12px;
            color: #95a5a6;
            margin-top: 5px;
        }

        /* Filters */
        .filters-section {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        .filter-select {
            padding: 8px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 13px;
            transition: all 0.3s;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102,126,234,0.4);
        }

        .reset-btn {
            background: #95a5a6;
            padding: 10px 20px;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 13px;
            transition: all 0.3s;
        }

        .reset-btn:hover {
            background: #7f8c8d;
        }

        /* Reviews List */
        .review-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .review-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.2);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(102,126,234,0.1);
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .user-email {
            font-size: 13px;
            color: #7f8c8d;
        }

        .review-date {
            font-size: 12px;
            color: #95a5a6;
        }

        .recommend-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .recommend-yes {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
        }

        .recommend-no {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .ratings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .rating-item {
            background: linear-gradient(135deg, rgba(102,126,234,0.05) 0%, rgba(118,75,162,0.05) 100%);
            padding: 15px;
            border-radius: 10px;
            border-left: 3px solid #667eea;
        }

        .rating-label {
            font-size: 12px;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 5px;
        }

        .rating-stars {
            font-size: 18px;
            color: #f39c12;
        }

        .overall-rating {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 15px;
        }

        .overall-rating-value {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .overall-rating-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
        }

        .review-text {
            background: rgba(102,126,234,0.05);
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            margin-top: 15px;
        }

        .review-text-label {
            font-size: 12px;
            font-weight: 700;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .review-text-content {
            color: #2c3e50;
            line-height: 1.8;
            font-size: 14px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .page-link {
            padding: 10px 16px;
            background: rgba(255,255,255,0.95);
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            transition: all 0.3s;
        }

        .page-link:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }

        .page-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .page-link.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .empty-state {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 60px 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-title {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .empty-text {
            color: #7f8c8d;
        }

        /* Internal Navigation Tabs */
        .internal-nav {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 0;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .nav-tabs {
            display: flex;
            gap: 0;
            list-style: none;
            margin: 0;
            padding: 0;
            border-bottom: 3px solid rgba(102,126,234,0.1);
        }

        .nav-tabs li {
            flex: 1;
        }

        .nav-tab {
            display: block;
            padding: 20px 15px;
            text-align: center;
            text-decoration: none;
            color: #7f8c8d;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            position: relative;
            cursor: pointer;
        }

        .nav-tab:hover {
            background: rgba(102,126,234,0.05);
            color: #667eea;
        }

        .nav-tab.active {
            background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .nav-tab-icon {
            font-size: 20px;
            display: block;
            margin-bottom: 5px;
        }

        .nav-tab-label {
            display: block;
            font-size: 13px;
        }

        .nav-tab-count {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 8px;
            font-weight: 800;
        }

        @media (max-width: 768px) {
            .nav-tabs {
                flex-wrap: wrap;
            }

            .nav-tabs li {
                flex: 0 0 50%;
            }

            .nav-tab {
                padding: 15px 10px;
                font-size: 12px;
            }

            .nav-tab-icon {
                font-size: 18px;
            }

            .nav-tab-count {
                font-size: 10px;
                padding: 1px 6px;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 16px;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .review-header {
                flex-direction: column;
                gap: 15px;
            }

            .filters-section {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>📝 User Reviews</h1>
            <p class="page-subtitle">View and monitor customer feedback and ratings</p>
        </div>

        <!-- Internal Navigation -->
        <div class="internal-nav">
            <ul class="nav-tabs">
                <li>
                    <a href="reviews.php?view=all" class="nav-tab <?php echo $view === 'all' ? 'active' : ''; ?>">
                        <span class="nav-tab-icon">📋</span>
                        <span class="nav-tab-label">All Reviews<span class="nav-tab-count"><?php echo $allCount; ?></span></span>
                    </a>
                </li>
                <li>
                    <a href="reviews.php?view=positive" class="nav-tab <?php echo $view === 'positive' ? 'active' : ''; ?>">
                        <span class="nav-tab-icon">✅</span>
                        <span class="nav-tab-label">Positive<span class="nav-tab-count"><?php echo $positiveCount; ?></span></span>
                    </a>
                </li>
                <li>
                    <a href="reviews.php?view=negative" class="nav-tab <?php echo $view === 'negative' ? 'active' : ''; ?>">
                        <span class="nav-tab-icon">⚠️</span>
                        <span class="nav-tab-label">Negative<span class="nav-tab-count"><?php echo $negativeCount; ?></span></span>
                    </a>
                </li>
                <li>
                    <a href="reviews.php?view=recent" class="nav-tab <?php echo $view === 'recent' ? 'active' : ''; ?>">
                        <span class="nav-tab-icon">🕐</span>
                        <span class="nav-tab-label">Recent<span class="nav-tab-count"><?php echo $recentCount; ?></span></span>
                    </a>
                </li>
                <li>
                    <a href="reviews.php?view=low-rated" class="nav-tab <?php echo $view === 'low-rated' ? 'active' : ''; ?>">
                        <span class="nav-tab-icon">⭐</span>
                        <span class="nav-tab-label">Low Rated<span class="nav-tab-count"><?php echo $lowRatedCount; ?></span></span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Reviews</div>
                <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-subtext">All time reviews</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-label">Average Rating</div>
                <div class="stat-value"><?php echo number_format($stats['avg_rating'], 1); ?>/5</div>
                <div class="stat-subtext">★★★★★</div>
            </div>
            <div class="stat-card green">
                <div class="stat-label">Recommendations</div>
                <div class="stat-value"><?php echo number_format($stats['recommend_count']); ?></div>
                <div class="stat-subtext"><?php echo $stats['total'] > 0 ? round(($stats['recommend_count']/$stats['total'])*100) : 0; ?>% recommend us</div>
            </div>
            <div class="stat-card red">
                <div class="stat-label">Not Recommended</div>
                <div class="stat-value"><?php echo number_format($stats['not_recommend_count']); ?></div>
                <div class="stat-subtext"><?php echo $stats['total'] > 0 ? round(($stats['not_recommend_count']/$stats['total'])*100) : 0; ?>% do not recommend</div>
            </div>
        </div>

        <!-- Category Averages -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">⬜ Slot Condition</div>
                <div class="stat-value"><?php echo number_format($stats['avg_slot'], 1); ?>/5</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">🔒 Safety Rating</div>
                <div class="stat-value"><?php echo number_format($stats['avg_safety'], 1); ?>/5</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">✨ Cleanliness</div>
                <div class="stat-value"><?php echo number_format($stats['avg_clean'], 1); ?>/5</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">👥 Staff Service</div>
                <div class="stat-value"><?php echo number_format($stats['avg_staff'], 1); ?>/5</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="" style="display: flex; gap: 20px; flex-wrap: wrap; width: 100%;">
                <div class="filter-group">
                    <label class="filter-label">Recommendation:</label>
                    <select name="recommend" class="filter-select">
                        <option value="all" <?php echo $filter_recommend === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="yes" <?php echo $filter_recommend === 'yes' ? 'selected' : ''; ?>>Recommend</option>
                        <option value="no" <?php echo $filter_recommend === 'no' ? 'selected' : ''; ?>>Not Recommend</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Minimum Rating:</label>
                    <select name="rating" class="filter-select">
                        <option value="0" <?php echo $filter_rating === 0 ? 'selected' : ''; ?>>All Ratings</option>
                        <option value="5" <?php echo $filter_rating === 5 ? 'selected' : ''; ?>>5 Stars</option>
                        <option value="4" <?php echo $filter_rating === 4 ? 'selected' : ''; ?>>4+ Stars</option>
                        <option value="3" <?php echo $filter_rating === 3 ? 'selected' : ''; ?>>3+ Stars</option>
                        <option value="2" <?php echo $filter_rating === 2 ? 'selected' : ''; ?>>2+ Stars</option>
                        <option value="1" <?php echo $filter_rating === 1 ? 'selected' : ''; ?>>1+ Stars</option>
                    </select>
                </div>
                <button type="submit" class="filter-btn">Apply Filters</button>
                <a href="reviews.php" class="reset-btn" style="text-decoration: none; line-height: 1.6;">Reset</a>
            </form>
        </div>

        <!-- Reviews List -->
        <?php if ($result->num_rows > 0): ?>
            <?php while ($review = $result->fetch_assoc()): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($review['name'] ?? $review['username'] ?? 'Anonymous'); ?></div>
                            <div class="user-email"><?php echo htmlspecialchars($review['email'] ?? 'No email'); ?></div>
                            <div class="review-date">Reviewed on <?php echo date('M d, Y h:i A', strtotime($review['created_at'])); ?></div>
                        </div>
                        <span class="recommend-badge <?php echo $review['recommend'] === 'yes' ? 'recommend-yes' : 'recommend-no'; ?>">
                            <?php echo $review['recommend'] === 'yes' ? '✓ Recommends' : '✗ Not Recommended'; ?>
                        </span>
                    </div>

                    <div class="overall-rating">
                        <div class="overall-rating-value"><?php echo $review['overall_rating']; ?>/5</div>
                        <div class="overall-rating-label">Overall Rating</div>
                        <div style="font-size: 20px; margin-top: 8px;">
                            <?php 
                            for($i = 1; $i <= 5; $i++) {
                                echo $i <= $review['overall_rating'] ? '★' : '☆';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="ratings-grid">
                        <div class="rating-item">
                            <div class="rating-label">⬜ Slot Condition</div>
                            <div class="rating-stars">
                                <?php 
                                for($i = 1; $i <= 5; $i++) {
                                    echo $i <= $review['slot_condition'] ? '★' : '☆';
                                }
                                echo ' ' . $review['slot_condition'] . '/5';
                                ?>
                            </div>
                        </div>
                        <div class="rating-item">
                            <div class="rating-label">🔒 Safety Rating</div>
                            <div class="rating-stars">
                                <?php 
                                for($i = 1; $i <= 5; $i++) {
                                    echo $i <= $review['safety_rating'] ? '★' : '☆';
                                }
                                echo ' ' . $review['safety_rating'] . '/5';
                                ?>
                            </div>
                        </div>
                        <div class="rating-item">
                            <div class="rating-label">✨ Cleanliness</div>
                            <div class="rating-stars">
                                <?php 
                                for($i = 1; $i <= 5; $i++) {
                                    echo $i <= $review['cleanliness'] ? '★' : '☆';
                                }
                                echo ' ' . $review['cleanliness'] . '/5';
                                ?>
                            </div>
                        </div>
                        <div class="rating-item">
                            <div class="rating-label">👥 Staff Service</div>
                            <div class="rating-stars">
                                <?php 
                                for($i = 1; $i <= 5; $i++) {
                                    echo $i <= $review['staff_service'] ? '★' : '☆';
                                }
                                echo ' ' . $review['staff_service'] . '/5';
                                ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($review['review_text'])): ?>
                        <div class="review-text">
                            <div class="review-text-label">Review Comment:</div>
                            <div class="review-text-content"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&recommend=<?php echo $filter_recommend; ?>&rating=<?php echo $filter_rating; ?>" class="page-link">« Previous</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&recommend=<?php echo $filter_recommend; ?>&rating=<?php echo $filter_rating; ?>" 
                           class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page+1; ?>&recommend=<?php echo $filter_recommend; ?>&rating=<?php echo $filter_rating; ?>" class="page-link">Next »</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">⭐</div>
                <div class="empty-title">No Reviews Found</div>
                <div class="empty-text">No user reviews match your current filters.</div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>

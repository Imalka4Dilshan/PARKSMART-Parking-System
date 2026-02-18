<?php
session_start();
date_default_timezone_set('Asia/Colombo');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login2.php');
    exit();
}

include __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];

// Get user info
$user_query = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_info = $user_query->get_result()->fetch_assoc();

// Create reviews table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(255),
    recommend VARCHAR(20) DEFAULT 'yes',
    slot_condition INT DEFAULT 0,
    safety_rating INT DEFAULT 0,
    cleanliness INT DEFAULT 0,
    staff_service INT DEFAULT 0,
    overall_rating INT DEFAULT 0,
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(user_id),
    INDEX(created_at)
)");

// Add recommend column if it doesn't exist
$conn->query("ALTER TABLE reviews ADD COLUMN IF NOT EXISTS recommend VARCHAR(20) DEFAULT 'yes' AFTER username");

$message = '';
$error = '';

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $recommend = $_POST['recommend'] ?? '';
    $slot_condition = intval($_POST['slot_condition'] ?? 0);
    $safety_rating = intval($_POST['safety_rating'] ?? 0);
    $cleanliness = intval($_POST['cleanliness'] ?? 0);
    $staff_service = intval($_POST['staff_service'] ?? 0);
    $review_text = trim($_POST['review_text'] ?? '');
    
    // Calculate overall rating
    $overall_rating = round(($slot_condition + $safety_rating + $cleanliness + $staff_service) / 4);
    
    // Validate recommendation and ratings
    if (empty($recommend) || !in_array($recommend, ['yes', 'no'])) {
        $error = 'Please select if you recommend this service or not';
    } elseif ($slot_condition < 1 || $safety_rating < 1 || $cleanliness < 1 || $staff_service < 1) {
        $error = 'Please rate all features (1-5 stars)';
    } elseif (strlen($review_text) < 10) {
        $error = 'Please write at least 10 characters in your review';
    } else {
        // Check if user already submitted a review
        $check_query = $conn->prepare("SELECT id FROM reviews WHERE user_id = ?");
        $check_query->bind_param("i", $user_id);
        $check_query->execute();
        $existing = $check_query->get_result();
        
        if ($existing->num_rows > 0) {
            // Update existing review
            $stmt = $conn->prepare("UPDATE reviews SET recommend = ?, slot_condition = ?, safety_rating = ?, cleanliness = ?, staff_service = ?, overall_rating = ?, review_text = ?, created_at = NOW() WHERE user_id = ?");
            $stmt->bind_param("siiiidsi", $recommend, $slot_condition, $safety_rating, $cleanliness, $staff_service, $overall_rating, $review_text, $user_id);
            $message = 'Your review has been updated successfully!';
        } else {
            // Insert new review
            $stmt = $conn->prepare("INSERT INTO reviews (user_id, username, recommend, slot_condition, safety_rating, cleanliness, staff_service, overall_rating, review_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issiiiids", $user_id, $user_info['username'], $recommend, $slot_condition, $safety_rating, $cleanliness, $staff_service, $overall_rating, $review_text);
            $message = 'Thank you for your review! It has been submitted successfully.';
        }
        
        if ($stmt->execute()) {
            // Success
        } else {
            $error = 'Failed to submit review. Please try again.';
        }
    }
}

// Handle review deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $delete_stmt = $conn->prepare("DELETE FROM reviews WHERE user_id = ?");
    $delete_stmt->bind_param("i", $user_id);
    if ($delete_stmt->execute()) {
        header('Location: reviews.php?deleted=1');
        exit();
    } else {
        $error = 'Failed to delete review. Please try again.';
    }
}

// Check for success messages from redirects
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $message = 'Your review has been deleted successfully!';
}

// Get user's existing review if any
$user_review = null;
$user_review_query = $conn->prepare("SELECT * FROM reviews WHERE user_id = ?");
$user_review_query->bind_param("i", $user_id);
$user_review_query->execute();
$user_review_result = $user_review_query->get_result();
if ($user_review_result->num_rows > 0) {
    $user_review = $user_review_result->fetch_assoc();
}

// Get all reviews with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 5;
$offset = ($page - 1) * $per_page;

$total_reviews_query = $conn->query("SELECT COUNT(*) as total FROM reviews");
$total_reviews = $total_reviews_query->fetch_assoc()['total'];
$total_pages = ceil($total_reviews / $per_page);

$reviews_query = $conn->prepare("SELECT * FROM reviews ORDER BY created_at DESC LIMIT ? OFFSET ?");
$reviews_query->bind_param("ii", $per_page, $offset);
$reviews_query->execute();
$reviews = $reviews_query->get_result();

// Calculate average ratings
$avg_query = $conn->query("SELECT 
    AVG(slot_condition) as avg_slot,
    AVG(safety_rating) as avg_safety,
    AVG(cleanliness) as avg_clean,
    AVG(staff_service) as avg_staff,
    AVG(overall_rating) as avg_overall,
    COUNT(*) as total_count
FROM reviews");
$averages = $avg_query->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - ParkSmart</title>
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
            padding-bottom: 40px;
        }

        .page-header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .page-title {
            font-size: 24px;
            font-weight: 800;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-align: center;
        }

        .container {
            padding: 25px 20px;
            max-width: 900px;
            margin: 0 auto;
        }

        .stats-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .stats-title {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(102,126,234,0.05);
            border-radius: 12px;
        }

        .stat-label {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: #667eea;
        }

        .stars {
            color: #f39c12;
            font-size: 16px;
        }

        .review-form-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .form-title {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .rating-group {
            margin-bottom: 20px;
        }

        .rating-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .star-rating {
            display: flex;
            gap: 8px;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 32px;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #f39c12;
        }

        .textarea-field {
            width: 100%;
            padding: 14px;
            border: 2px solid rgba(102,126,234,0.2);
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            resize: vertical;
            min-height: 120px;
            transition: all 0.3s ease;
        }

        .textarea-field:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 4px 15px rgba(102,126,234,0.2);
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102,126,234,0.4);
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102,126,234,0.5);
        }
        .btn-delete {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(231,76,60,0.4);
            margin-top: 10px;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(231,76,60,0.5);
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
        }

        .button-container {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .button-container button {
            flex: 1;
        }
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }

        .message.success {
            background: rgba(39,174,96,0.1);
            color: #27ae60;
            border: 2px solid #27ae60;
        }

        .message.error {
            background: rgba(231,76,60,0.1);
            color: #e74c3c;
            border: 2px solid #e74c3c;
        }

        .reviews-section {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .review-card {
            background: rgba(102,126,234,0.05);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .reviewer-name {
            font-size: 16px;
            font-weight: 700;
            color: #2c3e50;
        }

        .review-date {
            font-size: 12px;
            color: #7f8c8d;
        }

        .review-ratings {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .rating-item {
            font-size: 12px;
            color: #555;
        }

        .rating-item .label {
            font-weight: 600;
            display: block;
            margin-bottom: 3px;
        }

        .review-text {
            color: #2c3e50;
            line-height: 1.6;
            font-size: 14px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .page-link {
            padding: 8px 16px;
            background: rgba(102,126,234,0.1);
            color: #667eea;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: rgba(102,126,234,0.2);
            transform: translateY(-2px);
        }

        .page-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .overall-badge {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <?php include 'user_nav.php'; ?>
    
    <div class="page-header">
        <div class="page-title">⭐ PARKING REVIEWS & RATINGS</div>
    </div>

    <div class="container">
        <!-- Overall Statistics -->
        <div class="stats-card">
            <div class="stats-title">Overall Ratings <?php if($averages['total_count'] > 0) echo "({$averages['total_count']} Reviews)"; ?></div>
            <?php if ($averages['total_count'] > 0): ?>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-label">Overall Rating</div>
                        <div class="stat-value"><?php echo number_format($averages['avg_overall'], 1); ?></div>
                        <div class="stars">
                            <?php 
                            $avg = round($averages['avg_overall']);
                            for($i = 1; $i <= 5; $i++) {
                                echo $i <= $avg ? '★' : '☆';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Slot Condition</div>
                        <div class="stat-value"><?php echo number_format($averages['avg_slot'], 1); ?></div>
                        <div class="stars">
                            <?php 
                            $avg = round($averages['avg_slot']);
                            for($i = 1; $i <= 5; $i++) {
                                echo $i <= $avg ? '★' : '☆';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Safety</div>
                        <div class="stat-value"><?php echo number_format($averages['avg_safety'], 1); ?></div>
                        <div class="stars">
                            <?php 
                            $avg = round($averages['avg_safety']);
                            for($i = 1; $i <= 5; $i++) {
                                echo $i <= $avg ? '★' : '☆';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Cleanliness</div>
                        <div class="stat-value"><?php echo number_format($averages['avg_clean'], 1); ?></div>
                        <div class="stars">
                            <?php 
                            $avg = round($averages['avg_clean']);
                            for($i = 1; $i <= 5; $i++) {
                                echo $i <= $avg ? '★' : '☆';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Staff Service</div>
                        <div class="stat-value"><?php echo number_format($averages['avg_staff'], 1); ?></div>
                        <div class="stars">
                            <?php 
                            $avg = round($averages['avg_staff']);
                            for($i = 1; $i <= 5; $i++) {
                                echo $i <= $avg ? '★' : '☆';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d;">No reviews yet. Be the first to review!</p>
            <?php endif; ?>
        </div>

        <!-- Review Form -->
        <div class="review-form-card">
            <div class="form-title"><?php echo $user_review ? 'Update Your Review' : 'Write a Review'; ?></div>
            
            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="rating-group">
                    <label class="rating-label" style="font-size:18px; color:#667eea; font-weight:800; margin-bottom:15px;">Do you recommend this service? *</label>
                    <div style="display:flex; gap:20px; margin-top:10px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:16px; padding:12px 24px; border:2px solid #4caf50; border-radius:10px; transition:all 0.3s ease; background:<?php echo ($user_review && $user_review['recommend'] == 'yes') ? 'linear-gradient(135deg, #4caf50 0%, #2e7d32 100%)' : '#fff'; ?>; color:<?php echo ($user_review && $user_review['recommend'] == 'yes') ? '#fff' : '#333'; ?>;">
                            <input type="radio" name="recommend" value="yes" required <?php echo ($user_review && $user_review['recommend'] == 'yes') ? 'checked' : ''; ?> style="width:20px; height:20px; cursor:pointer;">
                            <span style="font-weight:700;">✓ Recommend</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:16px; padding:12px 24px; border:2px solid #f44336; border-radius:10px; transition:all 0.3s ease; background:<?php echo ($user_review && $user_review['recommend'] == 'no') ? 'linear-gradient(135deg, #f44336 0%, #c62828 100%)' : '#fff'; ?>; color:<?php echo ($user_review && $user_review['recommend'] == 'no') ? '#fff' : '#333'; ?>;">
                            <input type="radio" name="recommend" value="no" required <?php echo ($user_review && $user_review['recommend'] == 'no') ? 'checked' : ''; ?> style="width:20px; height:20px; cursor:pointer;">
                            <span style="font-weight:700;">✗ Not Recommend</span>
                        </label>
                    </div>
                </div>

                <div class="rating-group" style="margin-top:30px;">
                    <label class="rating-label">Slot Condition *</label>
                    <div class="star-rating">
                        <input type="radio" name="slot_condition" value="5" id="slot5" <?php echo ($user_review && $user_review['slot_condition'] == 5) ? 'checked' : ''; ?>>
                        <label for="slot5">★</label>
                        <input type="radio" name="slot_condition" value="4" id="slot4" <?php echo ($user_review && $user_review['slot_condition'] == 4) ? 'checked' : ''; ?>>
                        <label for="slot4">★</label>
                        <input type="radio" name="slot_condition" value="3" id="slot3" <?php echo ($user_review && $user_review['slot_condition'] == 3) ? 'checked' : ''; ?>>
                        <label for="slot3">★</label>
                        <input type="radio" name="slot_condition" value="2" id="slot2" <?php echo ($user_review && $user_review['slot_condition'] == 2) ? 'checked' : ''; ?>>
                        <label for="slot2">★</label>
                        <input type="radio" name="slot_condition" value="1" id="slot1" <?php echo ($user_review && $user_review['slot_condition'] == 1) ? 'checked' : ''; ?>>
                        <label for="slot1">★</label>
                    </div>
                </div>

                <div class="rating-group">
                    <label class="rating-label">Safety & Security *</label>
                    <div class="star-rating">
                        <input type="radio" name="safety_rating" value="5" id="safety5" <?php echo ($user_review && $user_review['safety_rating'] == 5) ? 'checked' : ''; ?>>
                        <label for="safety5">★</label>
                        <input type="radio" name="safety_rating" value="4" id="safety4" <?php echo ($user_review && $user_review['safety_rating'] == 4) ? 'checked' : ''; ?>>
                        <label for="safety4">★</label>
                        <input type="radio" name="safety_rating" value="3" id="safety3" <?php echo ($user_review && $user_review['safety_rating'] == 3) ? 'checked' : ''; ?>>
                        <label for="safety3">★</label>
                        <input type="radio" name="safety_rating" value="2" id="safety2" <?php echo ($user_review && $user_review['safety_rating'] == 2) ? 'checked' : ''; ?>>
                        <label for="safety2">★</label>
                        <input type="radio" name="safety_rating" value="1" id="safety1" <?php echo ($user_review && $user_review['safety_rating'] == 1) ? 'checked' : ''; ?>>
                        <label for="safety1">★</label>
                    </div>
                </div>

                <div class="rating-group">
                    <label class="rating-label">Cleanliness *</label>
                    <div class="star-rating">
                        <input type="radio" name="cleanliness" value="5" id="clean5" <?php echo ($user_review && $user_review['cleanliness'] == 5) ? 'checked' : ''; ?>>
                        <label for="clean5">★</label>
                        <input type="radio" name="cleanliness" value="4" id="clean4" <?php echo ($user_review && $user_review['cleanliness'] == 4) ? 'checked' : ''; ?>>
                        <label for="clean4">★</label>
                        <input type="radio" name="cleanliness" value="3" id="clean3" <?php echo ($user_review && $user_review['cleanliness'] == 3) ? 'checked' : ''; ?>>
                        <label for="clean3">★</label>
                        <input type="radio" name="cleanliness" value="2" id="clean2" <?php echo ($user_review && $user_review['cleanliness'] == 2) ? 'checked' : ''; ?>>
                        <label for="clean2">★</label>
                        <input type="radio" name="cleanliness" value="1" id="clean1" <?php echo ($user_review && $user_review['cleanliness'] == 1) ? 'checked' : ''; ?>>
                        <label for="clean1">★</label>
                    </div>
                </div>

                <div class="rating-group">
                    <label class="rating-label">Staff Service *</label>
                    <div class="star-rating">
                        <input type="radio" name="staff_service" value="5" id="staff5" <?php echo ($user_review && $user_review['staff_service'] == 5) ? 'checked' : ''; ?>>
                        <label for="staff5">★</label>
                        <input type="radio" name="staff_service" value="4" id="staff4" <?php echo ($user_review && $user_review['staff_service'] == 4) ? 'checked' : ''; ?>>
                        <label for="staff4">★</label>
                        <input type="radio" name="staff_service" value="3" id="staff3" <?php echo ($user_review && $user_review['staff_service'] == 3) ? 'checked' : ''; ?>>
                        <label for="staff3">★</label>
                        <input type="radio" name="staff_service" value="2" id="staff2" <?php echo ($user_review && $user_review['staff_service'] == 2) ? 'checked' : ''; ?>>
                        <label for="staff2">★</label>
                        <input type="radio" name="staff_service" value="1" id="staff1" <?php echo ($user_review && $user_review['staff_service'] == 1) ? 'checked' : ''; ?>>
                        <label for="staff1">★</label>
                    </div>
                </div>

                <div class="rating-group">
                    <label class="rating-label">Your Review *</label>
                    <textarea name="review_text" class="textarea-field" placeholder="Share your experience with ParkSmart parking system..." required><?php echo $user_review ? htmlspecialchars($user_review['review_text']) : ''; ?></textarea>
                </div>

                <?php if ($user_review): ?>
                <div class="button-container">
                    <button type="submit" name="submit_review" class="btn-submit">
                        🔄 Update Review
                    </button>
                    <button type="submit" name="delete_review" class="btn-delete" onclick="return confirm('Are you sure you want to delete your review? This action cannot be undone.');">
                        🗑️ Delete Review
                    </button>
                </div>
                <?php else: ?>
                <button type="submit" name="submit_review" class="btn-submit">
                    ✍️ Submit Review
                </button>
                <?php endif; ?>
            </form>
        </div>

        <!-- All Reviews -->
        <div class="reviews-section">
            <div class="section-title">
                <span>💬 All Reviews (<?php echo $total_reviews; ?>)</span>
            </div>

            <?php if ($reviews->num_rows > 0): ?>
                <?php while ($review = $reviews->fetch_assoc()): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="reviewer-name">
                                <?php echo htmlspecialchars($review['username'] ?? 'Anonymous'); ?>
                                <span class="overall-badge">
                                    <?php echo $review['overall_rating']; ?>/5 ⭐
                                </span>
                            </div>
                            <div class="review-date">
                                <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div style="margin:15px 0; padding:12px; border-radius:8px; background:<?php echo ($review['recommend'] == 'yes' || !isset($review['recommend'])) ? 'linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%)' : 'linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%)'; ?>; border-left:4px solid <?php echo ($review['recommend'] == 'yes' || !isset($review['recommend'])) ? '#4caf50' : '#f44336'; ?>; font-weight:700; font-size:15px; color:#333;">
                            <?php 
                            $recommend_text = ($review['recommend'] == 'no') ? '✗ Does Not Recommend This Service' : '✓ Recommends This Service';
                            echo $recommend_text;
                            ?>
                        </div>
                        
                        <div class="review-ratings">
                            <div class="rating-item">
                                <span class="label">Slot Condition:</span>
                                <span class="stars">
                                    <?php 
                                    for($i = 1; $i <= 5; $i++) {
                                        echo $i <= $review['slot_condition'] ? '★' : '☆';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="rating-item">
                                <span class="label">Safety:</span>
                                <span class="stars">
                                    <?php 
                                    for($i = 1; $i <= 5; $i++) {
                                        echo $i <= $review['safety_rating'] ? '★' : '☆';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="rating-item">
                                <span class="label">Cleanliness:</span>
                                <span class="stars">
                                    <?php 
                                    for($i = 1; $i <= 5; $i++) {
                                        echo $i <= $review['cleanliness'] ? '★' : '☆';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="rating-item">
                                <span class="label">Staff Service:</span>
                                <span class="stars">
                                    <?php 
                                    for($i = 1; $i <= 5; $i++) {
                                        echo $i <= $review['staff_service'] ? '★' : '☆';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="review-text">
                            "<?php echo htmlspecialchars($review['review_text']); ?>"
                        </div>
                    </div>
                <?php endwhile; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="page-link">« Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="page-link">Next »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; padding: 40px;">No reviews yet. Be the first to share your experience!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

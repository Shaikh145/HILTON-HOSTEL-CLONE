<?php
require_once 'db.php';

// Get hotel ID and date parameters
$hotelId = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
$checkIn = isset($_GET['check_in']) ? $_GET['check_in'] : '';
$checkOut = isset($_GET['check_out']) ? $_GET['check_out'] : '';

// Validate hotel ID
if ($hotelId <= 0) {
    header('Location: index.php');
    exit;
}

// Get hotel details
$stmt = $conn->prepare("SELECT * FROM hotels WHERE hotel_id = :hotel_id");
$stmt->bindParam(':hotel_id', $hotelId);
$stmt->execute();
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) {
    header('Location: index.php');
    exit;
}

// Get hotel amenities
$amenityStmt = $conn->prepare("SELECT a.* FROM amenities a 
                              JOIN hotel_amenities ha ON a.amenity_id = ha.amenity_id 
                              WHERE ha.hotel_id = :hotel_id");
$amenityStmt->bindParam(':hotel_id', $hotelId);
$amenityStmt->execute();
$hotelAmenities = $amenityStmt->fetchAll(PDO::FETCH_ASSOC);

// Get room types for this hotel
$roomStmt = $conn->prepare("SELECT * FROM room_types WHERE hotel_id = :hotel_id ORDER BY price_per_night");
$roomStmt->bindParam(':hotel_id', $hotelId);
$roomStmt->execute();
$roomTypes = $roomStmt->fetchAll(PDO::FETCH_ASSOC);

// Get reviews for this hotel
$reviewStmt = $conn->prepare("SELECT * FROM reviews WHERE hotel_id = :hotel_id ORDER BY review_date DESC LIMIT 5");
$reviewStmt->bindParam(':hotel_id', $hotelId);
$reviewStmt->execute();
$reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate nights of stay if dates are provided
$nights = 1;
if (!empty($checkIn) && !empty($checkOut)) {
    $checkInDate = new DateTime($checkIn);
    $checkOutDate = new DateTime($checkOut);
    $interval = $checkInDate->diff($checkOutDate);
    $nights = $interval->days;
}

// Format dates for display
$checkInFormatted = !empty($checkIn) ? date('M d, Y', strtotime($checkIn)) : '';
$checkOutFormatted = !empty($checkOut) ? date('M d, Y', strtotime($checkOut)) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hotel['name']); ?> | Hilton Hotels</title>
    <style>
        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        a {
            text-decoration: none;
            color: inherit;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .btn {
            display: inline-block;
            background-color: #00256e;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-transform: uppercase;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #001c54;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid #00256e;
            color: #00256e;
        }
        
        .btn-outline:hover {
            background-color: #00256e;
            color: white;
        }
        
        /* Header Styles */
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #00256e;
        }
        
        .logo span {
            color: #d4af37;
        }
        
        .nav-links {
            display: flex;
            gap: 25px;
        }
        
        .nav-links a {
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #00256e;
        }
        
        /* Hotel Banner */
        .hotel-banner {
            height: 400px;
            background-image: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('<?php echo htmlspecialchars($hotel['banner_image']); ?>');
            background-size: cover;
            background-position: center;
            color: white;
            display: flex;
            align-items: flex-end;
        }
        
        .banner-content {
            padding: 40px 0;
        }
        
        .banner-content h1 {
            font-size: 40px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .hotel-location {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 18px;
            margin-bottom: 15px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        }
        
        .hotel-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        }
        
        .stars {
            color: #d4af37;
            font-size: 20px;
        }
        
        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            padding: 40px 0;
        }
        
        /* Hotel Details */
        .hotel-details {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #00256e;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .hotel-description {
            margin-bottom: 30px;
        }
        
        .amenities-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .amenity-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .amenity-item i {
            color: #00256e;
        }
        
        /* Room Types */
        .room-types {
            margin-top: 40px;
        }
        
        .room-card {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 25px;
            display: grid;
            grid-template-columns: 250px 1fr;
        }
        
        .room-image {
            height: 100%;
            overflow: hidden;
        }
        
        .room-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .room-image img:hover {
            transform: scale(1.05);
        }
        
        .room-details {
            padding: 20px;
        }
        
        .room-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .room-name h3 {
            font-size: 20px;
            color: #00256e;
            margin-bottom: 5px;
        }
        
        .room-price {
            text-align: right;
        }
        
        .room-price .price {
            font-size: 24px;
            font-weight: bold;
            color: #00256e;
        }
        
        .room-info {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .room-feature {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 14px;
        }
        
        .room-description {
            margin-bottom: 15px;
            color: #666;
        }
        
        .room-amenities {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .room-amenity {
            background-color: #f5f5f5;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            color: #666;
        }
        
        .room-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .room-total {
            font-size: 14px;
        }
        
        .room-total strong {
            font-size: 18px;
            color: #00256e;
        }
        
        /* Booking Widget */
        .booking-widget {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            position: sticky;
            top: 90px;
        }
        
        .booking-widget h3 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #00256e;
            text-align: center;
        }
        
        .widget-form {
            margin-bottom: 20px;
        }
        
        .widget-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .widget-form input, 
        .widget-form select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .price-preview {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .price-row.total {
            font-weight: bold;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .price-preview button {
            width: 100%;
            margin-top: 10px;
        }
        
        /* Reviews Section */
        .reviews {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-top: 30px;
        }
        
        .review-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .rating-average {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .rating-average .score {
            font-size: 48px;
            font-weight: bold;
            color: #00256e;
            line-height: 1;
        }
        
        .rating-average .stars {
            margin: 10px 0;
        }
        
        .review-item {
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .review-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .reviewer-name {
            font-weight: bold;
        }
        
        .review-date {
            color: #666;
            font-size: 14px;
        }
        
        .review-rating {
            color: #d4af37;
            margin-bottom: 10px;
        }
        
        .review-comment {
            color: #333;
        }
        
        /* Map Section (Placeholder) */
        .map-section {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-top: 30px;
        }
        
        .map-placeholder {
            height: 300px;
            background-color: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 18px;
        }
        
        /* Footer */
        footer {
            background-color: #222;
            color: white;
            padding: 60px 0 20px;
            margin-top: 60px;
        }
        
        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-column h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #d4af37;
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column ul li {
            margin-bottom: 10px;
        }
        
        .footer-column ul li a {
            color: #aaa;
            transition: color 0.3s;
        }
        
        .footer-column ul li a:hover {
            color: white;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-links a {
            display: inline-block;
            background-color: #444;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            transition: background-color 0.3s;
        }
        
        .social-links a:hover {
            background-color: #d4af37;
        }
        
        .copyright {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #444;
            color: #aaa;
        }
        
        /* Media Queries */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .hotel-banner {
                height: 300px;
            }
            
            .banner-content h1 {
                font-size: 32px;
            }
            
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .booking-widget {
                position: static;
                margin-bottom: 30px;
            }
            
            .room-card {
                grid-template-columns: 1fr;
            }
            
            .room-image {
                height: 200px;
            }
            
            .room-footer {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-container">
            <div class="logo">HILTON <span>HOTELS</span></div>
            <nav class="nav-links">
                <a href="index.php">Home</a>
                <a href="#">About</a>
                <a href="#">Destinations</a>
                <a href="#">Contact</a>
            </nav>
        </div>
    </header>

    <!-- Hotel Banner -->
    <section class="hotel-banner">
        <div class="container banner-content">
            <h1><?php echo htmlspecialchars($hotel['name']); ?></h1>
            <div class="hotel-location">
                <span>📍</span>
                <span><?php echo htmlspecialchars($hotel['address']); ?></span>
            </div>
            <div class="hotel-rating">
                <div class="stars">
                    <?php
                    $rating = round($hotel['rating'] * 2) / 2; // Round to nearest 0.5
                    $fullStars = floor($rating);
                    $halfStar = $rating - $fullStars >= 0.5;
                    
                    for ($i = 0; $i < $fullStars; $i++) {
                        echo '★';
                    }
                    
                    if ($halfStar) {
                        echo '★';
                    }
                    ?>
                </div>
                <div><?php echo number_format($hotel['rating'], 1); ?>/5 based on guest reviews</div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container main-content">
        <div class="left-column">
            <!-- Hotel Details -->
            <section class="hotel-details">
                <h2 class="section-title">About This Hotel</h2>
                <div class="hotel-description">
                    <p><?php echo htmlspecialchars($hotel['description']); ?></p>
                </div>
                
                <h3 class="section-title">Amenities</h3>
                <div class="amenities-list">
                    <?php foreach($hotelAmenities as $amenity): ?>
                        <div class="amenity-item">
                            <i>✓</i>
                            <span><?php echo htmlspecialchars($amenity['name']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <!-- Room Types -->
            <section class="room-types">
                <h2 class="section-title">Available Room Types</h2>
                
                <?php if(count($roomTypes) > 0): ?>
                    <?php foreach($roomTypes as $room): ?>
                        <?php
                        // Get room amenities
                        $roomAmenityStmt = $conn->prepare("SELECT a.name FROM amenities a 
                                                         JOIN room_amenities ra ON a.amenity_id = ra.amenity_id 
                                                         WHERE ra.room_type_id = :room_type_id");
                        $roomAmenityStmt->bindParam(':room_type_id', $room['room_type_id']);
                        $roomAmenityStmt->execute();
                        $roomAmenities = $roomAmenityStmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        // Calculate total price for stay
                        $totalPrice = $room['price_per_night'] * $nights;
                        ?>
                        
                        <div class="room-card">
                            <div class="room-image">
                                <img src="<?php echo htmlspecialchars($room['image']); ?>" alt="<?php echo htmlspecialchars($room['name']); ?>">
                            </div>
                            <div class="room-details">
                                <div class="room-header">
                                    <div class="room-name">
                                        <h3><?php echo htmlspecialchars($room['name']); ?></h3>
                                    </div>
                                    <div class="room-price">
                                        <div class="price">$<?php echo number_format($room['price_per_night'], 2); ?></div>
                                        <div>per night</div>
                                    </div>
                                </div>
                                
                                <div class="room-info">
                                    <div class="room-feature">
                                        <i>👥</i>
                                        <span>Sleeps <?php echo htmlspecialchars($room['capacity']); ?></span>
                                    </div>
                                    <div class="room-feature">
                                        <i>🛏️</i>
                                        <span><?php echo htmlspecialchars($room['beds']); ?></span>
                                    </div>
                                    <div class="room-feature">
                                        <i>📏</i>
                                        <span><?php echo htmlspecialchars($room['room_size']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="room-description">
                                    <p><?php echo htmlspecialchars($room['description']); ?></p>
                                </div>
                                
                                <div class="room-amenities">
                                    <?php foreach($roomAmenities as $amenity): ?>
                                        <div class="room-amenity"><?php echo htmlspecialchars($amenity); ?></div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="room-footer">
                                    <div class="room-total">
                                        <?php if(!empty($checkIn) && !empty($checkOut)): ?>
                                            Total for <?php echo $nights; ?> night<?php echo $nights != 1 ? 's' : ''; ?>: 
                                            <strong>$<?php echo number_format($totalPrice, 2); ?></strong>
                                        <?php endif; ?>
                                    </div>
                                    <a href="booking.php?room_id=<?php echo $room['room_type_id']; ?>&check_in=<?php echo urlencode($checkIn); ?>&check_out=<?php echo urlencode($checkOut); ?>" class="btn">Book Now</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No rooms available for this hotel.</p>
                <?php endif; ?>
            </section>
            
            <!-- Reviews Section -->
            <section class="reviews">
                <h2 class="section-title">Guest Reviews</h2>
                
                <?php if(count($reviews) > 0): ?>
                    <?php
                    // Calculate average rating
                    $totalRating = 0;
                    foreach($reviews as $review) {
                        $totalRating += $review['rating'];
                    }
                    $averageRating = $totalRating / count($reviews);
                    ?>
                    
                    <div class="review-summary">
                        <div class="rating-average">
                            <div class="score"><?php echo number_format($averageRating, 1); ?></div>
                            <div class="stars">
                                <?php
                                $fullStars = floor($averageRating);
                                $halfStar = $averageRating - $fullStars >= 0.5;
                                
                                for ($i = 0; $i < $fullStars; $i++) {
                                    echo '★';
                                }
                                
                                if ($halfStar) {
                                    echo '★';
                                }
                                ?>
                            </div>
                            <div>Based on <?php echo count($reviews); ?> review<?php echo count($reviews) != 1 ? 's' : ''; ?></div>
                        </div>
                    </div>
                    
                    <div class="reviews-list">
                        <?php foreach($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-name"><?php echo htmlspecialchars($review['guest_name']); ?></div>
                                    <div class="review-date"><?php echo date('F j, Y', strtotime($review['review_date'])); ?></div>
                                </div>
                                <div class="review-rating">
                                    <?php
                                    $rating = $review['rating'];
                                    $fullStars = floor($rating);
                                    $halfStar = $rating - $fullStars >= 0.5;
                                    
                                    for ($i = 0; $i < $fullStars; $i++) {
                                        echo '★';
                                    }
                                    
                                    if ($halfStar) {
                                        echo '★';
                                    }
                                    ?>
                                    (<?php echo number_format($rating, 1); ?>)
                                </div>
                                <div class="review-comment">
                                    <?php echo htmlspecialchars($review['comment']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No reviews available for this hotel yet.</p>
                <?php endif; ?>
            </section>
            
            <!-- Map Section (Placeholder) -->
            <section class="map-section">
                <div class="map-placeholder">
                    <p>Map showing location of <?php echo htmlspecialchars($hotel['name']); ?></p>
                </div>
            </section>
        </div>
        
        <div class="right-column">
            <!-- Booking Widget -->
            <aside class="booking-widget">
                <h3>Check Availability</h3>
                
                <form class="widget-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET">
                    <input type="hidden" name="hotel_id" value="<?php echo $hotelId; ?>">
                    
                    <div class="form-group">
                        <label for="check_in">Check In</label>
                        <input type="date" id="check_in" name="check_in" required value="<?php echo $checkIn; ?>" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="check_out">Check Out</label>
                        <input type="date" id="check_out" name="check_out" required value="<?php echo $checkOut; ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="guests">Guests</label>
                        <select id="guests" name="guests">
                            <option value="1">1 Guest</option>
                            <option value="2" selected>2 Guests</option>
                            <option value="3">3 Guests</option>
                            <option value="4">4 Guests</option>
                            <option value="5">5+ Guests</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">Check Availability</button>
                </form>
                
                <?php if(!empty($checkIn) && !empty($checkOut) && isset($roomTypes[0])): ?>
                    <div class="price-preview">
                        <h4>Price Details</h4>
                        <div class="price-row">
                            <span>Lowest Price Room:</span>
                            <span>$<?php echo number_format($roomTypes[0]['price_per_night'], 2); ?> / night</span>
                        </div>
                        <div class="price-row">
                            <span>Stay:</span>
                            <span><?php echo $nights; ?> night<?php echo $nights != 1 ? 's' : ''; ?></span>
                        </div>
                        <div class="price-row total">
                            <span>Total from:</span>
                            <span>$<?php echo number_format($roomTypes[0]['price_per_night'] * $nights, 2); ?></span>
                        </div>
                        
                        <a href="booking.php?room_id=<?php echo $roomTypes[0]['room_type_id']; ?>&check_in=<?php echo urlencode($checkIn); ?>&check_out=<?php echo urlencode($checkOut); ?>" class="btn">Book Now</a>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-container">
                <div class="footer-column">
                    <h3>Hilton Hotels</h3>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">News & Media</a></li>
                        <li><a href="#">Investor Relations</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Our Brands</h3>
                    <ul>
                        <li><a href="#">Hilton Hotels & Resorts</a></li>
                        <li><a href="#">Conrad Hotels</a></li>
                        <li><a href="#">DoubleTree by Hilton</a></li>
                        <li><a href="#">Hampton by Hilton</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Help</h3>
                    <ul>
                        <li><a href="#">Customer Support</a></li>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Terms & Conditions</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Connect With Us</h3>
                    <div class="social-links">
                        <a href="#">F</a>
                        <a href="#">T</a>
                        <a href="#">I</a>
                        <a href="#">Y</a>
                    </div>
                </div>
            </div>
            
            <div class="copyright">
                &copy; 2023 Hilton Hotels. All Rights Reserved.
            </div>
        </div>
    </footer>

    <script>
        // Ensure check-out date is after check-in date
        document.getElementById('check_in').addEventListener('change', function() {
            const checkInDate = new Date(this.value);
            const checkOutInput = document.getElementById('check_out');
            
            // Set minimum check-out date to day after check-in
            const minCheckOutDate = new Date(checkInDate);
            minCheckOutDate.setDate(minCheckOutDate.getDate() + 1);
            
            const minCheckOutDateStr = minCheckOutDate.toISOString().split('T')[0];
            checkOutInput.min = minCheckOutDateStr;
            
            // If current check-out date is before new min date, update it
            if (new Date(checkOutInput.value) <= checkInDate) {
                checkOutInput.value = minCheckOutDateStr;
            }
        });

        // Initialize map placeholder (this would be replaced with actual map implementation)
        const mapPlaceholder = document.querySelector('.map-placeholder');
        mapPlaceholder.textContent = `Map showing location of ${<?php echo json_encode($hotel['name']); ?>} at ${<?php echo json_encode($hotel['address']); ?>}`;
    </script>
</body>
</html>

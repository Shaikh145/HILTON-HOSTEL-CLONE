<?php
require_once 'db.php';

// Get booking ID
$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

// Validate booking ID
if ($bookingId <= 0) {
    header('Location: index.php');
    exit;
}

// Get booking details with room and hotel information
$stmt = $conn->prepare("SELECT b.*, rt.name as room_name, rt.image as room_image, rt.price_per_night, 
                        h.name as hotel_name, h.location, h.address, h.thumbnail as hotel_image
                        FROM bookings b
                        JOIN room_types rt ON b.room_type_id = rt.room_type_id
                        JOIN hotels h ON rt.hotel_id = h.hotel_id
                        WHERE b.booking_id = :booking_id");
$stmt->bindParam(':booking_id', $bookingId);
$stmt->execute();
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: index.php');
    exit;
}

// Calculate nights of stay
$checkInDate = new DateTime($booking['check_in_date']);
$checkOutDate = new DateTime($booking['check_out_date']);
$interval = $checkInDate->diff($checkOutDate);
$nights = $interval->days;

// Format dates for display
$checkInFormatted = date('l, M d, Y', strtotime($booking['check_in_date']));
$checkOutFormatted = date('l, M d, Y', strtotime($booking['check_out_date']));

// Generate confirmation code
$confirmationCode = strtoupper(substr(md5($booking['booking_id']), 0, 8));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation | Hilton Hotels</title>
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
        
        /* Confirmation Header */
        .confirmation-header {
            background-color: #d4af37;
            color: white;
            padding: 40px 0;
            text-align: center;
        }
        
        .confirmation-header i {
            font-size: 60px;
            margin-bottom: 20px;
            display: block;
        }
        
        .confirmation-title {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .confirmation-subtitle {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .confirmation-code {
            background-color: white;
            color: #00256e;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 4px;
            margin: 0 5px;
        }
        
        /* Main Content */
        .main-content {
            padding: 60px 0;
        }
        
        .confirmation-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Booking Details */
        .booking-details {
            padding: 30px;
        }
        
        .section-title {
            font-size: 24px;
            margin-bottom: 25px;
            color: #00256e;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin-bottom: 20px;
        }
        
        .detail-item {
            padding-right: 20px;
        }
        
        .detail-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-weight: bold;
            font-size: 16px;
        }
        
        /* Hotel Info */
        .hotel-info {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 20px;
            margin: 30px 0;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .hotel-image {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .hotel-image img {
            width: 100%;
            height: 100px;
            object-fit: cover;
        }
        
        .hotel-details h3 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #00256e;
        }
        
        .hotel-address {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        /* Payment Info */
        .payment-info {
            background-color: #f9f9f9;
            padding: 20px 30px;
            border-top: 1px solid #eee;
        }
        
        .payment-title {
            font-size: 18px;
            color: #00256e;
            margin-bottom: 15px;
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
            font-size: 18px;
        }
        
        /* Actions */
        .confirmation-actions {
            padding: 30px;
            text-align: center;
            background-color: white;
            border-top: 1px solid #eee;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        /* Travel Info */
        .travel-info {
            margin-top: 40px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }
        
        .info-item {
            text-align: center;
        }
        
        .info-item i {
            font-size: 36px;
            color: #d4af37;
            margin-bottom: 15px;
            display: block;
        }
        
        .info-item h4 {
            margin-bottom: 10px;
            color: #00256e;
        }
        
        .info-item p {
            color: #666;
            font-size: 14px;
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
            
            .confirmation-title {
                font-size: 26px;
            }
            
            .confirmation-subtitle {
                font-size: 16px;
            }
            
            .detail-row {
                grid-template-columns: 1fr;
            }
            
            .detail-item {
                margin-bottom: 15px;
            }
            
            .hotel-info {
                grid-template-columns: 1fr;
            }
            
            .hotel-image img {
                height: 150px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }

        @media print {
            header, footer, .confirmation-actions, .travel-info {
                display: none;
            }
            
            .confirmation-header {
                background-color: #fff;
                color: #000;
                padding: 20px 0;
            }
            
            .confirmation-container {
                box-shadow: none;
            }
            
            .payment-info {
                background-color: #fff;
            }
            
            body {
                background-color: #fff;
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

    <!-- Confirmation Header -->
    <section class="confirmation-header">
        <div class="container">
            <i>✓</i>
            <h1 class="confirmation-title">Booking Confirmed!</h1>
            <p class="confirmation-subtitle">Your confirmation code is <span class="confirmation-code"><?php echo $confirmationCode; ?></span></p>
            <p class="confirmation-subtitle">A confirmation email has been sent to <?php echo htmlspecialchars($booking['guest_email']); ?></p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Booking Confirmation -->
            <div class="confirmation-container">
                <!-- Booking Details -->
                <div class="booking-details">
                    <h2 class="section-title">Booking Details</h2>
                    
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Confirmation Code</div>
                            <div class="detail-value"><?php echo $confirmationCode; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Booking Date</div>
                            <div class="detail-value"><?php echo date('F d, Y', strtotime($booking['booking_date'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Check-in</div>
                            <div class="detail-value"><?php echo $checkInFormatted; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Check-out</div>
                            <div class="detail-value"><?php echo $checkOutFormatted; ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Room Type</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['room_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Guests</div>
                            <div class="detail-value">
                                <?php echo $booking['adults']; ?> Adult<?php echo $booking['adults'] != 1 ? 's' : ''; ?>
                                <?php if($booking['children'] > 0): ?>
                                    , <?php echo $booking['children']; ?> Child<?php echo $booking['children'] != 1 ? 'ren' : ''; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Guest Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Contact Information</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($booking['guest_email']); ?><br>
                                <?php echo htmlspecialchars($booking['guest_phone']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if(!empty($booking['special_requests'])): ?>
                    <div class="detail-row">
                        <div class="detail-item" style="grid-column: 1 / span 2;">
                            <div class="detail-label">Special Requests</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['special_requests']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Hotel Info -->
                    <div class="hotel-info">
                        <div class="hotel-image">
                            <img src="<?php echo htmlspecialchars($booking['hotel_image']); ?>" alt="<?php echo htmlspecialchars($booking['hotel_name']); ?>">
                        </div>
                        <div class="hotel-details">
                            <h3><?php echo htmlspecialchars($booking['hotel_name']); ?></h3>
                            <div class="hotel-address"><?php echo htmlspecialchars($booking['address']); ?></div>
                            <div class="hotel-contact">
                                <strong>Check-in time:</strong> After 3:00 PM<br>
                                <strong>Check-out time:</strong> Before 12:00 PM
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Info -->
                <div class="payment-info">
                    <h3 class="payment-title">Payment Summary</h3>
                    <div class="price-row">
                        <span>Room Rate:</span>
                        <span>$<?php echo number_format($booking['price_per_night'], 2); ?> / night</span>
                    </div>
                    <div class="price-row">
                        <span>Stay Duration:</span>
                        <span><?php echo $nights; ?> night<?php echo $nights != 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="price-row">
                        <span>Taxes & Fees:</span>
                        <span>Included</span>
                    </div>
                    <div class="price-row total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($booking['total_price'], 2); ?></span>
                    </div>
                    <div class="price-row" style="font-size: 14px; color: #666; margin-top: 10px;">
                        <span>Payment Status:</span>
                        <span>
                            <?php if($booking['payment_status'] === 'pending'): ?>
                                Payment will be collected at check-in
                            <?php else: ?>
                                <?php echo ucfirst($booking['payment_status']); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="confirmation-actions">
                    <p>Need to make changes to your reservation? <br>Contact us or manage your booking online.</p>
                    <div class="action-buttons">
                        <button class="btn" onclick="window.print()">Print Confirmation</button>
                        <a href="index.php" class="btn btn-outline">Back to Home</a>
                    </div>
                </div>
            </div>
            
            <!-- Travel Info -->
            <div class="travel-info">
                <h2 class="section-title">Important Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <i>🏨</i>
                        <h4>Check-in Information</h4>
                        <p>Check-in starts at 3:00 PM. Early check-in may be available upon request, subject to availability.</p>
                    </div>
                    <div class="info-item">
                        <i>🔑</i>
                        <h4>Requirements at Check-in</h4>
                        <p>Please present a valid photo ID and the credit card used for booking. A security deposit may be required.</p>
                    </div>
                    <div class="info-item">
                        <i>📝</i>
                        <h4>Cancellation Policy</h4>
                        <p>Free cancellation until 48 hours before check-in. Late cancellations or no-shows may incur charges.</p>
                    </div>
                </div>
            </div>
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
        // Send confirmation email (this would normally be handled server-side)
        document.addEventListener('DOMContentLoaded', function() {
            // This is just for demonstration purposes
            console.log('Booking confirmation displayed for booking ID: <?php echo $bookingId; ?>');
        });
    </script>
</body>
</html>

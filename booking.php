<?php
require_once 'db.php';

// Get room ID and date parameters
$roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$checkIn = isset($_GET['check_in']) ? $_GET['check_in'] : '';
$checkOut = isset($_GET['check_out']) ? $_GET['check_out'] : '';

// Validate required parameters
if ($roomId <= 0 || empty($checkIn) || empty($checkOut)) {
    header('Location: index.php');
    exit;
}

// Get room details
$stmt = $conn->prepare("SELECT rt.*, h.name as hotel_name, h.location, h.address 
                        FROM room_types rt 
                        JOIN hotels h ON rt.hotel_id = h.hotel_id 
                        WHERE rt.room_type_id = :room_id");
$stmt->bindParam(':room_id', $roomId);
$stmt->execute();
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    header('Location: index.php');
    exit;
}

// Calculate nights of stay
$checkInDate = new DateTime($checkIn);
$checkOutDate = new DateTime($checkOut);
$interval = $checkInDate->diff($checkOutDate);
$nights = $interval->days;

// Calculate total price
$totalPrice = $room['price_per_night'] * $nights;

// Format dates for display
$checkInFormatted = date('l, F j, Y', strtotime($checkIn));
$checkOutFormatted = date('l, F j, Y', strtotime($checkOut));

// Process booking form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $guestName = isset($_POST['guest_name']) ? trim($_POST['guest_name']) : '';
    $guestEmail = isset($_POST['guest_email']) ? trim($_POST['guest_email']) : '';
    $guestPhone = isset($_POST['guest_phone']) ? trim($_POST['guest_phone']) : '';
    $adults = isset($_POST['adults']) ? (int)$_POST['adults'] : 1;
    $children = isset($_POST['children']) ? (int)$_POST['children'] : 0;
    $specialRequests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';
    
    // Perform validation
    if (empty($guestName)) {
        $errors[] = 'Please enter your full name';
    }
    
    if (empty($guestEmail) || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($guestPhone)) {
        $errors[] = 'Please enter your phone number';
    }
    
    if ($adults <= 0) {
        $errors[] = 'At least one adult is required';
    }
    
    if ($adults + $children > $room['capacity']) {
        $errors[] = 'The number of guests exceeds the room capacity';
    }
    
    // If no errors, process the booking
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO bookings (room_type_id, guest_name, guest_email, guest_phone, 
                                   check_in_date, check_out_date, adults, children, special_requests, total_price) 
                                   VALUES (:room_type_id, :guest_name, :guest_email, :guest_phone, 
                                   :check_in_date, :check_out_date, :adults, :children, :special_requests, :total_price)");
            
            $stmt->bindParam(':room_type_id', $roomId);
            $stmt->bindParam(':guest_name', $guestName);
            $stmt->bindParam(':guest_email', $guestEmail);
            $stmt->bindParam(':guest_phone', $guestPhone);
            $stmt->bindParam(':check_in_date', $checkIn);
            $stmt->bindParam(':check_out_date', $checkOut);
            $stmt->bindParam(':adults', $adults);
            $stmt->bindParam(':children', $children);
            $stmt->bindParam(':special_requests', $specialRequests);
            $stmt->bindParam(':total_price', $totalPrice);
            
            $stmt->execute();
            $bookingId = $conn->lastInsertId();
            
            // Redirect to confirmation page
            header("Location: confirmation.php?booking_id=" . $bookingId);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'An error occurred while processing your booking. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Stay | Hilton Hotels</title>
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
        
        /* Booking Section */
        .booking-section {
            padding: 60px 0;
        }
        
        .booking-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .page-title {
            margin-bottom: 40px;
            color: #00256e;
            font-size: 32px;
            text-align: center;
        }
        
        /* Booking Form */
        .booking-form {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #00256e;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .checkbox-group input {
            margin-right: 10px;
            width: auto;
        }
        
        .form-note {
            margin-top: 25px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
            font-size: 14px;
            color: #666;
        }
        
        .form-note h4 {
            margin-bottom: 10px;
            color: #00256e;
        }
        
        .error-message {
            color: #d9534f;
            background-color: #fdf7f7;
            border: 1px solid #d9534f;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .error-message ul {
            margin-left: 20px;
        }
        
        /* Booking Summary */
        .booking-summary {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 90px;
        }
        
        .summary-image {
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .summary-image img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .summary-hotel {
            margin-bottom: 15px;
        }
        
        .summary-hotel h3 {
            font-size: 20px;
            margin-bottom: 5px;
            color: #00256e;
        }
        
        .summary-hotel p {
            color: #666;
            font-size: 14px;
        }
        
        .summary-room {
            margin-bottom: 20px;
        }
        
        .summary-room h4 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .summary-room p {
            color: #666;
            font-size: 14px;
        }
        
        .summary-dates {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .date-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .date-row span {
            color: #666;
        }
        
        .date-row strong {
            font-weight: bold;
        }
        
        .price-summary {
            margin-bottom: 20px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .price-row.total {
            font-size: 18px;<?php
require_once 'db.php';

// Get room ID and date parameters
$roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$checkIn = isset($_GET['check_in']) ? $_GET['check_in'] : '';
$checkOut = isset($_GET['check_out']) ? $_GET['check_out'] : '';

// Validate room ID and dates
if ($roomId <= 0 || empty($checkIn) || empty($checkOut)) {
    header('Location: index.php');
    exit;
}

// Get room details
$stmt = $conn->prepare("SELECT rt.*, h.name as hotel_name, h.location, h.address 
                        FROM room_types rt 
                        JOIN hotels h ON rt.hotel_id = h.hotel_id 
                        WHERE rt.room_type_id = :room_id");
$stmt->bindParam(':room_id', $roomId);
$stmt->execute();
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    header('Location: index.php');
    exit;
}

// Calculate nights of stay
$checkInDate = new DateTime($checkIn);
$checkOutDate = new DateTime($checkOut);
$interval = $checkInDate->diff($checkOutDate);
$nights = $interval->days;

// Calculate total price
$totalPrice = $room['price_per_night'] * $nights;

// Format dates for display
$checkInFormatted = date('M d, Y', strtotime($checkIn));
$checkOutFormatted = date('M d, Y', strtotime($checkOut));

// Handle form submission
$errors = [];
$formSubmitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formSubmitted = true;
    
    // Get form data
    $guestName = isset($_POST['guest_name']) ? trim($_POST['guest_name']) : '';
    $guestEmail = isset($_POST['guest_email']) ? trim($_POST['guest_email']) : '';
    $guestPhone = isset($_POST['guest_phone']) ? trim($_POST['guest_phone']) : '';
    $adults = isset($_POST['adults']) ? (int)$_POST['adults'] : 1;
    $children = isset($_POST['children']) ? (int)$_POST['children'] : 0;
    $specialRequests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';
    
    // Validate form data
    if (empty($guestName)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($guestEmail)) {
        $errors[] = 'Email address is required';
    } elseif (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($guestPhone)) {
        $errors[] = 'Phone number is required';
    }
    
    if ($adults < 1) {
        $errors[] = 'At least 1 adult is required';
    }
    
    if ($adults + $children > $room['capacity']) {
        $errors[] = 'The number of guests exceeds the room capacity of ' . $room['capacity'];
    }
    
    // If no errors, process booking
    if (empty($errors)) {
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // Insert booking into database
            $bookingStmt = $conn->prepare("INSERT INTO bookings 
                                         (room_type_id, guest_name, guest_email, guest_phone, 
                                          check_in_date, check_out_date, adults, children, 
                                          special_requests, total_price, payment_status) 
                                         VALUES 
                                         (:room_id, :guest_name, :guest_email, :guest_phone, 
                                          :check_in, :check_out, :adults, :children, 
                                          :special_requests, :total_price, 'pending')");
            
            $bookingStmt->bindParam(':room_id', $roomId);
            $bookingStmt->bindParam(':guest_name', $guestName);
            $bookingStmt->bindParam(':guest_email', $guestEmail);
            $bookingStmt->bindParam(':guest_phone', $guestPhone);
            $bookingStmt->bindParam(':check_in', $checkIn);
            $bookingStmt->bindParam(':check_out', $checkOut);
            $bookingStmt->bindParam(':adults', $adults);
            $bookingStmt->bindParam(':children', $children);
            $bookingStmt->bindParam(':special_requests', $specialRequests);
            $bookingStmt->bindParam(':total_price', $totalPrice);
            $bookingStmt->execute();
            
            // Get the booking ID
            $bookingId = $conn->lastInsertId();
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to confirmation page
            header("Location: confirmation.php?booking_id=$bookingId");
            exit;
            
        } catch (PDOException $e) {
            // Rollback transaction in case of error
            $conn->rollBack();
            $errors[] = 'Booking failed: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Stay | <?php echo htmlspecialchars($room['name']); ?> | Hilton Hotels</title>
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

        .btn-block {
            display: block;
            width: 100%;
            text-align: center;
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
        
        /* Booking Header */
        .booking-header {
            background-color: #00256e;
            color: white;
            padding: 20px 0;
        }
        
        .booking-title {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .booking-subtitle {
            font-size: 16px;
            opacity: 0.8;
        }
        
        /* Main Content */
        .main-content {
            padding: 40px 0;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        /* Booking Form */
        .booking-form {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .section-title {
            font-size: 24px;
            margin-bottom: 25px;
            color: #00256e;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .form-group .error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-note {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .error-message {
            background-color: #fde8e8;
            color: #e53e3e;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #e53e3e;
        }
        
        .error-message ul {
            margin-top: 10px;
            margin-left: 20px;
        }
        
        /* Booking Summary */
        .booking-summary {
            background-color: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 90px;
        }
        
        .summary-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: #00256e;
            text-align: center;
        }
        
        .hotel-info {
            margin-bottom: 20px;
        }
        
        .hotel-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #00256e;
        }
        
        .hotel-location {
            color: #666;
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            gap: 5px;
        }
        
        .hotel-location i {
            margin-top: 3px;
        }
        
        .room-info {
            padding: 15px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .room-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .room-dates,
        .room-guests {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .price-summary {
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
            font-size: 18px;
        }
        
        .security-note {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-top: 20px;
        }
        
        .security-note i {
            color: #00256e;
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
            
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .booking-summary {
                position: static;
                margin-bottom: 30px;
                order: -1;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
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

    <!-- Booking Header -->
    <section class="booking-header">
        <div class="container">
            <h1 class="booking-title">Complete Your Booking</h1>
            <p class="booking-subtitle">Enter your details to confirm your reservation</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container main-content">
        <!-- Booking Form -->
        <div class="booking-form">
            <h2 class="section-title">Guest Information</h2>
            
            <?php if ($formSubmitted && !empty($errors)): ?>
                <div class="error-message">
                    <strong>Please correct the following errors:</strong>
                    <ul>
                        <?php foreach($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST">
                <div class="form-group">
                    <label for="guest_name">Full Name *</label>
                    <input type="text" id="guest_name" name="guest_name" required value="<?php echo isset($_POST['guest_name']) ? htmlspecialchars($_POST['guest_name']) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="guest_email">Email Address *</label>
                        <input type="email" id="guest_email" name="guest_email" required value="<?php echo isset($_POST['guest_email']) ? htmlspecialchars($_POST['guest_email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="guest_phone">Phone Number *</label>
                        <input type="tel" id="guest_phone" name="guest_phone" required value="<?php echo isset($_POST['guest_phone']) ? htmlspecialchars($_POST['guest_phone']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="adults">Adults *</label>
                        <select id="adults" name="adults" required>
                            <?php for($i = 1; $i <= $room['capacity']; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo (isset($_POST['adults']) && $_POST['adults'] == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> Adult<?php echo $i != 1 ? 's' : ''; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="children">Children (Ages 0-17)</label>
                        <select id="children" name="children">
                            <?php for($i = 0; $i <= $room['capacity']; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo (isset($_POST['children']) && $_POST['children'] == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> Child<?php echo $i != 1 ? 'ren' : ''; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="special_requests">Special Requests (Optional)</label>
                    <textarea id="special_requests" name="special_requests"><?php echo isset($_POST['special_requests']) ? htmlspecialchars($_POST['special_requests']) : ''; ?></textarea>
                </div>
                
                <h2 class="section-title">Payment Information</h2>
                <div class="form-group">
                    <label for="card_number">Card Number *</label>
                    <input type="text" id="card_number" name="card_number" required placeholder="1234 5678 9012 3456">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry_date">Expiry Date *</label>
                        <input type="text" id="expiry_date" name="expiry_date" required placeholder="MM/YY">
                    </div>
                    
                    <div class="form-group">
                        <label for="cvv">Security Code (CVV) *</label>
                        <input type="text" id="cvv" name="cvv" required placeholder="123">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="card_name">Name on Card *</label>
                    <input type="text" id="card_name" name="card_name" required>
                </div>
                
                <div class="form-note">
                    <p><strong>Note:</strong> You will not be charged until check-in. We only verify your card's validity to secure your booking.</p>
                </div>
                
                <button type="submit" class="btn btn-block" style="margin-top: 30px;">Complete Booking</button>
            </form>
        </div>
        
        <!-- Booking Summary -->
        <div class="booking-summary">
            <h3 class="summary-title">Booking Summary</h3>
            
            <div class="hotel-info">
                <div class="hotel-name"><?php echo htmlspecialchars($room['hotel_name']); ?></div>
                <div class="hotel-location">
                    <span>📍</span>
                    <span><?php echo htmlspecialchars($room['address']); ?></span>
                </div>
            </div>
            
            <div class="room-info">
                <div class="room-name"><?php echo htmlspecialchars($room['name']); ?></div>
                <div class="room-dates">
                    <span>Check-in:</span>
                    <span><?php echo $checkInFormatted; ?></span>
                </div>
                <div class="room-dates">
                    <span>Check-out:</span>
                    <span><?php echo $checkOutFormatted; ?></span>
                </div>
                <div class="room-guests">
                    <span>Maximum Occupancy:</span>
                    <span><?php echo $room['capacity']; ?> guests</span>
                </div>
            </div>
            
            <div class="price-summary">
                <div class="price-row">
                    <span>Room Rate:</span>
                    <span>$<?php echo number_format($room['price_per_night'], 2); ?> / night</span>
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
                    <span>$<?php echo number_format($totalPrice, 2); ?></span>
                </div>
            </div>
            
            <div class="security-note">
                <p><i>🔒</i> Secure booking process. Your personal and payment information is encrypted.</p>
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
        // Card number formatting
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 16) {
                value = value.substr(0, 16);
            }
            
            // Add spaces every 4 digits
            let formattedValue = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            
            e.target.value = formattedValue;
        });
        
        // Expiry date formatting (MM/YY)
        document.getElementById('expiry_date').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 4) {
                value = value.substr(0, 4);
            }
            
            // Format as MM/YY
            if (value.length > 2) {
                value = value.substr(0, 2) + '/' + value.substr(2);
            }
            
            e.target.value = value;
        });
        
        // CVV formatting (limit to 3-4 digits)
        document.getElementById('cvv').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 4) {
                value = value.substr(0, 4);
            }
            e.target.value = value;
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
            const expiryDate = document.getElementById('expiry_date').value;
            const cvv = document.getElementById('cvv').value;
            const cardName = document.getElementById('card_name').value;
            
            let valid = true;
            let errorMessage = '';
            
            if (cardNumber.length < 13 || cardNumber.length > 16) {
                valid = false;
                errorMessage += 'Card number must be between 13 and 16 digits.\n';
            }
            
            if (!expiryDate.match(/^\d{2}\/\d{2}$/)) {
                valid = false;
                errorMessage += 'Expiry date must be in MM/YY format.\n';
            } else {
                const [month, year] = expiryDate.split('/');
                const now = new Date();
                const currentYear = now.getFullYear() % 100;
                const currentMonth = now.getMonth() + 1;
                
                if (parseInt(month) < 1 || parseInt(month) > 12) {
                    valid = false;
                    errorMessage += 'Invalid month in expiry date.\n';
                }
                
                if (parseInt(year) < currentYear || 
                    (parseInt(year) === currentYear && parseInt(month) < currentMonth)) {
                    valid = false;
                    errorMessage += 'Card has expired.\n';
                }
            }
            
            if (cvv.length < 3) {
                valid = false;
                errorMessage += 'CVV must be at least 3 digits.\n';
            }
            
            if (cardName.trim() === '') {
                valid = false;
                errorMessage += 'Name on card is required.\n';
            }
            
            if (!valid) {
                e.preventDefault();
                alert('Please fix the following errors:\n\n' + errorMessage);
            }
        });
        
        // Track form changes for guest count validation
        const adultsSelect = document.getElementById('adults');
        const childrenSelect = document.getElementById('children');
        
        function validateGuestCount() {
            const totalGuests = parseInt(adultsSelect.value) + parseInt(childrenSelect.value);
            const maxCapacity = <?php echo $room['capacity']; ?>;
            
            if (totalGuests > maxCapacity) {
                alert(`This room can only accommodate ${maxCapacity} guests in total. Please adjust your selection.`);
                // Reset to valid values
                if (parseInt(adultsSelect.value) > 1) {
                    adultsSelect.value = Math.min(parseInt(adultsSelect.value), maxCapacity);
                    childrenSelect.value = 0;
                } else {
                    childrenSelect.value = maxCapacity - 1;
                }
            }
        }
        
        adultsSelect.addEventListener('change', validateGuestCount);
        childrenSelect.addEventListener('change', validateGuestCount);
    </script>
</body>
</html>

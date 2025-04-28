<?php
require_once 'db.php';

// Get featured hotels
$stmt = $conn->prepare("SELECT * FROM hotels ORDER BY rating DESC LIMIT 3");
$stmt->execute();
$featuredHotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top-rated hotels
$stmt = $conn->prepare("SELECT h.*, COUNT(r.review_id) as review_count, AVG(r.rating) as avg_rating 
                        FROM hotels h 
                        LEFT JOIN reviews r ON h.hotel_id = r.hotel_id 
                        GROUP BY h.hotel_id 
                        ORDER BY avg_rating DESC LIMIT 3");
$stmt->execute();
$topRatedHotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all locations for search dropdown
$stmt = $conn->prepare("SELECT DISTINCT location FROM hotels ORDER BY location");
$stmt->execute();
$locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hilton Hotels | Find Your Perfect Stay</title>
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
        
        /* Hero Section */
        .hero {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://cf.bstatic.com/xdata/images/hotel/max1024x768/327338049.jpg?k=32b41fef613c78220592bebb730635581e1e4e4c5fb7e19124d0fd0ae4e03b5c&o=&hp=1');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .hero p {
            font-size: 20px;
            max-width: 700px;
            margin: 0 auto 40px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        }
        
        /* Search Form */
        .search-form {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            max-width: 900px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .search-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .search-form select,
        .search-form input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-form button {
            margin-top: 26px;
            height: 48px;
        }
        
        /* Featured Hotels Section */
        .section {
            padding: 80px 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
            font-size: 36px;
            color: #00256e;
        }
        
        .section-title span {
            color: #d4af37;
        }
        
        .hotels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .hotel-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .hotel-card:hover {
            transform: translateY(-10px);
        }
        
        .hotel-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }
        
        .hotel-card-content {
            padding: 20px;
        }
        
        .hotel-card h3 {
            font-size: 22px;
            margin-bottom: 10px;
            color: #00256e;
        }
        
        .hotel-card p {
            margin-bottom: 15px;
            color: #666;
        }
        
        .hotel-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .hotel-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: bold;
            color: #d4af37;
        }
        
        .star-icon {
            font-size: 18px;
        }
        
        .hotel-location {
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Why Choose Us Section */
        .why-us {
            background-color: #f9f9f9;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .feature-card i {
            font-size: 40px;
            color: #d4af37;
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            margin-bottom: 15px;
            color: #00256e;
        }
        
        /* Top Rated Section */
        .top-rated {
            background-color: white;
        }
        
        /* Newsletter Section */
        .newsletter {
            background-color: #00256e;
            color: white;
            text-align: center;
            padding: 60px 0;
        }
        
        .newsletter h2 {
            margin-bottom: 20px;
            font-size: 36px;
        }
        
        .newsletter p {
            max-width: 600px;
            margin: 0 auto 30px;
            font-size: 18px;
        }
        
        .newsletter-form {
            display: flex;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .newsletter-form input {
            flex-grow: 1;
            padding: 12px;
            border: none;
            border-radius: 4px 0 0 4px;
        }
        
        .newsletter-form button {
            border-radius: 0 4px 4px 0;
            background-color: #d4af37;
        }
        
        .newsletter-form button:hover {
            background-color: #c19b2e;
        }
        
        /* Footer */
        footer {
            background-color: #222;
            color: white;
            padding: 60px 0 20px;
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
            
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .hero h1 {
                font-size: 36px;
            }
            
            .hero p {
                font-size: 18px;
            }
            
            .section-title {
                font-size: 28px;
            }
            
            .newsletter h2 {
                font-size: 28px;
            }
            
            .newsletter-form {
                flex-direction: column;
                gap: 10px;
            }
            
            .newsletter-form input,
            .newsletter-form button {
                width: 100%;
                border-radius: 4px;
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Experience Luxury & Comfort</h1>
            <p>From city centers to beachfront resorts, find your perfect Hilton stay</p>
            
            <!-- Search Form -->
            <form class="search-form" action="hotels.php" method="GET">
                <div class="form-group">
                    <label for="location">Destination</label>
                    <select id="location" name="location" required>
                        <option value="">Select destination</option>
                        <?php foreach($locations as $location): ?>
                            <option value="<?php echo htmlspecialchars($location); ?>"><?php echo htmlspecialchars($location); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="check_in">Check In</label>
                    <input type="date" id="check_in" name="check_in" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="check_out">Check Out</label>
                    <input type="date" id="check_out" name="check_out" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                
                <button type="submit" class="btn">Search</button>
            </form>
        </div>
    </section>

    <!-- Featured Hotels Section -->
    <section class="section featured">
        <div class="container">
            <h2 class="section-title">Featured <span>Hotels</span></h2>
            
            <div class="hotels-grid">
                <?php foreach($featuredHotels as $hotel): ?>
                <div class="hotel-card">
                    <img src="<?php echo htmlspecialchars($hotel['thumbnail']); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                    <div class="hotel-card-content">
                        <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($hotel['description'], 0, 100) . '...'); ?></p>
                        <div class="hotel-card-footer">
                            <div class="hotel-rating">
                                <span class="star-icon">★</span>
                                <span><?php echo htmlspecialchars($hotel['rating']); ?>/5</span>
                            </div>
                            <div class="hotel-location">
                                <span>📍</span>
                                <span><?php echo htmlspecialchars($hotel['location']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="section why-us">
        <div class="container">
            <h2 class="section-title">Why Choose <span>Hilton</span></h2>
            
            <div class="features-grid">
                <div class="feature-card">
                    <i>🏆</i>
                    <h3>Best Price Guarantee</h3>
                    <p>Find a lower price elsewhere? We'll match it and give you an additional 25% discount.</p>
                </div>
                
                <div class="feature-card">
                    <i>🎁</i>
                    <h3>Hilton Honors Rewards</h3>
                    <p>Earn points for every stay and redeem for free nights, experiences, and more.</p>
                </div>
                
                <div class="feature-card">
                    <i>🔒</i>
                    <h3>Free Cancellation</h3>
                    <p>Plans change. That's why most of our rooms offer free cancellation.</p>
                </div>
                
                <div class="feature-card">
                    <i>👍</i>
                    <h3>Exceptional Service</h3>
                    <p>Our staff is dedicated to making your stay as comfortable as possible.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Top Rated Section -->
    <section class="section top-rated">
        <div class="container">
            <h2 class="section-title">Top Rated <span>Stays</span></h2>
            
            <div class="hotels-grid">
                <?php foreach($topRatedHotels as $hotel): ?>
                <div class="hotel-card">
                    <img src="<?php echo htmlspecialchars($hotel['thumbnail']); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                    <div class="hotel-card-content">
                        <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($hotel['description'], 0, 100) . '...'); ?></p>
                        <div class="hotel-card-footer">
                            <div class="hotel-rating">
                                <span class="star-icon">★</span>
                                <span><?php echo isset($hotel['avg_rating']) ? number_format($hotel['avg_rating'], 1) : $hotel['rating']; ?>/5</span>
                            </div>
                            <div class="hotel-location">
                                <span>📍</span>
                                <span><?php echo htmlspecialchars($hotel['location']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter">
        <div class="container">
            <h2>Subscribe to Our Newsletter</h2>
            <p>Stay updated with our latest offers, promotions, and travel inspirations.</p>
            
            <form class="newsletter-form">
                <input type="email" placeholder="Enter your email address" required>
                <button type="submit" class="btn">Subscribe</button>
            </form>
        </div>
    </section>

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

        // Form validation
        document.querySelector('.search-form').addEventListener('submit', function(e) {
            const location = document.getElementById('location').value;
            const checkIn = document.getElementById('check_in').value;
            const checkOut = document.getElementById('check_out').value;
            
            if (!location || !checkIn || !checkOut) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }
            
            const checkInDate = new Date(checkIn);
            const checkOutDate = new Date(checkOut);
            
            if (checkInDate >= checkOutDate) {
                e.preventDefault();
                alert('Check-out date must be after check-in date');
                return;
            }
        });
    </script>
</body>
</html>

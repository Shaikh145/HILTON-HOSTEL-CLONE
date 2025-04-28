<?php
require_once 'db.php';

// Get search parameters
$location = isset($_GET['location']) ? $_GET['location'] : '';
$checkIn = isset($_GET['check_in']) ? $_GET['check_in'] : '';
$checkOut = isset($_GET['check_out']) ? $_GET['check_out'] : '';

// Get filter parameters
$minPrice = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 10000;
$rating = isset($_GET['rating']) ? (float)$_GET['rating'] : 0;
$amenityIds = isset($_GET['amenities']) ? $_GET['amenities'] : [];

// Get sort parameter
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'price_low';

// Base query for hotels
$query = "SELECT DISTINCT h.*, MIN(rt.price_per_night) as min_price, MAX(rt.price_per_night) as max_price 
          FROM hotels h
          JOIN room_types rt ON h.hotel_id = rt.hotel_id";

// Add amenity filter if selected
if (!empty($amenityIds)) {
    $query .= " JOIN hotel_amenities ha ON h.hotel_id = ha.hotel_id 
                WHERE ha.amenity_id IN (" . implode(',', array_map('intval', $amenityIds)) . ")";
} else {
    $query .= " WHERE 1=1";
}

// Add location filter if specified
if (!empty($location)) {
    $query .= " AND h.location = :location";
}

// Add rating filter if specified
if ($rating > 0) {
    $query .= " AND h.rating >= :rating";
}

// Add price range filter
$query .= " AND rt.price_per_night BETWEEN :min_price AND :max_price";

// Group by hotel
$query .= " GROUP BY h.hotel_id";

// Add sorting
switch ($sortBy) {
    case 'price_high':
        $query .= " ORDER BY min_price DESC";
        break;
    case 'rating':
        $query .= " ORDER BY h.rating DESC";
        break;
    case 'price_low':
    default:
        $query .= " ORDER BY min_price ASC";
        break;
}

// Prepare and execute query
$stmt = $conn->prepare($query);

if (!empty($location)) {
    $stmt->bindParam(':location', $location);
}

if ($rating > 0) {
    $stmt->bindParam(':rating', $rating);
}

$stmt->bindParam(':min_price', $minPrice);
$stmt->bindParam(':max_price', $maxPrice);

$stmt->execute();
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all amenities for filter
$amenityStmt = $conn->prepare("SELECT * FROM amenities ORDER BY name");
$amenityStmt->execute();
$amenities = $amenityStmt->fetchAll(PDO::FETCH_ASSOC);

// Get price range for slider
$priceRangeStmt = $conn->prepare("SELECT MIN(price_per_night) as min, MAX(price_per_night) as max FROM room_types");
$priceRangeStmt->execute();
$priceRange = $priceRangeStmt->fetch(PDO::FETCH_ASSOC);

// Format dates for display
$checkInFormatted = !empty($checkIn) ? date('M d, Y', strtotime($checkIn)) : '';
$checkOutFormatted = !empty($checkOut) ? date('M d, Y', strtotime($checkOut)) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hilton Hotels | Available Hotels</title>
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
        
        /* Search Summary */
        .search-summary {
            background-color: #00256e;
            color: white;
            padding: 20px 0;
        }
        
        .summary-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .search-info p {
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .search-info h2 {
            font-size: 24px;
        }
        
        .modify-search {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .modify-search:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
        
        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
            padding: 40px 0;
        }
        
        /* Filters Section */
        .filters {
            background-color: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            align-self: start;
            position: sticky;
            top: 80px;
        }
        
        .filters h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #00256e;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .filter-group {
            margin-bottom: 25px;
        }
        
        .filter-group h4 {
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .price-range {
            margin-bottom: 10px;
        }
        
        .price-inputs {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .price-inputs input {
            width: 45%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .range-slider {
            width: 100%;
            margin-top: 10px;
        }
        
        .amenity-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .amenity-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .amenity-item input {
            margin-right: 10px;
        }
        
        .star-filter {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .star-filter input {
            margin-right: 10px;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .filter-buttons button {
            padding: 10px;
            flex: 1;
            font-weight: normal;
            font-size: 14px;
        }
        
        /* Hotels List */
        .hotels-list {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .sort-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .sort-options p {
            font-weight: bold;
        }
        
        .sort-options select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 200px;
        }
        
        .hotel-item {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: grid;
            grid-template-columns: 300px 1fr;
        }
        
        .hotel-image {
            height: 100%;
            overflow: hidden;
        }
        
        .hotel-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .hotel-image img:hover {
            transform: scale(1.05);
        }
        
        .hotel-details {
            padding: 25px;
            display: flex;
            flex-direction: column;
        }
        
        .hotel-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .hotel-name h3 {
            font-size: 22px;
            color: #00256e;
            margin-bottom: 5px;
        }
        
        .hotel-location {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 14px;
        }
        
        .hotel-rating {
            text-align: right;
        }
        
        .hotel-rating .stars {
            color: #d4af37;
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .hotel-description {
            margin-bottom: 15px;
            color: #666;
        }
        
        .hotel-amenities {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .hotel-amenity {
            background-color: #f5f5f5;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            color: #666;
        }
        
        .hotel-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .hotel-price {
            font-size: 14px;
            color: #666;
        }
        
        .hotel-price span {
            font-size: 24px;
            font-weight: bold;
            color: #00256e;
        }
        
        .no-hotels {
            text-align: center;
            padding: 50px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .no-hotels h3 {
            color: #00256e;
            margin-bottom: 15px;
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
            
            .summary-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .filters {
                position: static;
            }
            
            .hotel-item {
                grid-template-columns: 1fr;
            }
            
            .hotel-image {
                height: 200px;
            }
            
            .hotel-footer {
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

    <!-- Search Summary -->
    <section class="search-summary">
        <div class="container summary-content">
            <div class="search-info">
                <p>Your search results for</p>
                <h2>
                    <?php echo !empty($location) ? htmlspecialchars($location) : 'All Locations'; ?>
                    <?php if($checkInFormatted && $checkOutFormatted): ?>
                        <span>(<?php echo $checkInFormatted; ?> - <?php echo $checkOutFormatted; ?>)</span>
                    <?php endif; ?>
                </h2>
            </div>
            <a href="index.php" class="modify-search">Modify Search</a>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container main-content">
        <!-- Filters Section -->
        <aside class="filters">
            <h3>Filter Results</h3>
            <form action="hotels.php" method="GET" id="filter-form">
                <!-- Keep search parameters -->
                <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
                <input type="hidden" name="check_in" value="<?php echo htmlspecialchars($checkIn); ?>">
                <input type="hidden" name="check_out" value="<?php echo htmlspecialchars($checkOut); ?>">
                
                <!-- Price Range Filter -->
                <div class="filter-group">
                    <h4>Price Range</h4>
                    <div class="price-range">
                        <input type="range" id="price-slider" class="range-slider" min="<?php echo floor($priceRange['min']); ?>" max="<?php echo ceil($priceRange['max']); ?>" step="10">
                        <div class="price-inputs">
                            <input type="number" id="min-price" name="min_price" value="<?php echo $minPrice; ?>" min="<?php echo floor($priceRange['min']); ?>" max="<?php echo ceil($priceRange['max']); ?>">
                            <input type="number" id="max-price" name="max_price" value="<?php echo $maxPrice; ?>" min="<?php echo floor($priceRange['min']); ?>" max="<?php echo ceil($priceRange['max']); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Rating Filter -->
                <div class="filter-group">
                    <h4>Star Rating</h4>
                    <div class="star-filter">
                        <input type="radio" id="rating-any" name="rating" value="0" <?php echo $rating == 0 ? 'checked' : ''; ?>>
                        <label for="rating-any">Any</label>
                    </div>
                    <div class="star-filter">
                        <input type="radio" id="rating-3" name="rating" value="3" <?php echo $rating == 3 ? 'checked' : ''; ?>>
                        <label for="rating-3">3+ Stars</label>
                    </div>
                    <div class="star-filter">
                        <input type="radio" id="rating-4" name="rating" value="4" <?php echo $rating == 4 ? 'checked' : ''; ?>>
                        <label for="rating-4">4+ Stars</label>
                    </div>
                    <div class="star-filter">
                        <input type="radio" id="rating-4.5" name="rating" value="4.5" <?php echo $rating == 4.5 ? 'checked' : ''; ?>>
                        <label for="rating-4.5">4.5+ Stars</label>
                    </div>
                </div>
                
                <!-- Amenities Filter -->
                <div class="filter-group">
                    <h4>Amenities</h4>
                    <div class="amenity-list">
                        <?php foreach($amenities as $amenity): ?>
                        <div class="amenity-item">
                            <input type="checkbox" id="amenity-<?php echo $amenity['amenity_id']; ?>" name="amenities[]" value="<?php echo $amenity['amenity_id']; ?>" <?php echo in_array($amenity['amenity_id'], $amenityIds) ? 'checked' : ''; ?>>
                            <label for="amenity-<?php echo $amenity['amenity_id']; ?>"><?php echo htmlspecialchars($amenity['name']); ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Sort By (hidden, controlled by the visible sort dropdown) -->
                <input type="hidden" id="sort-by-hidden" name="sort_by" value="<?php echo htmlspecialchars($sortBy); ?>">
                
                <!-- Filter Buttons -->
                <div class="filter-buttons">
                    <button type="submit" class="btn">Apply Filters</button>
                    <button type="button" id="reset-filters" class="btn btn-outline">Reset</button>
                </div>
            </form>
        </aside>
        
        <!-- Hotels List -->
        <div class="hotels-results">
            <!-- Sort Options -->
            <div class="sort-options">
                <p>Found <?php echo count($hotels); ?> hotel<?php echo count($hotels) != 1 ? 's' : ''; ?></p>
                <div>
                    <label for="sort-by">Sort by:</label>
                    <select id="sort-by">
                        <option value="price_low" <?php echo $sortBy == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sortBy == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="rating" <?php echo $sortBy == 'rating' ? 'selected' : ''; ?>>Top Rated</option>
                    </select>
                </div>
            </div>
            
            <!-- Hotels List -->
            <div class="hotels-list">
                <?php if(count($hotels) > 0): ?>
                    <?php foreach($hotels as $hotel): ?>
                        <!-- Get hotel amenities -->
                        <?php
                        $amenityStmt = $conn->prepare("SELECT a.name FROM amenities a 
                                                      JOIN hotel_amenities ha ON a.amenity_id = ha.amenity_id 
                                                      WHERE ha.hotel_id = :hotel_id LIMIT 5");
                        $amenityStmt->bindParam(':hotel_id', $hotel['hotel_id']);
                        $amenityStmt->execute();
                        $hotelAmenities = $amenityStmt->fetchAll(PDO::FETCH_COLUMN);
                        ?>
                        
                        <div class="hotel-item">
                            <div class="hotel-image">
                                <a href="room_details.php?hotel_id=<?php echo $hotel['hotel_id']; ?>&check_in=<?php echo urlencode($checkIn); ?>&check_out=<?php echo urlencode($checkOut); ?>">
                                    <img src="<?php echo htmlspecialchars($hotel['thumbnail']); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                                </a>
                            </div>
                            <div class="hotel-details">
                                <div class="hotel-header">
                                    <div class="hotel-name">
                                        <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                                        <div class="hotel-location">
                                            <span>📍</span>
                                            <span><?php echo htmlspecialchars($hotel['address']); ?></span>
                                        </div>
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
                                        <div><?php echo number_format($hotel['rating'], 1); ?>/5</div>
                                    </div>
                                </div>
                                
                                <div class="hotel-description">
                                    <?php echo htmlspecialchars(substr($hotel['description'], 0, 150) . '...'); ?>
                                </div>
                                
                                <div class="hotel-amenities">
                                    <?php foreach($hotelAmenities as $amenity): ?>
                                        <div class="hotel-amenity"><?php echo htmlspecialchars($amenity); ?></div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="hotel-footer">
                                    <div class="hotel-price">
                                        <div>Rooms starting at</div>
                                        <div><span>$<?php echo number_format($hotel['min_price'], 2); ?></span> / night</div>
                                    </div>
                                    <a href="room_details.php?hotel_id=<?php echo $hotel['hotel_id']; ?>&check_in=<?php echo urlencode($checkIn); ?>&check_out=<?php echo urlencode($checkOut); ?>" class="btn">View Rooms</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-hotels">
                        <h3>No hotels found</h3>
                        <p>Try modifying your search criteria or check back later for new listings.</p>
                        <a href="index.php" class="btn" style="margin-top: 20px;">Back to Search</a>
                    </div>
                <?php endif; ?>
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
        // Price range slider functionality
        const minPrice = document.getElementById('min-price');
        const maxPrice = document.getElementById('max-price');
        const priceSlider = document.getElementById('price-slider');
        
        // Update min/max values when slider changes
        priceSlider.addEventListener('input', function() {
            const value = this.value;
            minPrice.value = 0;
            maxPrice.value = value;
        });
        
        // Update slider when min/max inputs change
        maxPrice.addEventListener('change', function() {
            if (parseInt(this.value) < parseInt(minPrice.value)) {
                this.value = minPrice.value;
            }
            priceSlider.value = this.value;
        });
        
        // Sort functionality
        const sortBy = document.getElementById('sort-by');
        const sortByHidden = document.getElementById('sort-by-hidden');
        
        sortBy.addEventListener('change', function() {
            sortByHidden.value = this.value;
            document.getElementById('filter-form').submit();
        });
        
        // Reset filters
        document.getElementById('reset-filters').addEventListener('click', function() {
            const form = document.getElementById('filter-form');
            
            // Reset price range
            minPrice.value = minPrice.min;
            maxPrice.value = maxPrice.max;
            priceSlider.value = maxPrice.max;
            
            // Reset rating
            document.getElementById('rating-any').checked = true;
            
            // Reset amenities
            const amenityCheckboxes = form.querySelectorAll('input[name="amenities[]"]');
            amenityCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Keep sort order
            // Keep location and dates
            
            // Submit form
            form.submit();
        });
    </script>
</body>
</html>

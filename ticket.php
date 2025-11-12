<?php
/*this file sheet is for php ticket booking
    hadif hashim*/

require_once 'config.php';

$success = '';
$error = '';

// Handle ticket booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_ticket'])) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    
    $user_id = getUserId();
    $ticket_type = sanitizeInput($_POST['ticket_type'] ?? 'bus');
    $from_location = sanitizeInput($_POST['from']);
    $to_location = sanitizeInput($_POST['to']);
    $onward_date = sanitizeInput($_POST['date']);
    $return_date = !empty($_POST['return']) ? sanitizeInput($_POST['return']) : NULL;
    $passengers = (int)($_POST['passengers'] ?? 1);
    $price = 50.00 * $passengers; // Base price calculation
    $booking_reference = 'TKT' . time() . rand(1000, 9999);
    
    if (empty($from_location) || empty($to_location) || empty($onward_date)) {
        $error = "Please fill in all required fields!";
    } else {
        $sql = "INSERT INTO tickets (user_id, ticket_type, from_location, to_location, onward_date, return_date, passengers, price, booking_reference) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssssiis", $user_id, $ticket_type, $from_location, $to_location, $onward_date, $return_date, $passengers, $price, $booking_reference);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Ticket booked successfully! Your booking reference is: " . $booking_reference;
            
            // Send email notification (if PHPMailer is configured)
            // sendTicketConfirmation($user['email'], $booking_reference);
        } else {
            $error = "Error booking ticket. Please try again!";
        }
    }
}

// Get user's bookings
$user_bookings = [];
if (isLoggedIn()) {
    $bookings_sql = "SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
    $stmt = mysqli_prepare($conn, $bookings_sql);
    mysqli_stmt_bind_param($stmt, "i", getUserId());
    mysqli_stmt_execute($stmt);
    $bookings_result = mysqli_stmt_get_result($stmt);
    while ($booking = mysqli_fetch_assoc($bookings_result)) {
        $user_bookings[] = $booking;
    }
}
?>
<!DOCType html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>UITMCJ Online Ticket Service</title>
        <link rel="stylesheet" href="ticket.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body>
    <header>
        <div class="top-bar">
            <div class="top-bar-content">
                <div class="top-bar-text">UITMCJ Local Tickets Service</div>
                <div class="top-bar-links">
                    <a href="#offers">Offers</a>
                    <a href="#help">Help</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="profile.php">My Account</a>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php">User Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="main-header">
            <div class="logo">
                <h1 style="color: var(--primary-light); font-size: 28px; font-weight: bold;">Thrift</h1>
            </div>
            <ul class="nav-links">
                <li><a href="#bus"><i class="fas fa-bus"></i> Bus Tickets</a></li>
                <li><a href="#train"><i class="fas fa-train"></i> Train Tickets</a></li>
                <li><a href="#grab"><i class="fas fa-car"></i> Grabs</a></li>
                <li><a href="#offers"><i class="fas fa-percentage"></i> Offers</a></li>
                <li><a href="main.php"><i class="fas fa-home"></i> Home</a></li>
            </ul>
        </div>
    </header>

    <section class="hero">
        <h1>Book Bus, Train and Grabs</h1>
        <p>24/7 online site helping u even on last minute plans</p>
    </section>

    <section class="search-container">
        <?php if ($success): ?>
            <div style="background: #51cf66; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #ff6b6b; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form class="search-form" method="POST" action="">
            <div class="form-group">
                <label for="ticket_type">TICKET TYPE</label>
                <select id="ticket_type" name="ticket_type" required>
                    <option value="bus">Bus</option>
                    <option value="train">Train</option>
                    <option value="grab">Grab</option>
                </select>
            </div>
            <div class="form-group">
                <label for="from">FROM</label>
                <input type="text" id="from" name="from" placeholder="Leaving from" required>
            </div>
            <div class="form-group">
                <label for="to">TO</label>
                <input type="text" id="to" name="to" placeholder="Going to" required>
            </div>
            <div class="form-group">
                <label for="date">ONWARD DATE</label>
                <input type="date" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label for="return">RETURN DATE (Optional)</label>
                <input type="date" id="return" name="return">
            </div>
            <div class="form-group">
                <label for="passengers">PASSENGERS</label>
                <input type="number" id="passengers" name="passengers" value="1" min="1" max="10">
            </div>
            <button type="submit" name="book_ticket" class="search-btn">Book Ticket</button>
        </form>
    </section>

    <?php if (!empty($user_bookings)): ?>
    <section class="offers-section" style="margin-top: 50px;">
        <h2 class="section-title">Your Recent Bookings</h2>
        <div style="background: white; padding: 20px; border-radius: 8px;">
            <?php foreach ($user_bookings as $booking): ?>
            <div style="border-bottom: 1px solid #ddd; padding: 15px 0; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong><?php echo strtoupper($booking['ticket_type']); ?></strong> - 
                    <?php echo htmlspecialchars($booking['from_location']); ?> to <?php echo htmlspecialchars($booking['to_location']); ?>
                    <br>
                    <small>Date: <?php echo date('F d, Y', strtotime($booking['onward_date'])); ?> | 
                    Passengers: <?php echo $booking['passengers']; ?> | 
                    Reference: <?php echo $booking['booking_reference']; ?></small>
                </div>
                <div>
                    <strong style="color: var(--accent);">RM <?php echo number_format($booking['price'], 2); ?></strong>
                    <br>
                    <small style="color: <?php echo $booking['status'] == 'confirmed' ? 'green' : 'orange'; ?>">
                        <?php echo ucfirst($booking['status']); ?>
                    </small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="offers-section" id="offers">
        <h2 class="section-title">Trending Offers</h2>
        <div class="offers-grid">
            <div class="offer-card">
                <div class="offer-img">
                    <i class="fas fa-tag"></i>
                </div>
                <div class="offer-content">
                    <h3>FLAT 15% OFF</h3>
                    <p>using student acc to get 15% off</p>
                </div>
            </div>
            <div class="offer-card">
                <div class="offer-img">
                    <i class="fas fa-gift"></i>
                </div>
                <div class="offer-content">
                    <h3>CASHBACK UPTO 300myr</h3>
                    <p>we helping student with difficulties to went home. T&C apply.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="app-section">
        <div class="app-container">
            <div class="app-content">
                <h2>browse available ticket with us on mobile anytime</h2>
                <p>Download the thrift app to get up to date offer and didn't miss chance on offer and discount.</p>
                <div class="app-buttons">
                    <a href="#" class="app-btn">
                        <i class="fab fa-google-play"></i>
                        <div>
                            <small>GET IT ON</small>
                            <div>Google Play</div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="app-image">
                <i class="fas fa-mobile-alt" style="font-size: 250px; color: var(--accent);"></i>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>thrift</h3>
                <p>Creating beautiful websites for businesses of all sizes since 2025.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3>Info</h3>
                <ul>
                    <li><a href="#">Traveller Info</a></li>
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">User Reviews</a></li>
                    <li><a href="#">Bus Operator Registration</a></li>
                    <li><a href="#">Insurance Partner</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Resources</h3>
                <ul>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Community</a></li>
                    <li><a href="#">Webinars</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Company</h3>
                <ul>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="#">Partners</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; thrift-ing since 2005.</p>
        </div>
    </footer>
    </body>
</html>

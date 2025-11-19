<?php
/*this file sheet is for php main page
    hadif hashim*/

require_once 'config.php';

// Get categories from database
$categories_sql = "SELECT * FROM categories";
$categories_result = mysqli_query($conn, $categories_sql);
?>
<!DOCType html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>MarketPlace 2st | Main</title>
        <link rel="stylesheet" href="main.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    </head>
    <body>
    <header>
        <nav class="navbar">
            <div class="logo">MarketPlace</div>
            <div class="nav-links">
                <a href="main.php">Home</a>
                <a href="item.php">Items</a>
                <a href="ticket.php">Ticket</a>
                <a href="#">Contact</a>
                <a href="#">News</a>
            </div>
            <div class="auth-buttons">
                <?php if (isLoggedIn()): ?>
                    <a href="profile.php" class="btn btn-outline">Profile</a>
                    <a href="logout.php" class="btn btn-primary">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="login.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <section class="MarketContainer">
            <div class="MarketSlide slide-1 active">
                <div class="slide-content">
                    <h1>Your One Stop Shop for Thrifting</h1>
                    <p>Discover the perfect items for your style and budget.</p>
                    <a href="item.php" class="btn btn-primary">Get Started</a>
                </div>
                <div class="slide-bg" style="background-image: url('https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Fimg-s-msn-com.akamaized.net%2Ftenant%2Famp%2Fentityid%2FAA1Qvi8Z.img%3Fw%3D1280%26h%3D720%26m%3D4%26q%3D72&f=1&nofb=1&ipt=ab8277eefe44f06fa527a57e3c4c0eaf04ecfd954fa0fbf1014f541d376090a7');"></div>
            </div>
            <div class="MarketSlide slide-2">
                <div class="slide-content">
                    <h1>Responsive and User-Friendly</h1>
                    <p>Anytime, Anywhere.</p>
                    <a href="item.php" class="btn btn-primary">Learn More</a>
                </div>
                <div class="slide-bg" style="background-image: url('path/to/your/image2.jpg');"></div>
            </div>
            <div class="MarketSlide slide-3">
                <div class="slide-content">
                    <h1>Various Items Available</h1>
                    <p>Tickets, Electronics, Fashion, Food</p>
                    <a href="item.php" class="btn btn-primary">View items</a>
                </div>
                <div class="slide-bg" style="background-image: url('path/to/your/image3.jpg');"></div>
            </div>

            <div class="MarketNav">
                <div class="nav-btn prev-btn">
                    <i class="fas fa-chevron-left"></i>
                </div>
                <div class="nav-btn next-btn">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>

            <div class="MarketIndicators">
                <div class="indicator active" data-slide="0"></div>
                <div class="indicator" data-slide="1"></div>
                <div class="indicator" data-slide="2"></div>
            </div>
        </section>

        <section class="features">
            <div class="section-title">
                <h2>Available Category</h2>
                <p>We provide everything you need for last minute plan.</p>
            </div>
            <div class="features-grid">
                <?php 
                $feature_classes = ['feature-1', 'feature-2', 'feature-3', 'feature-4', 'feature-5', 'feature-6', 'feature-7', 'feature-8'];
                $index = 0;
                while ($category = mysqli_fetch_assoc($categories_result)): 
                    $feature_class = $feature_classes[$index % 8];
                    $index++;
                ?>
                <a href="item.php?category=<?php echo $category['id']; ?>" style="text-decoration: none; color: inherit;">
                    <div class="feature-card <?php echo $feature_class; ?>">
                        <div class="feature-icon">
                            <i class="fas fa-solid <?php echo $category['icon']; ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>thrift</h3>
                <p>Creating beautiful websites for businesses of all sizes since 2025.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3>Product</h3>
                <ul>
                    <li><a href="#">Features</a></li>
                    <li><a href="#">Pricing</a></li>
                    <li><a href="#">Templates</a></li>
                    <li><a href="#">Integrations</a></li>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.MarketSlide');
            const indicators = document.querySelectorAll('.indicator');
            const prevBtn = document.querySelector('.prev-btn');
            const nextBtn = document.querySelector('.next-btn');
            let currentSlide = 0;
            let slideInterval;

            function showSlide(index) {
                slides.forEach(slide => slide.classList.remove('active'));
                indicators.forEach(indicator => indicator.classList.remove('active'));
                
                slides[index].classList.add('active');
                indicators[index].classList.add('active');
                
                currentSlide = index;
            }

            function nextSlide() {
                let next = currentSlide + 1;
                if (next >= slides.length) {
                    next = 0;
                }
                showSlide(next);
            }

            function prevSlide() {
                let prev = currentSlide - 1;
                if (prev < 0) {
                    prev = slides.length - 1;
                }
                showSlide(prev);
            }

            function startSlideShow() {
                slideInterval = setInterval(nextSlide, 5000);
            }

            function stopSlideShow() {
                clearInterval(slideInterval);
            }

            nextBtn.addEventListener('click', function() {
                stopSlideShow();
                nextSlide();
                startSlideShow();
            });

            prevBtn.addEventListener('click', function() {
                stopSlideShow();
                prevSlide();
                startSlideShow();
            });

            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', function() {
                    stopSlideShow();
                    showSlide(index);
                    startSlideShow();
                });
            });

            startSlideShow();
        });
    </script>
    </body>
</html>

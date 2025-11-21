<?php
/*this file sheet is for php main page
    hadif hashim*/

require_once 'config.php';

// Get categories from database
$categories_sql = "SELECT * FROM categories";
$categories_result = mysqli_query($conn, $categories_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketPlace 2st | Main</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<header>
    <nav class="navbar">
        <div class="logo">MarketPlace</div>
        <div class="nav-links">
            <a href="main.php">Home</a>
            <a href="item.php">Buy</a>
            <a href="profile.php">Sell</a>
            <a href="item.php">Items</a>
            <a href="ticket.php">Ticket</a>
        </div>
        <div class="auth-buttons">
            <?php if (isLoggedIn()): ?>
                <a href="profile.php" class="btn btn-outline">Profile</a>
                <a href="logout.php" class="btn btn-primary">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Login/Sign Up</a>
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
            <div class="slide-bg"></div>
        </div>
        <div class="MarketSlide slide-2">
            <div class="slide-content">
                <h1>Responsive and User-Friendly</h1>
                <p>Anytime, Anywhere.</p>
                <a href="item.php" class="btn btn-primary">Learn More</a>
            </div>
            <div class="slide-bg"></div>
        </div>
        <div class="MarketSlide slide-3">
            <div class="slide-content">
                <h1>Various Items Available</h1>
                <p>Tickets, Electronics, Fashion, Food</p>
                <a href="item.php" class="btn btn-primary">View items</a>
            </div>
            <div class="slide-bg"></div>
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
                        <i class="fas fa-solid <?php echo htmlspecialchars($category['icon']); ?>"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </section>
</main>

<footer style="background: #1a1a1a; color: white; margin-top: 50px;">
    <div style="max-width: 800px; margin: 0 auto; text-align: center; padding: 40px 20px;">
        <div>
            <h3 style="font-size: 28px; margin-bottom: 15px; color: #d4af37;">Thrift</h3>
            <p style="color: #b8b8b8; margin-bottom: 30px; line-height: 1.6;">Creating beautiful websites for businesses of all sizes since 2025.</p>
        </div>
        <div style="border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 20px;">
            <p style="color: #b8b8b8; font-size: 0.9rem;">&copy; thrift-ing since 2005.</p>
        </div>
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
            
            slides[index].classList.add('act                      ve');
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
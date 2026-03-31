<?php
require_once "inc/auth.inc.php";

// Handle contact form submission
$contactSuccess = $contactError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name    = trim(htmlspecialchars($_POST['contact_name'] ?? '', ENT_QUOTES, 'UTF-8'));
    $email   = filter_input(INPUT_POST, 'contact_email', FILTER_SANITIZE_EMAIL);
    $message = trim(htmlspecialchars($_POST['contact_message'] ?? '', ENT_QUOTES, 'UTF-8'));

    if (!$name || !$email || !$message) {
        $contactError = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contactError = 'Please enter a valid email address.';
    } else {
        // In production: store in DB or send email
        $contactSuccess = "Thanks, {$name}! We'll be in touch soon.";
    }
}

// Fetch featured products
try {
    $pdo = getDB();
    $stmt = $pdo->query("
        SELECT p.*, c.name AS cat_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.is_active = 1 AND p.featured = 1
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    $featured = $stmt->fetchAll();
} catch (Exception $e) {
    $featured = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pomegranate – Next-Gen Tech</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css"
          integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<?php include "inc/nav.inc.php"; ?>

<!-- ═══════════════════════════════════════════════════════════
     HERO SECTION
═══════════════════════════════════════════════════════════ -->
<section id="hero">
    <canvas id="particles-canvas"></canvas>

    <!-- Background orbs -->
    <div class="orb orb-cyan" style="width:600px;height:600px;top:-20%;left:-10%;"></div>
    <div class="orb orb-purple" style="width:500px;height:500px;bottom:-10%;right:-5%;"></div>

    <div class="container hero-content">
        <div class="hero-badge">
            <span class="pulse-dot"></span>
            New arrivals dropping now
        </div>

        <h1 class="hero-title">
            Discover<br>
            <span class="text-gradient" id="typed-text"></span>
        </h1>

        <p class="hero-sub">
            Premium technology curated for those who demand the best.
            Where design meets performance — welcome to Pomegranate.
        </p>

        <div class="hero-actions">
            <a href="catalog.php" class="btn-primary-glow">
                <i class="bi bi-grid-3x3-gap"></i> Shop Now
            </a>
            <a href="about.php" class="btn-outline-glow">
                <i class="bi bi-play-circle"></i> Our Story
            </a>
        </div>
    </div>

    <!-- Scroll hint -->
    <div class="hero-scroll-hint">
        <span>Scroll</span>
        <i class="bi bi-chevron-down"></i>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     MARQUEE
═══════════════════════════════════════════════════════════ -->
<div class="marquee-section">
    <div class="marquee-track">
        <?php
        $items = [
            ['bi-shield-check',   'Secure Checkout'],
            ['bi-truck',          'Free Shipping over $100'],
            ['bi-arrow-repeat',   '30-Day Returns'],
            ['bi-star-fill',      '4.9/5 Rating'],
            ['bi-headset',        '24/7 Support'],
            ['bi-lightning-fill', 'Fast Delivery'],
            ['bi-gem',            'Premium Quality'],
            ['bi-lock-fill',      '2-Year Warranty'],
        ];
        // Double for seamless loop
        $all = array_merge($items, $items);
        foreach ($all as $item): ?>
        <div class="marquee-item">
            <i class="bi <?= $item[0] ?>"></i>
            <?= $item[1] ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     STATS
═══════════════════════════════════════════════════════════ -->
<section class="stats-section">
    <div class="container">
        <div class="row g-0">
            <div class="col-6 col-md-3">
                <div class="stat-item reveal delay-1">
                    <div class="stat-number" data-count="50000" data-suffix="+">0</div>
                    <div class="stat-label">Happy Customers</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item reveal delay-2">
                    <div class="stat-number" data-count="200" data-suffix="+">0</div>
                    <div class="stat-label">Products</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item reveal delay-3">
                    <div class="stat-number" data-count="4.9" data-suffix="/5">0</div>
                    <div class="stat-label">Avg. Rating</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item reveal delay-4">
                    <div class="stat-number" data-count="99" data-suffix="%">0</div>
                    <div class="stat-label">Satisfaction Rate</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     FEATURED PRODUCTS
═══════════════════════════════════════════════════════════ -->
<section class="py-6" style="padding:5rem 0;">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <div class="section-label"><i class="bi bi-stars"></i> Featured</div>
            <h2 class="section-heading">Our <span class="text-gradient">Top Picks</span></h2>
            <p class="text-white-50 mt-2" style="max-width:520px;margin:auto;">
                Hand-selected by our tech experts. The finest devices in their class.
            </p>
        </div>

        <?php if (!empty($featured)): ?>
        <div class="row g-4">
            <?php foreach ($featured as $i => $p): ?>
            <div class="col-12 col-sm-6 col-lg-4 reveal delay-<?= min($i+1, 5) ?>">
                <div class="glass-card product-card-wrap h-100">
                    <div class="product-img-wrap">
                        <img src="<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>">
                        <?php if ($p['sale_price']): ?>
                            <span class="product-badge badge-sale">Sale</span>
                        <?php else: ?>
                            <span class="product-badge badge-featured">Featured</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-body">
                        <div class="product-category"><?= h($p['cat_name'] ?? 'Tech') ?></div>
                        <div class="product-name"><?= h($p['name']) ?></div>
                        <div class="product-desc"><?= h($p['description']) ?></div>
                        <div class="product-price-row">
                            <span class="price-current">
                                $<?= number_format($p['sale_price'] ?? $p['price'], 2) ?>
                            </span>
                            <?php if ($p['sale_price']): ?>
                            <span class="price-original">$<?= number_format($p['price'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="product.php?id=<?= $p['id'] ?>" class="btn-add-cart">
                            <i class="bi bi-bag-plus"></i> View Product
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- Fallback placeholder cards when DB is not connected -->
        <div class="row g-4">
            <?php
            $placeholders = [
                ['NeoPulse X1',      'Flagship smartphone with 200MP camera and 5G.', '1,099.00', '1,299.00', 'Smartphones', 'assets/phone.jpg'],
                ['UltraBook Pro 16', '16" OLED laptop — the ultimate workstation.',   '2,499.00', null,       'Laptops',     'assets/cat.jpg'],
                ['ArcWatch Ultra',   'Smartwatch with ECG & 14-day battery life.',    '599.00',   null,       'Wearables',   'assets/cat.jpg'],
                ['NeoPulse A5',      'Mid-range phone with flagship-grade features.',  '599.00',  '699.00',   'Smartphones', 'assets/phone-berries.jpg'],
                ['SoundPods Pro',    'True wireless ANC earbuds — 36h total battery.','249.00',  '299.00',   'Accessories', 'assets/cat.jpg'],
                ['SlimAir 13',       'Ultra-thin 13" laptop at just 890g.',           '999.00',  '1,199.00', 'Laptops',     'assets/phone-blue.jpg'],
            ];
            foreach ($placeholders as $i => $p): ?>
            <div class="col-12 col-sm-6 col-lg-4 reveal delay-<?= min($i+1, 5) ?>">
                <div class="glass-card h-100">
                    <div class="product-img-wrap">
                        <img src="<?= $p[5] ?>" alt="<?= $p[0] ?>">
                        <span class="product-badge <?= $p[3] ? 'badge-sale' : 'badge-featured' ?>">
                            <?= $p[3] ? 'Sale' : 'Featured' ?>
                        </span>
                    </div>
                    <div class="product-body">
                        <div class="product-category"><?= $p[4] ?></div>
                        <div class="product-name"><?= $p[0] ?></div>
                        <div class="product-desc"><?= $p[1] ?></div>
                        <div class="product-price-row">
                            <span class="price-current">$<?= $p[2] ?></span>
                            <?php if ($p[3]): ?>
                            <span class="price-original">$<?= $p[3] ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="catalog.php" class="btn-add-cart">
                            <i class="bi bi-bag-plus"></i> Shop Now
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="text-center mt-5 reveal">
            <a href="catalog.php" class="btn-outline-glow">
                View All Products <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<div class="section-divider"></div>

<!-- ═══════════════════════════════════════════════════════════
     WHY POMEGRANATE
═══════════════════════════════════════════════════════════ -->
<section style="padding:5rem 0;">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <div class="section-label"><i class="bi bi-gem"></i> Why Us</div>
            <h2 class="section-heading">Built for <span class="text-gradient">Tech Enthusiasts</span></h2>
        </div>

        <div class="row g-4">
            <div class="col-12 col-md-6 col-lg-4 reveal delay-1">
                <div class="feature-card">
                    <div class="feature-icon-wrap icon-cyan"><i class="bi bi-cpu"></i></div>
                    <h5>Cutting-Edge Hardware</h5>
                    <p>Only the latest chipsets, displays, and sensors make it to our shelves. No compromises.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4 reveal delay-2">
                <div class="feature-card">
                    <div class="feature-icon-wrap icon-purple"><i class="bi bi-shield-check"></i></div>
                    <h5>Verified Authentic</h5>
                    <p>Every product is sourced directly from manufacturers. Guaranteed genuine, every time.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4 reveal delay-3">
                <div class="feature-card">
                    <div class="feature-icon-wrap icon-pink"><i class="bi bi-lightning-fill"></i></div>
                    <h5>Lightning Fast Shipping</h5>
                    <p>Same-day dispatch on orders before 2 PM. Your gear arrives when you need it.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4 reveal delay-2">
                <div class="feature-card">
                    <div class="feature-icon-wrap icon-green"><i class="bi bi-arrow-repeat"></i></div>
                    <h5>Hassle-Free Returns</h5>
                    <p>30-day no-questions-asked return policy. Your satisfaction is non-negotiable.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4 reveal delay-3">
                <div class="feature-card">
                    <div class="feature-icon-wrap icon-amber"><i class="bi bi-headset"></i></div>
                    <h5>Expert Support</h5>
                    <p>Our tech-savvy team is available 24/7 via chat, email, or phone. Real experts, real help.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4 reveal delay-4">
                <div class="feature-card">
                    <div class="feature-icon-wrap icon-cyan"><i class="bi bi-credit-card-2-front"></i></div>
                    <h5>Secure Payments</h5>
                    <p>256-bit SSL encryption, multiple payment gateways, and zero-fraud guarantee.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="section-divider"></div>

<!-- ═══════════════════════════════════════════════════════════
     CONTACT
═══════════════════════════════════════════════════════════ -->
<section id="contact" class="contact-section" style="padding:5rem 0;">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Left copy -->
            <div class="col-lg-5 reveal-left">
                <div class="section-label"><i class="bi bi-chat-dots"></i> Get In Touch</div>
                <h2 class="section-heading mb-3">Let's <span class="text-gradient">Talk Tech</span></h2>
                <p class="text-white-50" style="line-height:1.8;">
                    Have a question, partnership idea, or just want to geek out about the latest gadgets?
                    Our team replies within 24 hours.
                </p>

                <div class="d-flex flex-column gap-3 mt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="feature-icon-wrap icon-cyan mb-0"><i class="bi bi-envelope"></i></div>
                        <div>
                            <div class="small text-white-50">Email us</div>
                            <div class="fw-semibold">hello@pomegranate.com</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="feature-icon-wrap icon-purple mb-0"><i class="bi bi-geo-alt"></i></div>
                        <div>
                            <div class="small text-white-50">Visit us</div>
                            <div class="fw-semibold">80 Stamford Road, Singapore 178902</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right form -->
            <div class="col-lg-6 offset-lg-1 reveal-right">
                <div class="contact-form-card">
                    <h4 class="fw-bold mb-4">Send a message</h4>

                    <?php if ($contactSuccess): ?>
                    <div class="alert-success-dark p-3 mb-4 auto-dismiss">
                        <i class="bi bi-check-circle me-2"></i><?= h($contactSuccess) ?>
                    </div>
                    <?php elseif ($contactError): ?>
                    <div class="alert-error-dark p-3 mb-4">
                        <i class="bi bi-exclamation-triangle me-2"></i><?= h($contactError) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="#contact" novalidate>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <input type="text" name="contact_name" class="form-control-dark"
                                       placeholder="Your name" required
                                       value="<?= h($_POST['contact_name'] ?? '') ?>">
                            </div>
                            <div class="col-sm-6">
                                <input type="email" name="contact_email" class="form-control-dark"
                                       placeholder="Your email" required
                                       value="<?= h($_POST['contact_email'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <textarea name="contact_message" class="form-control-dark"
                                          placeholder="Your message" rows="4" required><?= h($_POST['contact_message'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="contact_submit" class="btn-primary-glow w-100"
                                        style="justify-content:center;">
                                    <i class="bi bi-send"></i> Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include "inc/footer.inc.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm"
        crossorigin="anonymous"></script>
<script src="js/main.js"></script>
</body>
</html>

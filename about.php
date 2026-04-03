<?php 
require_once __DIR__ . '/inc/bootstrap.php';

$pageTitle = "Pomegranate | About Us";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pomegranate | About Us</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/form.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include __DIR__ . '/inc/nav.inc.php'; ?>

<main>
    <div class="hero mb-0">
        <img src="assets/esmeralda.jpg" class="hero-img" alt="Esmeralda">
        <div class="hero-overlay position-absolute text-center">
            <h1 class="display-4 fw-bold text-white text-uppercase">Our Story</h1>
            <h1 class="section-heading mb-4 text-white">
                From Spec Sheets to Real-Life Solutions
            </h1>
        </div>
    </div>

    <div class="container py-5 bg-white animated">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="fw-bold mb-4">The spark that started it all</h2>
                <p class="text-blac mx-auto" style="max-width:720px;font-size:1.1rem;line-height:1.8;">
                    Pomegranate started with a small team of students, builders, and everyday tech users who were tired of confusing product hype.
                    We set out to create a store that explains technology clearly, recommends responsibly, and helps people buy what they actually need.
                </p>
                <p class="text-black mx-auto mb-0" style="max-width:720px;font-size:1.02rem;line-height:1.8;">
                    Today, we focus on practical devices for study, work, and creative projects, carefully selected, honestly priced,
                    and supported by a tekam that stays with you after checkout.
                </p>
            </div>
            <div class="col-md-6">
                <img src="assets/team.jpg" alt="The team" class="img-fluid rounded-4 shadow">
            </div>
        </div>
    </div>

<!-- ═══════════════════════════════════════════════════════════
     MISSION
═══════════════════════════════════════════════════════════ -->
<section id="mission" style="padding:5rem 0;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5 reveal-left">
                <!-- Decorative card stack -->
                <div class="position-relative" style="height:380px;">
                    <div style="position:absolute;inset:0;background:linear-gradient(135deg,rgba(34,211,238,.1),rgba(129,140,248,.1));border-radius:24px;border:1px solid rgba(255,255,255,.08);transform:rotate(-4deg);"></div>
                    <div style="position:absolute;inset:10px 20px;background:var(--bg-secondary);border-radius:20px;border:1px solid rgba(255,255,255,.08);overflow:hidden;">
                        <img src="assets/phone.jpg" alt="Mission" style="width:100%;height:100%;object-fit:cover;opacity:.7;">
                        <div style="position:absolute;inset:0;background:linear-gradient(180deg,transparent 40%,rgba(5,10,20,.95));"></div>
                        <div style="position:absolute;bottom:1.5rem;left:1.5rem;right:1.5rem;">
                            <div class="section-label d-inline-flex mb-2"><i class="bi bi-rocket-takeoff text-white"></i> Since 2020</div>
                            <h4 class="fw-bold text-white">Built by tech lovers,<br>for tech lovers.</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 offset-lg-1 reveal-right">
                <div class="section-label"><i class="bi bi-compass"></i> Mission</div>
                <h2 class="section-heading mb-4">
                    Innovation is Our <span class="text-gradient">Default Mode</span>
                </h2>
                <p class="text-black mb-4" style="line-height:1.9;">
                    We don't just sell gadgets — we curate experiences. Every device in our catalog is
                    rigorously tested by our team of engineers and enthusiasts before it earns a place on
                    our shelves.
                </p>
                <p class="text-black mb-4" style="line-height:1.9;">
                    Our mission is to bridge the gap between cutting-edge technology and the people who
                    will shape tomorrow with it. Whether you're a developer, designer, gamer, or creator —
                    Pomegranate has the tools you need.
                </p>
                <div class="row g-3">
                    <?php foreach ([
                        ['bi-lightning-fill','icon-cyan',   'Fast', 'Same-day dispatch on in-stock items'],
                        ['bi-shield-check',  'icon-purple', 'Secure','Bank-grade SSL on every transaction'],
                        ['bi-star-fill',     'icon-amber',  'Quality','Genuine products, zero counterfeits'],
                    ] as $f): ?>
                    <div class="col-4 text-center">
                        <div class="feature-icon-wrap <?= $f[1] ?> mx-auto mb-2" style="width:44px;height:44px;font-size:1.1rem;">
                            <i class="bi <?= $f[0] ?>"></i>
                        </div>
                        <div class="fw-bold small"><?= $f[2] ?></div>
                        <div class="text-black" style="font-size:.75rem;"><?= $f[3] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="section-divider"></div>

<!-- ═══════════════════════════════════════════════════════════
     VALUES
═══════════════════════════════════════════════════════════ -->
<section id="values" style="padding:5rem 0;background:var(--bg-secondary);">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <div class="section-label"><i class="bi bi-heart"></i> Values</div>
            <h2 class="section-heading">What We <span class="text-gradient">Stand For</span></h2>
        </div>
        <div class="row g-4">
            <?php $vals = [
                ['bi-people-fill',      'icon-cyan',  'Community First',   'We grow with our customers. Every feedback shapes our next move.'],
                ['bi-leaf',             'icon-green', 'Sustainability',     'Carbon-neutral shipping and eco-friendly packaging by 2026.'],
                ['bi-eye',              'icon-purple','Transparency',       'No hidden fees. No dark patterns. Just honest business.'],
                ['bi-rocket-takeoff',   'icon-amber', 'Constant Innovation','We never stop pushing. The status quo is our biggest rival.'],
                ['bi-universal-access', 'icon-pink',  'Accessibility',      'Technology for everyone — inclusive design in everything we do.'],
                ['bi-hand-thumbs-up',   'icon-cyan',  'Integrity',          'We do the right thing, even when no one\'s watching.'],
            ];
            foreach ($vals as $i => $v): ?>
            <div class="col-12 col-md-6 col-lg-4 reveal delay-<?= min($i+1,5) ?>">
                <div class="feature-card">
                    <div class="feature-icon-wrap <?= $v[1] ?>"><i class="bi <?= $v[0] ?>"></i></div>
                    <h5><?= $v[2] ?></h5>
                    <p><?= $v[3] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="section-divider"></div>

    <section class="py-5 bg-light animated">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Why Choose Us?</h2>
                <p class="text-muted">The core principles that drive our selection process.</p>
            </div>
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="p-4">
                        <i class="bi bi-shield-check feature-icon mb-3"></i>
                        <h3 class="fw-bold h5">Quality Assurance</h3>
                        <p class="small text-muted">Regular warehouse maintenance checks ensuring products are kept in a clean, no-risk environment to avoid damages</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4">
                        <i class="bi bi-box-seam feature-icon mb-3"></i>
                        <h3 class="fw-bold h5">Global Sourcing</h3>
                        <p class="small text-muted">We partner with top brands to bring you exclusive releases.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4">
                        <i class="bi bi-headset feature-icon mb-3"></i>
                        <h3 class="fw-bold h5">24/7 Support</h3>
                        <p class="small text-muted">Our dedicated team is always here to guide and solve.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 text-center bg-dark text-white animated">
        <div class="container">
            <h2 class="fw-bold">Ready to Explore?</h2>
            <p class="mb-4">Check out our latest collection of premium tech.</p>
            <button onclick="window.location.href='/shop/catalog.php'" class="btn btn-outline-light btn-lg px-5">Shop Now</button>
        </div>
    </section>
</main>

<?php include "inc/footer.inc.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
<script async src="https://unpkg.com/es-module-shims@1.8.0/dist/es-module-shims.js"></script>
<script type="importmap">
{
    "imports": {
        "three": "https://unpkg.com/three@0.158.0/build/three.module.js",
        "three/addons/": "https://unpkg.com/three@0.158.0/examples/jsm/"
    }
}
</script>
<script defer type="module" src="js/main.js"></script>
</body>
</html>

<?php require_once "inc/auth.inc.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us – Pomegranate</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<?php include "inc/nav.inc.php"; ?>

<main id="main-content">
<!-- ═══════════════════════════════════════════════════════════
     HERO
═══════════════════════════════════════════════════════════ -->
<section style="padding:6rem 0 4rem;position:relative;overflow:hidden;">
    <div class="orb orb-cyan" style="width:500px;height:500px;top:-40%;left:-10%;"></div>
    <div class="orb orb-purple" style="width:400px;height:400px;bottom:-20%;right:-5%;"></div>

    <div class="container text-center position-relative" style="z-index:2;">
        <div class="section-label"><i class="bi bi-building"></i> Our Story</div>
        <h1 class="section-heading mb-4">
            We're Pushing <span class="text-gradient">What's Possible</span>
        </h1>
        <p class="text-white-50 mx-auto" style="max-width:600px;font-size:1.1rem;line-height:1.8;">
            Pomegranate was founded on a simple belief: technology should be beautiful, powerful,
            and accessible to everyone who dares to dream bigger.
        </p>
    </div>
</section>

<div class="section-divider"></div>

<!-- ═══════════════════════════════════════════════════════════
     MISSION
═══════════════════════════════════════════════════════════ -->
<section style="padding:5rem 0;">
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
                            <div class="section-label d-inline-flex mb-2"><i class="bi bi-rocket-takeoff"></i> Since 2020</div>
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
                <p class="text-white-50 mb-4" style="line-height:1.9;">
                    We don't just sell gadgets — we curate experiences. Every device in our catalog is
                    rigorously tested by our team of engineers and enthusiasts before it earns a place on
                    our shelves.
                </p>
                <p class="text-white-50 mb-4" style="line-height:1.9;">
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
                        <div class="text-white-50" style="font-size:.75rem;"><?= $f[3] ?></div>
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
<section style="padding:5rem 0;background:var(--bg-secondary);">
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

<!-- ═══════════════════════════════════════════════════════════
     TEAM
═══════════════════════════════════════════════════════════ -->
<section style="padding:5rem 0;">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <div class="section-label"><i class="bi bi-people"></i> Team</div>
            <h2 class="section-heading">The Minds <span class="text-gradient">Behind It All</span></h2>
        </div>
        <div class="row justify-content-center g-4">
            <?php $team = [
                ['Alex Chen',   'Co-Founder & CEO',     'bi-person-circle', 'icon-cyan',   'Former Google engineer. Obsessed with UX.'],
                ['Priya Nair',  'CTO',                  'bi-person-circle', 'icon-purple', '10 years in hardware R&D at Samsung.'],
                ['Jordan Lee',  'Head of Design',       'bi-person-circle', 'icon-pink',   'Ex-Apple designer. Pixel-perfect perfectionist.'],
                ['Sam Tan',     'VP of Operations',     'bi-person-circle', 'icon-green',  'Supply chain wizard. Ships on time, every time.'],
            ];
            foreach ($team as $i => $m): ?>
            <div class="col-12 col-sm-6 col-lg-3 reveal delay-<?= $i+1 ?>">
                <div class="glass-card text-center p-4">
                    <div class="d-flex align-items-center justify-content-center mb-3 rounded-circle mx-auto <?= $m[3] ?>"
                         style="width:72px;height:72px;font-size:2.2rem;">
                        <i class="bi <?= $m[2] ?>"></i>
                    </div>
                    <h5 class="fw-bold mb-1"><?= $m[0] ?></h5>
                    <div class="text-white-50 small mb-2"><?= $m[1] ?></div>
                    <p class="text-white-50" style="font-size:.82rem;margin:0;"><?= $m[4] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     CTA
═══════════════════════════════════════════════════════════ -->
<section style="padding:5rem 0;background:var(--bg-secondary);text-align:center;">
    <div class="container reveal">
        <h2 class="section-heading mb-3">Ready to <span class="text-gradient">Experience It?</span></h2>
        <p class="text-white-50 mb-4" style="max-width:480px;margin:auto;">
            Browse our catalog and find the device that takes your work — and life — to the next level.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="catalog.php" class="btn-primary-glow">
                <i class="bi bi-grid-3x3-gap"></i> Browse Catalog
            </a>
            <a href="signup.php" class="btn-outline-glow">
                <i class="bi bi-person-plus"></i> Join Pomegranate
            </a>
        </div>
    </div>
</section>

</main>

<?php include "inc/footer.inc.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>

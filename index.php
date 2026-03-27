<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/support.inc.php';

$formData = [
    'inquiry_type' => 'Contact',
    'name' => '',
    'email' => '',
    'subject' => '',
    'message' => '',
];
$errors = [];
$successMessage = get_flash('support_success');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'contact_form') {
    $formData = sanitize_contact_payload($_POST);

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $errors = validate_contact_payload($formData);

        if ($errors === []) {
            $mailResult = send_customer_confirmation_email($formData);

            if ($mailResult['sent']) {
                set_flash(
                    'support_success',
                    'Thanks for reaching out. We sent a confirmation email to your inbox.'
                );
                redirect_to('index.php#contact-support');
            }

            $errors['form'] = 'We could not send your confirmation email right now. Please try again later.';
        }
    }
}

$pageTitle = 'Pomegranate | Home';
include __DIR__ . '/inc/page-top.inc.php';
?>

<div id="carouselExampleCaptions" class="carousel slide mb-4" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="2" aria-label="Slide 3"></button>
    </div>
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="<?= h(app_url('assets/phone.jpg')) ?>" class="d-block w-100" alt="Phone">
            <div class="carousel-caption d-none d-md-block">
                <h5>To Inspire</h5>
                <p>"Let's go invent tomorrow instead of worrying about what happened yesterday." - Steve Jobs</p>
            </div>
        </div>
        <div class="carousel-item">
            <img src="<?= h(app_url('assets/phone-berries.jpg')) ?>" class="d-block w-100" alt="Phone berries">
            <div class="carousel-caption d-none d-md-block">
                <h5>To Innovate</h5>
                <p>"Innovation is the outcome of a habit, not a random act." - Sukant Ratnakar</p>
            </div>
        </div>
        <div class="carousel-item">
            <img src="<?= h(app_url('assets/phone-blue.jpg')) ?>" class="d-block w-100" alt="Phone blue">
            <div class="carousel-caption d-none d-md-block">
                <h5>To Commemorate</h5>
                <p>"Technology is best when it brings people together." - Matt Mullenweg</p>
            </div>
        </div>
    </div>
</div>

<div id="collections" class="container my-5">
    <div class="text-center py-4">
        <h1 class="fw-bold">Our Collections</h1>
        <p class="text-muted">Discover the latest technological trends and keep up to date</p>
    </div>

    <div class="row justify-content-center g-4">
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
            <div class="card w-100 shadow-sm border-0">
                <img src="<?= h(app_url('assets/cat.jpg')) ?>" class="card-img-top" alt="Collection showcase">
                <div class="card-body text-center">
                    <h5 class="card-title fw-bold">The 1 Series</h5>
                    <p class="card-text">
                        Explore thoughtful device designs, practical performance upgrades, and standout aesthetics in one refined lineup.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
            <div class="card w-100 shadow-sm border-0">
                <img src="<?= h(app_url('assets/cat.jpg')) ?>" class="card-img-top" alt="Collection showcase">
                <div class="card-body text-center">
                    <h5 class="card-title fw-bold">The 2 Series</h5>
                    <p class="card-text">
                        Compare modern hardware, user-first features, and flexible choices that fit work, study, and everyday life.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
            <div class="card w-100 shadow-sm border-0">
                <img src="<?= h(app_url('assets/cat.jpg')) ?>" class="card-img-top" alt="Collection showcase">
                <div class="card-body text-center">
                    <h5 class="card-title fw-bold">The 3 Series</h5>
                    <p class="card-text">
                        Stay close to new launches, customer impressions, and future-ready ideas from across our product family.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<section id="contact-support" class="position-relative py-5">
    <img id="contact-map" src="<?= h(app_url('assets/contact-map-2.png')) ?>" alt="Contact map background"
        class="position-absolute top-0 start-0 w-100 h-100">
    <div id="contact-container" class="container position-relative">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-5">
                <div class="support-sidecard h-100">
                    <span class="support-eyebrow">Contact Center</span>
                    <h2 class="fw-bold mt-2">Contact us or leave feedback in one quick form.</h2>
                    <p class="text-muted mb-4">
                        Once your message is sent successfully, PHPMailer will email you a confirmation so you know it went through.
                    </p>
                    <div class="support-feature-list">
                        <div class="support-feature">
                            <i class="bi bi-shield-check"></i>
                            <span>Server-side validation and sanitized submissions</span>
                        </div>
                        <div class="support-feature">
                            <i class="bi bi-envelope-check"></i>
                            <span>Automated confirmation email sent to the customer after submission</span>
                        </div>
                        <div class="support-feature">
                            <i class="bi bi-chat-square-heart"></i>
                            <span>Simple shared form for both contact messages and feedback</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="support-form-card">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div>
                            <h3 class="fw-bold mb-1">Send a message</h3>
                            <p class="text-muted mb-0">Choose whether you are contacting us or sharing feedback.</p>
                        </div>
                        <span class="badge text-bg-light border">Confirmation email on success</span>
                    </div>

                    <?php if ($successMessage !== null): ?>
                        <div class="alert alert-success mt-4 mb-0"><?= h($successMessage) ?></div>
                    <?php endif; ?>

                    <?php if (isset($errors['form'])): ?>
                        <div class="alert alert-danger mt-4 mb-0"><?= h($errors['form']) ?></div>
                    <?php endif; ?>

                    <form method="post" class="row g-3 mt-1" novalidate>
                        <input type="hidden" name="form_action" value="contact_form">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="inquiry_type">Inquiry type</label>
                            <select
                                id="inquiry_type"
                                name="inquiry_type"
                                class="form-select<?= isset($errors['inquiry_type']) ? ' is-invalid' : '' ?>"
                                required
                            >
                                <?php foreach (support_inquiry_types() as $type): ?>
                                    <option value="<?= h($type) ?>"<?= $formData['inquiry_type'] === $type ? ' selected' : '' ?>>
                                        <?= h($type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['inquiry_type'])): ?>
                                <div class="invalid-feedback"><?= h($errors['inquiry_type']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="name">Your name</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                maxlength="100"
                                class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                                value="<?= h($formData['name']) ?>"
                                required
                            >
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= h($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="email">Email address</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                maxlength="150"
                                class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                                value="<?= h($formData['email']) ?>"
                                required
                            >
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= h($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="subject">Subject</label>
                            <input
                                id="subject"
                                name="subject"
                                type="text"
                                maxlength="150"
                                class="form-control<?= isset($errors['subject']) ? ' is-invalid' : '' ?>"
                                value="<?= h($formData['subject']) ?>"
                                required
                            >
                            <?php if (isset($errors['subject'])): ?>
                                <div class="invalid-feedback"><?= h($errors['subject']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="message">Message</label>
                            <textarea
                                id="message"
                                name="message"
                                rows="5"
                                maxlength="2000"
                                class="form-control<?= isset($errors['message']) ? ' is-invalid' : '' ?>"
                                required
                            ><?= h($formData['message']) ?></textarea>
                            <?php if (isset($errors['message'])): ?>
                                <div class="invalid-feedback"><?= h($errors['message']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 d-flex flex-column flex-sm-row gap-3 align-items-sm-center">
                            <button id="contact-submit" type="submit" class="btn text-white px-4">Send message</button>
                            <small class="text-muted">You will only see success after your confirmation email is sent.</small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/inc/page-bottom.inc.php'; ?>

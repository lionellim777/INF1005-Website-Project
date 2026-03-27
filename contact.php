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
                redirect_to('contact.php#contact-support');
            }

            $errors['form'] = 'We could not send your confirmation email right now. Please try again later.';
        }
    }
}

$pageTitle = 'Pomegranate | Contact';
include __DIR__ . '/inc/page-top.inc.php';
?>

<section id="contact-support" class="position-relative py-5">
    <img id="contact-map" src="<?= h(app_url('assets/contact-map-2.png')) ?>" alt="Contact map background"
        class="position-absolute top-0 start-0 w-100 h-100">
    <div id="contact-container" class="container position-relative">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-5">
                <div class="support-sidecard h-100">
                    <span class="support-eyebrow">Contact Center</span>
                    <h1 class="fw-bold mt-2">Contact us or leave feedback in one quick form.</h1>
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
                            <h2 class="fw-bold mb-1">Send a message</h2>
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

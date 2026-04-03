<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';
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

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } elseif (support_honeypot_triggered($_POST)) {
        $errors['form'] = 'We could not process that submission. Please try again.';
    } elseif (support_submission_is_rate_limited()) {
        $errors['form'] = support_submission_rate_limit_message();
    } else {
        $errors = validate_contact_payload($formData);

        if ($errors === []) {
            mark_support_submission_attempt();
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const charCountElements = document.querySelectorAll('[data-char-count]');
    
    charCountElements.forEach(function(el) {
        const counterId = el.getAttribute('data-char-count');
        const counterSpan = document.getElementById(counterId);
        
        if (!counterSpan) {
            console.warn('Counter span not found for:', counterId);
            return;
        }
        
        function updateCount() {
            const length = el.value.length;
            const max = el.getAttribute('maxlength') || '?';
            counterSpan.textContent = length + ' / ' + max;
        }
        
        el.addEventListener('input', updateCount);
        updateCount(); // set initial value
    });
});
</script>

<link rel="stylesheet" href="/css/form.css">

<section id="contact-support" class="py-5">
    <div id="contact-container" class="container">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-5">
                <div class="support-sidecard h-100">
                    <span class="support-eyebrow">Contact Center</span>
                    <h1 class="fw-bold mt-2">Contact us or leave feedback in one quick form.</h1>
                    <p class="text-muted mb-4">
                        Once your message is sent successfully, we will email you a confirmation so you know it went through.
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

                    <form id="contact-form" method="post" class="row g-3 mt-1" novalidate>
                        <input type="hidden" name="form_action" value="contact_form">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <div class="support-honeypot" aria-hidden="true">
                            <label for="website">Website</label>
                            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                        </div>

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
                                minlength="2"
                                maxlength="100"
                                class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                                value="<?= h($formData['name']) ?>"
                                required
                            >
                            <div class="form-text">Use your actual name so we know how to address you.</div>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= h($errors['name']) ?></div>
                            <?php else: ?>
                                <div class="invalid-feedback">Please enter at least 2 characters for your name.</div>
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
                            <div class="form-text">We will use this address for the confirmation email.</div>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= h($errors['email']) ?></div>
                            <?php else: ?>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="subject">Subject</label>
                            <input
                                id="subject"
                                name="subject"
                                type="text"
                                minlength="3"
                                maxlength="150"
                                data-char-count="subject-count"
                                class="form-control<?= isset($errors['subject']) ? ' is-invalid' : '' ?>"
                                value="<?= h($formData['subject']) ?>"
                                required
                            >
                            <div class="d-flex justify-content-between align-items-center gap-3 form-text">
                                <span>Keep it short and specific. Avoid adding links here.</span>
                                <span id="subject-count" class="char-count" aria-live="polite">0 / 150</span>
                            </div>
                            <?php if (isset($errors['subject'])): ?>
                                <div class="invalid-feedback"><?= h($errors['subject']) ?></div>
                            <?php else: ?>
                                <div class="invalid-feedback">Please enter a subject between 3 and 150 characters.</div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="message">Message</label>
                            <textarea
                                id="message"
                                name="message"
                                rows="5"
                                maxlength="2000"
                                data-char-count="message-count"
                                class="form-control<?= isset($errors['message']) ? ' is-invalid' : '' ?>"
                                required
                            ><?= h($formData['message']) ?></textarea>
                            <div class="d-flex justify-content-between align-items-center gap-3 form-text">
                                <span>Include enough detail for us to understand the issue or feedback. Limit links to keep the form focused.</span>
                                <span id="message-count" class="char-count" aria-live="polite">0 / 2000</span>
                            </div>
                            <?php if (isset($errors['message'])): ?>
                                <div class="invalid-feedback"><?= h($errors['message']) ?></div>
                            <?php else: ?>
                                <div class="invalid-feedback">Please enter a message between 10 and 2000 characters.</div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 d-flex flex-column flex-sm-row gap-3 align-items-sm-center">
                            <button id="contact-submit" type="submit" class="btn text-white px-4">Send message</button>
                            <small class="text-muted">You will only see success after your confirmation email is sent.</small>
                        </div>
                        <div class="col-12">
                            <div class="support-policy-note">
                                <strong>Submission policy:</strong> Please avoid sending duplicate messages. We aim to review submissions within 2 to 3 business days, and repeated rapid submissions may be temporarily blocked.
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/inc/page-bottom.inc.php'; ?>

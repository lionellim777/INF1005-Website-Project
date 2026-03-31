const yearTarget = document.getElementById('year');

if (yearTarget) {
    yearTarget.textContent = new Date().getFullYear().toString();
}

const contactForm = document.getElementById('contact-form');

if (contactForm instanceof HTMLFormElement) {
    const countFields = contactForm.querySelectorAll('[data-char-count]');

    const updateCount = (field) => {
        const counterId = field.getAttribute('data-char-count');

        if (!counterId) {
            return;
        }

        const counter = document.getElementById(counterId);

        if (!counter) {
            return;
        }

        const maxLength = field.getAttribute('maxlength') ?? '';
        counter.textContent = `${field.value.length} / ${maxLength}`;
    };

    countFields.forEach((field) => {
        if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement)) {
            return;
        }

        updateCount(field);
        field.addEventListener('input', () => updateCount(field));
    });

    contactForm.addEventListener('submit', (event) => {
        if (!contactForm.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        contactForm.classList.add('was-validated');
    });
}

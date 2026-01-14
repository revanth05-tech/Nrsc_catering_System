/**
 * NRSC Catering System - Form Validation
 */

function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    let isValid = true;
    clearErrors(form);

    form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        }
    });

    form.querySelectorAll('[type="email"]').forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            showFieldError(field, 'Please enter a valid email');
            isValid = false;
        }
    });

    form.querySelectorAll('[data-min]').forEach(field => {
        const min = parseInt(field.dataset.min);
        if (parseInt(field.value) < min) {
            showFieldError(field, `Minimum value is ${min}`);
            isValid = false;
        }
    });

    return isValid;
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showFieldError(field, message) {
    field.classList.add('error');
    const error = document.createElement('span');
    error.className = 'field-error';
    error.textContent = message;
    field.parentNode.appendChild(error);
}

function clearErrors(form) {
    form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    form.querySelectorAll('.field-error').forEach(el => el.remove());
}

function validateDate(dateField, minDate = null) {
    const date = new Date(dateField.value);
    const min = minDate ? new Date(minDate) : new Date();
    min.setHours(0, 0, 0, 0);

    if (date < min) {
        showFieldError(dateField, 'Date cannot be in the past');
        return false;
    }
    return true;
}

function validateGuestCount(field, min = 1, max = 500) {
    const count = parseInt(field.value);
    if (count < min || count > max) {
        showFieldError(field, `Guest count must be between ${min} and ${max}`);
        return false;
    }
    return true;
}

window.validateForm = validateForm;
window.validateDate = validateDate;
window.validateGuestCount = validateGuestCount;

// ============================================================
// VALIDATION.JS - Form Validation Utilities
// ============================================================

// ============ PHONE VALIDATION ============
function validatePhone(phone) {
    const phoneRegex = /^09[0-9]{8}$/;
    return phoneRegex.test(phone);
}

function formatPhone(phone) {
    // Remove non-digits
    phone = phone.replace(/\D/g, '');
    // Limit to 10 digits
    if (phone.length > 10) {
        phone = phone.slice(0, 10);
    }
    return phone;
}

// ============ PIN VALIDATION ============
function validatePin(pin) {
    return /^[0-9]{6}$/.test(pin);
}

function validatePinMatch(pin1, pin2) {
    return pin1 === pin2;
}

// ============ NAME VALIDATION ============
function validateName(name) {
    return name.trim().length >= 2;
}

// ============ AMOUNT VALIDATION ============
function validateAmount(amount) {
    const num = parseFloat(amount);
    return !isNaN(num) && num > 0;
}

function formatAmount(amount) {
    const num = parseFloat(amount);
    if (isNaN(num)) return '0.00';
    return num.toFixed(2);
}

// ============ EMAIL VALIDATION ============
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// ============ FIELD VALIDATORS ============
const validators = {
    phone: {
        validate: validatePhone,
        message: 'Please enter a valid phone number (09xxxxxxxx)'
    },
    pin: {
        validate: validatePin,
        message: 'PIN must be 6 digits'
    },
    name: {
        validate: validateName,
        message: 'Please enter your full name'
    },
    email: {
        validate: validateEmail,
        message: 'Please enter a valid email address'
    },
    amount: {
        validate: validateAmount,
        message: 'Please enter a valid amount'
    }
};

// ============ FORM VALIDATION ============
function validateForm(formId, rules) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input, select, textarea');
    let isValid = true;
    
    inputs.forEach(input => {
        const name = input.name || input.id;
        const rule = rules[name];
        
        if (rule) {
            const value = input.value;
            const isValidField = rule.validate(value);
            
            if (!isValidField) {
                showFieldError(input, rule.message);
                isValid = false;
            } else {
                clearFieldError(input);
            }
        }
    });
    
    return isValid;
}

function showFieldError(input, message) {
    const errorEl = input.parentElement.querySelector('.error-message');
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.add('show');
    }
    input.classList.add('error');
}

function clearFieldError(input) {
    const errorEl = input.parentElement.querySelector('.error-message');
    if (errorEl) {
        errorEl.classList.remove('show');
    }
    input.classList.remove('error');
}

// ============ REAL-TIME VALIDATION ============
function setupRealTimeValidation(formId, rules) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        const name = input.name || input.id;
        const rule = rules[name];
        
        if (rule) {
            // Validate on blur
            input.addEventListener('blur', function() {
                const isValid = rule.validate(this.value);
                if (!isValid && this.value.length > 0) {
                    showFieldError(this, rule.message);
                } else {
                    clearFieldError(this);
                }
            });
            
            // Clear error on input
            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    clearFieldError(this);
                }
            });
        }
    });
}

// ============ EXPOSE FUNCTIONS ============
window.validatePhone = validatePhone;
window.validatePin = validatePin;
window.validateName = validateName;
window.validateAmount = validateAmount;
window.validateEmail = validateEmail;
window.formatPhone = formatPhone;
window.formatAmount = formatAmount;
window.validateForm = validateForm;
window.setupRealTimeValidation = setupRealTimeValidation;
window.validators = validators;

/* js/script.js */
document.addEventListener('DOMContentLoaded', function() {
    // Phone number formatting
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 10) this.value = this.value.slice(0, 10);
        });
    });

    // PIN/OTP numeric only
    const pinInputs = document.querySelectorAll('input[type="password"], input#otp');
    pinInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });

    // Amount validation
    const amountInput = document.querySelector('input[name="amount"]');
    if (amountInput) {
        amountInput.addEventListener('blur', function() {
            const val = parseFloat(this.value);
            if (val < 0) this.value = 0;
            if (this.value.includes('.')) {
                const parts = this.value.split('.');
                if (parts[1] && parts[1].length > 2) {
                    this.value = parts[0] + '.' + parts[1].slice(0, 2);
                }
            }
        });
    }

    // Form submit animation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const btn = this.querySelector('.btn-primary');
            if (btn) {
                btn.textContent = 'Processing...';
                btn.disabled = true;
            }
        });
    });

    // Auto-dismiss flash messages
    const errorEl = document.querySelector('.error');
    if (errorEl) {
        setTimeout(() => {
            errorEl.style.opacity = '0';
            setTimeout(() => errorEl.remove(), 300);
        }, 4000);
    }
});

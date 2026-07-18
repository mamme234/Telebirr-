// ============================================================
// MAIN.JS - Core JavaScript for Telebirr Pro
// ============================================================

// ============ DOM READY ============
document.addEventListener('DOMContentLoaded', function() {
    // Initialize page specific functions
    const page = getPageName();
    
    switch(page) {
        case 'index':
            initLandingPage();
            break;
        case 'login':
            initLoginPage();
            break;
        case 'dashboard':
            initDashboard();
            break;
        case 'bonus':
            initBonusPage();
            break;
        case 'profile':
            initProfilePage();
            break;
    }
    
    // Global event listeners
    initGlobalListeners();
});

// ============ PAGE DETECTION ============
function getPageName() {
    const path = window.location.pathname;
    const page = path.split('/').pop().split('.')[0];
    return page || 'index';
}

// ============ LANDING PAGE ============
function initLandingPage() {
    // Animate stats counting
    animateStats();
    
    // Smooth scroll for nav links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
}

function animateStats() {
    const stats = document.querySelectorAll('.stat-number');
    stats.forEach(stat => {
        const target = parseInt(stat.getAttribute('data-count'));
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;
        
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                if (target >= 1000) {
                    stat.textContent = Math.round(current).toLocaleString();
                } else {
                    stat.textContent = Math.round(current);
                }
                requestAnimationFrame(updateCounter);
            } else {
                stat.textContent = target.toLocaleString();
            }
        };
        
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                updateCounter();
                observer.disconnect();
            }
        });
        observer.observe(stat);
    });
}

// ============ LOGIN PAGE ============
function initLoginPage() {
    // Check for tab parameter
    const hash = window.location.hash;
    if (hash === '#register') {
        switchTab('register');
    }
}

function switchTab(tab) {
    const tabs = document.querySelectorAll('.tab-btn');
    const forms = document.querySelectorAll('.auth-form');
    
    tabs.forEach(t => t.classList.remove('active'));
    forms.forEach(f => f.style.display = 'none');
    
    if (tab === 'login') {
        document.querySelector('.tab-btn[data-tab="login"]').classList.add('active');
        document.getElementById('loginForm').style.display = 'block';
    } else {
        document.querySelector('.tab-btn[data-tab="register"]').classList.add('active');
        document.getElementById('registerForm').style.display = 'block';
    }
}

function togglePin(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.parentElement.querySelector('.toggle-pin i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// ============ VALIDATION FUNCTIONS ============
function validateLogin() {
    const phone = document.getElementById('login_phone');
    const pin = document.getElementById('login_pin');
    let isValid = true;
    
    // Validate phone
    const phoneRegex = /^09[0-9]{8}$/;
    if (!phoneRegex.test(phone.value)) {
        showError(phone, 'Please enter a valid phone number (09xxxxxxxx)');
        isValid = false;
    } else {
        clearError(phone);
    }
    
    // Validate PIN
    if (pin.value.length !== 6 || !/^[0-9]{6}$/.test(pin.value)) {
        showError(pin, 'PIN must be 6 digits');
        isValid = false;
    } else {
        clearError(pin);
    }
    
    if (isValid) {
        showLoading('loginBtn', 'Logging in...');
        return true;
    }
    return false;
}

function validateRegister() {
    const name = document.getElementById('reg_name');
    const phone = document.getElementById('reg_phone');
    const pin = document.getElementById('reg_pin');
    const confirmPin = document.getElementById('reg_confirm_pin');
    let isValid = true;
    
    // Validate name
    if (name.value.trim().length < 2) {
        showError(name, 'Please enter your full name');
        isValid = false;
    } else {
        clearError(name);
    }
    
    // Validate phone
    const phoneRegex = /^09[0-9]{8}$/;
    if (!phoneRegex.test(phone.value)) {
        showError(phone, 'Please enter a valid phone number (09xxxxxxxx)');
        isValid = false;
    } else {
        clearError(phone);
    }
    
    // Validate PIN
    if (pin.value.length !== 6 || !/^[0-9]{6}$/.test(pin.value)) {
        showError(pin, 'PIN must be 6 digits');
        isValid = false;
    } else {
        clearError(pin);
    }
    
    // Validate confirm PIN
    if (confirmPin.value !== pin.value) {
        showError(confirmPin, 'PINs do not match');
        isValid = false;
    } else {
        clearError(confirmPin);
    }
    
    if (isValid) {
        showLoading('registerBtn', 'Creating account...');
        return true;
    }
    return false;
}

function showError(input, message) {
    const errorEl = input.parentElement.querySelector('.error-message');
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.add('show');
    }
    input.style.borderColor = '#ef4444';
}

function clearError(input) {
    const errorEl = input.parentElement.querySelector('.error-message');
    if (errorEl) {
        errorEl.classList.remove('show');
    }
    input.style.borderColor = '#e2e8f0';
}

function showLoading(btnId, text) {
    const btn = document.getElementById(btnId);
    if (btn) {
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${text}`;
        btn.disabled = true;
    }
}

function resetButton(btnId, text, icon = '') {
    const btn = document.getElementById(btnId);
    if (btn) {
        btn.innerHTML = icon ? `<i class="fas fa-${icon}"></i> ${text}` : text;
        btn.disabled = false;
    }
}

function showMessage(elementId, message, type) {
    const el = document.getElementById(elementId);
    if (el) {
        el.textContent = message;
        el.className = `form-message ${type}`;
        el.style.display = 'block';
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            el.style.display = 'none';
        }, 5000);
    }
}

// ============ DASHBOARD ============
function initDashboard() {
    loadDashboardData();
}

async function loadDashboardData() {
    try {
        const response = await fetch('/api/dashboard');
        const data = await response.json();
        
        if (data.success) {
            // Update balance
            const balance = formatCurrency(data.user.balance);
            document.querySelectorAll('.balance-amount').forEach(el => {
                if (el.id === 'mainBalance' || el.id === 'userBalance') {
                    el.textContent = balance;
                }
            });
            
            // Update balance badges
            document.querySelectorAll('.balance-badge span').forEach(el => {
                if (el.id !== 'userBalance') {
                    el.textContent = balance;
                }
            });
            
            // Load bonuses
            renderBonuses(data.bonuses, 'activeBonuses');
            
            // Load transactions
            renderTransactions(data.transactions);
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
        showNotification('Error loading dashboard data');
    }
}

function renderBonuses(bonuses, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    if (!bonuses || bonuses.length === 0) {
        container.innerHTML = '<p class="no-bonuses">No bonuses available</p>';
        return;
    }
    
    container.innerHTML = bonuses.map(bonus => `
        <div class="bonus-card">
            <div class="bonus-icon">${getBonusIcon(bonus.type)}</div>
            <h3>${escapeHtml(bonus.title)}</h3>
            <p class="bonus-amount">${formatCurrency(bonus.amount)}</p>
            <p class="bonus-desc">${escapeHtml(bonus.description)}</p>
            <button onclick="claimBonus(${bonus.id})" class="btn-claim">Claim Now</button>
        </div>
    `).join('');
}

function renderTransactions(transactions) {
    const container = document.getElementById('recentTransactions');
    if (!container) return;
    
    if (!transactions || transactions.length === 0) {
        container.innerHTML = '<p class="no-transactions">No transactions yet</p>';
        return;
    }
    
    container.innerHTML = transactions.map(tx => `
        <div class="transaction-item">
            <div class="tx-icon ${tx.type}">
                <i class="fas fa-${getTransactionIcon(tx.type)}"></i>
            </div>
            <div class="tx-info">
                <span class="tx-description">${escapeHtml(tx.description)}</span>
                <span class="tx-time">${timeAgo(tx.created_at)}</span>
            </div>
            <div class="tx-amount positive">${formatCurrency(tx.amount)}</div>
        </div>
    `).join('');
}

// ============ BONUS PAGE ============
function initBonusPage() {
    loadAllBonuses();
}

async function loadAllBonuses() {
    try {
        const response = await fetch('/api/bonuses?active=false');
        const data = await response.json();
        
        if (data.success) {
            const container = document.getElementById('allBonuses');
            if (container) {
                renderBonuses(data.bonuses, 'allBonuses');
            }
        }
    } catch (error) {
        console.error('Error loading bonuses:', error);
        showNotification('Error loading bonuses');
    }
}

async function claimBonus(bonusId) {
    try {
        const response = await fetch('/api/claim-bonus', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ bonusId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            // Reload bonuses
            loadAllBonuses();
            // Update balance
            loadDashboardData();
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Error claiming bonus:', error);
        showNotification('Error claiming bonus');
    }
}

// ============ PROFILE PAGE ============
function initProfilePage() {
    loadProfileData();
}

async function loadProfileData() {
    try {
        const response = await fetch('/api/user');
        const data = await response.json();
        
        if (data.success) {
            const user = data.user;
            document.getElementById('profileName').textContent = user.name || 'User';
            document.getElementById('profilePhone').textContent = user.phone;
            document.getElementById('profileFullName').value = user.name || '';
            document.getElementById('profilePhoneNumber').value = user.phone;
            document.getElementById('userBalance').textContent = formatCurrency(user.balance);
        }
    } catch (error) {
        console.error('Error loading profile:', error);
        showNotification('Error loading profile');
    }
}

async function updateProfile() {
    const name = document.getElementById('profileFullName').value.trim();
    const currentPin = document.getElementById('currentPin').value;
    const newPin = document.getElementById('newPin').value;
    const confirmNewPin = document.getElementById('confirmNewPin').value;
    
    if (!name) {
        showNotification('Name is required', 'error');
        return false;
    }
    
    // Validate PIN change if provided
    if (currentPin || newPin || confirmNewPin) {
        if (!currentPin || !newPin || !confirmNewPin) {
            showNotification('Please fill all PIN fields', 'error');
            return false;
        }
        if (newPin.length !== 6 || !/^[0-9]{6}$/.test(newPin)) {
            showNotification('PIN must be 6 digits', 'error');
            return false;
        }
        if (newPin !== confirmNewPin) {
            showNotification('PINs do not match', 'error');
            return false;
        }
    }
    
    try {
        const response = await fetch('/api/user', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name,
                currentPin,
                newPin
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Profile updated successfully!', 'success');
            // Update displayed name
            document.getElementById('profileName').textContent = name;
            // Clear PIN fields
            document.getElementById('currentPin').value = '';
            document.getElementById('newPin').value = '';
            document.getElementById('confirmNewPin').value = '';
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showNotification('Error updating profile', 'error');
    }
    
    return false;
}

// ============ UTILITY FUNCTIONS ============
function formatCurrency(amount) {
    return 'ETB ' + Number(amount).toFixed(2);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function timeAgo(date) {
    const now = new Date();
    const past = new Date(date);
    const diff = Math.floor((now - past) / 1000);
    
    if (diff < 60) return diff + 's ago';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return past.toLocaleDateString();
}

function getBonusIcon(type) {
    const icons = {
        'internet': '📶',
        'cash': '💰',
        'data': '📱',
        'gift': '🎁'
    };
    return icons[type] || '🎁';
}

function getTransactionIcon(type) {
    const icons = {
        'bonus': 'gift',
        'payment': 'exchange-alt',
        'deposit': 'arrow-down',
        'withdrawal': 'arrow-up'
    };
    return icons[type] || 'exchange-alt';
}

// ============ GLOBAL FUNCTIONS ============
function toggleMenu() {
    const navLinks = document.querySelector('.nav-links');
    if (navLinks) {
        navLinks.classList.toggle('show');
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

function showNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    const messageEl = document.getElementById('notificationMessage');
    
    if (notification && messageEl) {
        messageEl.textContent = message;
        notification.className = `notification show ${type}`;
        
        // Auto hide after 5 seconds
        clearTimeout(window.notificationTimeout);
        window.notificationTimeout = setTimeout(() => {
            closeNotification();
        }, 5000);
    }
}

function closeNotification() {
    const notification = document.getElementById('notification');
    if (notification) {
        notification.classList.remove('show');
    }
}

function openModal(bonus) {
    const modal = document.getElementById('claimModal');
    const details = document.getElementById('bonusDetails');
    
    if (modal && details) {
        details.innerHTML = `
            <p><strong>${escapeHtml(bonus.title)}</strong></p>
            <p>Amount: ${formatCurrency(bonus.amount)}</p>
            <p>${escapeHtml(bonus.description)}</p>
        `;
        modal.classList.add('show');
        document.getElementById('confirmClaim').onclick = () => {
            claimBonus(bonus.id);
            closeModal();
        };
    }
}

function closeModal() {
    const modal = document.getElementById('claimModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

// ============ GLOBAL EVENT LISTENERS ============
function initGlobalListeners() {
    // Close modals on outside click
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('claimModal');
        if (modal && modal.classList.contains('show')) {
            if (e.target === modal) {
                closeModal();
            }
        }
    });
    
    // Close notification on click
    document.addEventListener('click', function(e) {
        if (e.target.closest('.notification button')) {
            closeNotification();
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
            closeNotification();
        }
    });
}

// ============ EXPOSE FUNCTIONS GLOBALLY ============
window.switchTab = switchTab;
window.togglePin = togglePin;
window.toggleMenu = toggleMenu;
window.toggleSidebar = toggleSidebar;
window.validateLogin = validateLogin;
window.validateRegister = validateRegister;
window.claimBonus = claimBonus;
window.updateProfile = updateProfile;
window.showNotification = showNotification;
window.closeNotification = closeNotification;
window.openModal = openModal;
window.closeModal = closeModal;
window.loadDashboardData = loadDashboardData;

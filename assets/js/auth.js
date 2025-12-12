/**
 * Authentication Form Scripts
 * Client-side validation for login/register forms
 */

document.addEventListener('DOMContentLoaded', function() {
    // Tab switching: handle both header tab buttons and inline links/buttons with data-tab
    const tabTriggers = document.querySelectorAll('[data-tab]');
    const tabContents = document.querySelectorAll('.tab-content');

    tabTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            const tabName = this.dataset.tab;

            // Remove active class from header tab buttons if present
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            // Remove active class from tab contents
            tabContents.forEach(c => c.classList.remove('active'));

            // Add active class to matching header tab button (if exists)
            const headerBtn = document.querySelector('.tab-btn[data-tab="' + tabName + '"]');
            if (headerBtn) headerBtn.classList.add('active');

            // Show the requested form
            const targetForm = document.getElementById(tabName + 'Form');
            if (targetForm) targetForm.classList.add('active');

            // Clear alerts
            document.querySelectorAll('.alert').forEach(a => a.remove());

            // Prevent accidental form submission when trigger is inside a form
            if (e && e.preventDefault) e.preventDefault();
        });
    });
    
    // Login form validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('loginEmail').value.trim();
            const password = document.getElementById('loginPassword').value;
            const errors = [];
            
            if (!email) {
                errors.push('Email is required');
            } else if (!validateEmail(email)) {
                errors.push('Invalid email format');
            }
            
            if (!password) {
                errors.push('Password is required');
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                showLoginErrors(errors);
            }
        });
    }
    
    // Register form validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        const passwordInput = document.getElementById('registerPassword');
        
        // Show password strength in real-time
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                showPasswordStrength(this.value, 'strengthBar');
            });
        }
        
        registerForm.addEventListener('submit', function(e) {
            const fullName = document.getElementById('registerFullName').value.trim();
            const username = document.getElementById('registerUsername').value.trim();
            const email = document.getElementById('registerEmail').value.trim();
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = document.getElementById('registerConfirmPassword').value;
            const errors = [];
            
            if (!fullName) {
                errors.push('Full name is required');
            }
            
            if (!username) {
                errors.push('Username is required');
            } else if (!validateUsername(username)) {
                errors.push('Username must be 3+ characters and contain only letters, numbers, dash, or underscore');
            }
            
            if (!email) {
                errors.push('Email is required');
            } else if (!validateEmail(email)) {
                errors.push('Invalid email format');
            }
            
            if (!password) {
                errors.push('Password is required');
            } else if (!validatePassword(password)) {
                errors.push('Password must be 6+ characters with uppercase, lowercase, and number');
            }
            
            if (!confirmPassword) {
                errors.push('Confirm password is required');
            } else if (password !== confirmPassword) {
                errors.push('Passwords do not match');
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                showRegisterErrors(errors);
            }
        });
    }
});

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePassword(password) {
    return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/.test(password);
}

function validateUsername(username) {
    return username.length >= 3 && /^[a-zA-Z0-9_-]+$/.test(username);
}

function calculatePasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;
    return strength;
}

function showPasswordStrength(password, barId) {
    const bar = document.getElementById(barId);
    if (!bar) return;
    
    bar.className = 'password-strength-bar';
    
    if (!password) {
        bar.style.width = '0';
        return;
    }
    
    const strength = calculatePasswordStrength(password);
    
    if (strength < 2) {
        bar.classList.add('weak');
    } else if (strength < 4) {
        bar.classList.add('medium');
    } else {
        bar.classList.add('strong');
    }
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;

    // Find the toggle button within the same input group
    let button = null;
    if (input.parentElement) {
        button = input.parentElement.querySelector('.password-toggle-btn');
    }
    // Fallback: try to find any nearby toggle button
    if (!button) button = document.querySelector('.password-toggle-btn');

    if (input.type === 'password') {
        input.type = 'text';
        if (button) button.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        input.type = 'password';
        if (button) button.innerHTML = '<i class="bi bi-eye"></i>';
    }
}

function showLoginErrors(errors) {
    // Remove any previous validation alerts to avoid duplicates
    document.querySelectorAll('.js-validation-alert').forEach(a => a.remove());

    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show js-validation-alert';
    alertDiv.innerHTML = `
        <i class="bi bi-exclamation-circle"></i> 
        <strong>Validation Error:</strong><br>
        ${errors.map(e => '• ' + e).join('<br>')}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const loginForm = document.getElementById('loginForm');
    loginForm.parentElement.insertBefore(alertDiv, loginForm);

    // Close handler: animate then remove
    const closeBtn = alertDiv.querySelector('.btn-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // animate
            alertDiv.classList.add('js-dismiss');
            setTimeout(() => { if (alertDiv.parentElement) alertDiv.remove(); }, 380);
        });
    }

    // Auto-dismiss after 3 seconds with fancy animation
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.classList.add('js-dismiss');
            setTimeout(() => { if (alertDiv.parentElement) alertDiv.remove(); }, 380);
        }
    }, 3000);
}

function showRegisterErrors(errors) {
    // Remove any previous validation alerts to avoid duplicates
    document.querySelectorAll('.js-validation-alert').forEach(a => a.remove());

    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show js-validation-alert';
    alertDiv.innerHTML = `
        <i class="bi bi-exclamation-circle"></i> 
        <strong>Validation Error:</strong><br>
        ${errors.map(e => '• ' + e).join('<br>')}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const registerForm = document.getElementById('registerForm');
    registerForm.parentElement.insertBefore(alertDiv, registerForm);

    // Close handler: animate then remove
    const closeBtn = alertDiv.querySelector('.btn-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            alertDiv.classList.add('js-dismiss');
            setTimeout(() => { if (alertDiv.parentElement) alertDiv.remove(); }, 380);
        });
    }

    // Auto-dismiss after 3 seconds with animation
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.classList.add('js-dismiss');
            setTimeout(() => { if (alertDiv.parentElement) alertDiv.remove(); }, 380);
        }
    }, 3000);
}

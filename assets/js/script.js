document.addEventListener('DOMContentLoaded', function() {
    // Theme Management
    const savedTheme = localStorage.getItem('schoolTheme') || 'blue';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeButtons(savedTheme);
    
    document.querySelectorAll('.theme-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const theme = this.getAttribute('data-theme');
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('schoolTheme', theme);
            updateThemeButtons(theme);
        });
    });
    
    function updateThemeButtons(activeTheme) {
        document.querySelectorAll('.theme-btn').forEach(function(btn) {
            btn.classList.remove('active');
            if (btn.getAttribute('data-theme') === activeTheme) {
                btn.classList.add('active');
            }
        });
    }
    
    // Mobile Menu Toggle Button
    const toggleBtn = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            if (overlay) {
                overlay.classList.toggle('active');
            }
        });
    }
    
    // Close sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
    
    // Mobile sidebar close on link click
    document.querySelectorAll('.sidebar-menu a').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('active');
                if (overlay) {
                    overlay.classList.remove('active');
                }
            }
        });
    });
    
    // Close sidebar on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            sidebar.classList.remove('active');
            if (overlay) {
                overlay.classList.remove('active');
            }
        }
    });
    
    // Modal Management
    document.querySelectorAll('.modal-trigger').forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
            }
        });
    });
    
    document.querySelectorAll('.modal-close').forEach(function(closeBtn) {
        closeBtn.addEventListener('click', function() {
            this.closest('.modal').classList.remove('active');
        });
    });
    
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });
    
    // Delete Confirmation
    document.querySelectorAll('.delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this record?')) {
                e.preventDefault();
            }
        });
    });
    
    // Logout - uses styled confirmation page in logout.php
    
    // Login Form Validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            
            if (username && username.value.trim() === '') {
                e.preventDefault();
                showError('Please enter username');
            }
            if (password && password.value.trim() === '') {
                e.preventDefault();
                showError('Please enter password');
            }
        });
    }
    
    function showError(message) {
        const existingError = document.querySelector('.error-msg');
        if (existingError) existingError.remove();
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-msg';
        errorDiv.textContent = message;
        
        const form = document.getElementById('loginForm');
        if (form) {
            form.insertBefore(errorDiv, form.firstChild);
        }
        
        setTimeout(function() {
            errorDiv.remove();
        }, 3000);
    }
    
    // Auto-calculate totals for score entry
    document.querySelectorAll('input[type="number"]').forEach(function(input) {
        input.addEventListener('change', function() {
            calculateRowTotal(this);
        });
        
        input.addEventListener('input', function() {
            calculateRowTotal(this);
        });
    });
    
    function calculateRowTotal(input) {
        const row = input.closest('tr');
        if (!row) return;
        
        const inputs = row.querySelectorAll('input[type="number"]');
        let total = 0;
        
        inputs.forEach(function(inp) {
            const val = parseFloat(inp.value) || 0;
            total += val;
        });
        
        const totalCell = row.querySelector('[id^="total_"]');
        if (totalCell) {
            totalCell.textContent = total > 0 ? total.toFixed(1) : '-';
        }
    }
});

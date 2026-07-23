// Toggle Password Visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling?.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        if (icon) icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#dc3545';
            isValid = false;
            
            // Show error message
            let errorMsg = input.nextElementSibling;
            if (!errorMsg || !errorMsg.classList.contains('error-msg')) {
                errorMsg = document.createElement('small');
                errorMsg.className = 'error-msg';
                errorMsg.style.color = '#dc3545';
                errorMsg.textContent = 'Field ini wajib diisi';
                input.parentNode.insertBefore(errorMsg, input.nextSibling);
            }
        } else {
            input.style.borderColor = '#ddd';
            const errorMsg = input.nextElementSibling;
            if (errorMsg && errorMsg.classList.contains('error-msg')) {
                errorMsg.remove();
            }
        }
    });
    
    return isValid;
}

// File Preview
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(file);
    }
}

// Show Loading
function showLoading() {
    const loading = document.createElement('div');
    loading.className = 'loading-overlay';
    loading.innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading...</p>
        </div>
    `;
    document.body.appendChild(loading);
}

// Hide Loading
function hideLoading() {
    const loading = document.querySelector('.loading-overlay');
    if (loading) {
        loading.remove();
    }
}

// Confirm Dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Format Date
function formatDate(dateString) {
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    return new Date(dateString).toLocaleDateString('id-ID', options);
}

// Format Number (for currency)
function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

// Auto-generate Nomor Pendaftaran
function generateNomorPendaftaran() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    return `REG/${year}${month}/${random}`;
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all tooltips
    const tooltips = document.querySelectorAll('[data-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            const title = this.getAttribute('title');
            const tooltipEl = document.createElement('div');
            tooltipEl.className = 'custom-tooltip';
            tooltipEl.textContent = title;
            document.body.appendChild(tooltipEl);
            
            const rect = this.getBoundingClientRect();
            tooltipEl.style.left = (rect.left + rect.width / 2) + 'px';
            tooltipEl.style.top = (rect.top - tooltipEl.offsetHeight - 10) + 'px';
            
            this.setAttribute('data-original-title', title);
            this.removeAttribute('title');
        });
        
        tooltip.addEventListener('mouseleave', function() {
            const tooltipEl = document.querySelector('.custom-tooltip');
            if (tooltipEl) {
                tooltipEl.remove();
            }
            const originalTitle = this.getAttribute('data-original-title');
            if (originalTitle) {
                this.setAttribute('title', originalTitle);
            }
        });
    });
    
    // Auto-hide alerts (but not password reset alerts)
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        // Don't auto-hide alerts containing password reset info
        if (alert.querySelector('.no-auto-hide') || alert.textContent.includes('Password baru')) {
            return;
        }
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        }, 5000);
    });
    
    // Form auto-save for pendaftar
    if (window.location.pathname.includes('/pendaftar/formulir')) {
        const form = document.querySelector('form');
        if (form) {
            const saveDraft = () => {
                const formData = new FormData(form);
                const data = {};
                formData.forEach((value, key) => {
                    data[key] = value;
                });
                localStorage.setItem('formulirDraft', JSON.stringify(data));
                
                const indicator = document.createElement('div');
                indicator.className = 'save-indicator';
                indicator.innerHTML = '<i class="fas fa-save"></i> Disimpan otomatis';
                indicator.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: #28a745;
                    color: white;
                    padding: 10px 15px;
                    border-radius: 5px;
                    font-size: 12px;
                    opacity: 0;
                    transition: opacity 0.3s;
                    z-index: 1000;
                `;
                document.body.appendChild(indicator);
                
                setTimeout(() => {
                    indicator.style.opacity = '1';
                }, 10);
                
                setTimeout(() => {
                    indicator.style.opacity = '0';
                    setTimeout(() => {
                        if (indicator.parentNode) {
                            indicator.parentNode.removeChild(indicator);
                        }
                    }, 300);
                }, 2000);
            };
            
            form.addEventListener('input', saveDraft);
            form.addEventListener('change', saveDraft);
            
            // Load draft if exists
            const draft = localStorage.getItem('formulirDraft');
            if (draft) {
                const data = JSON.parse(draft);
                Object.keys(data).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.value = data[key];
                    }
                });
            }
        }
    }
    
    // Table row click for details
    const tableRows = document.querySelectorAll('.table tbody tr[data-id]');
    tableRows.forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (id && !this.querySelector('input, button, a')) {
                window.location.href = `detail.php?id=${id}`;
            }
        });
    });
});


function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');

    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('active'); // HP
    } else {
        sidebar.classList.toggle('collapsed'); // Laptop
    }
}

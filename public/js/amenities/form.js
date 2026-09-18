document.addEventListener('DOMContentLoaded', function() {
    // Icon preview functionality
    const iconInput = document.getElementById('icon');
    const iconPreview = document.getElementById('iconPreview');
    const iconPreviewImg = document.getElementById('iconPreviewImg');
    const removeIconCheckbox = document.getElementById('remove_icon');
    
    if (iconInput) {
        iconInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    iconPreviewImg.src = e.target.result;
                    iconPreview.classList.remove('d-none');
                    
                    // Uncheck remove icon if new icon is selected
                    if (removeIconCheckbox) {
                        removeIconCheckbox.checked = false;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Toggle remove icon checkbox
    if (removeIconCheckbox) {
        removeIconCheckbox.addEventListener('change', function() {
            if (this.checked) {
                iconPreview.classList.add('d-none');
                if (iconInput) iconInput.value = '';
            }
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Toggle status switch
    const statusToggle = document.getElementById('statusToggle');
    if (statusToggle) {
        statusToggle.addEventListener('change', function() {
            const statusLabel = this.nextElementSibling.querySelector('.form-check-label');
            if (this.checked) {
                statusLabel.textContent = 'Active';
                statusLabel.classList.remove('text-muted');
            } else {
                statusLabel.textContent = 'Inactive';
                statusLabel.classList.add('text-muted');
            }
        });
    }
    
    // Handle delete confirmation
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirmMessage || 'Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Handle icon search
    const iconSearch = document.getElementById('iconSearch');
    if (iconSearch) {
        iconSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const iconItems = document.querySelectorAll('.icon-item');
            
            iconItems.forEach(item => {
                const iconName = item.dataset.name.toLowerCase();
                if (iconName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Handle icon selection
    const iconItems = document.querySelectorAll('.icon-item');
    iconItems.forEach(item => {
        item.addEventListener('click', function() {
            // Update hidden input
            const iconInput = document.getElementById('icon_name');
            if (iconInput) {
                iconInput.value = this.dataset.icon;
                
                // Update preview
                const iconPreview = document.getElementById('iconPreview');
                const iconPreviewImg = document.getElementById('iconPreviewImg');
                
                if (iconPreview && iconPreviewImg) {
                    iconPreviewImg.className = `fas fa-${this.dataset.icon} fa-3x`;
                    iconPreview.classList.remove('d-none');
                }
                
                // Update selected state
                document.querySelectorAll('.icon-item').forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
                
                // Close modal if open
                const modal = bootstrap.Modal.getInstance(document.getElementById('iconPickerModal'));
                if (modal) modal.hide();
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Image preview for room image upload
    const roomImageInput = document.getElementById('room_image');
    const imagePreview = document.getElementById('imagePreview');
    const imagePreviewImg = document.getElementById('imagePreviewImg');
    const removeImageCheckbox = document.getElementById('remove_image');
    
    if (roomImageInput) {
        roomImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreviewImg.src = e.target.result;
                    imagePreview.classList.remove('d-none');
                    
                    // Uncheck remove image if new image is selected
                    if (removeImageCheckbox) {
                        removeImageCheckbox.checked = false;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Amenities modal handling
    const amenitiesModal = document.getElementById('amenitiesModal');
    if (amenitiesModal) {
        const modal = new bootstrap.Modal(amenitiesModal);
        
        // Save selected amenities
        document.getElementById('saveAmenitiesBtn')?.addEventListener('click', function() {
            const selectedAmenities = [];
            document.querySelectorAll('#amenitiesList input[type="checkbox"]:checked').forEach(checkbox => {
                selectedAmenities.push(checkbox.value);
            });
            
            // Update hidden input
            document.getElementById('amenities').value = JSON.stringify(selectedAmenities);
            
            // Update selected count
            const selectedCount = selectedAmenities.length;
            document.getElementById('selectedAmenitiesCount').textContent = selectedCount;
            
            modal.hide();
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

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Toggle password visibility
    const togglePasswordBtns = document.querySelectorAll('.toggle-password');
    togglePasswordBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    });

    // Handle room status changes
    const statusBadge = document.querySelector('.room-status-badge');
    if (statusBadge) {
        const roomId = statusBadge.dataset.roomId;
        const statusSelect = document.getElementById('statusSelect');
        
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                const newStatus = this.value;
                
                fetch(`/api/rooms/${roomId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status: newStatus })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update status badge
                        const statusClasses = ['bg-success', 'bg-warning', 'bg-danger', 'bg-info'];
                        statusBadge.classList.remove(...statusClasses);
                        
                        let newBadgeClass = 'bg-secondary';
                        switch(newStatus) {
                            case 'available': newBadgeClass = 'bg-success'; break;
                            case 'maintenance': newBadgeClass = 'bg-warning'; break;
                            case 'unavailable': newBadgeClass = 'bg-danger'; break;
                            case 'cleaning': newBadgeClass = 'bg-info'; break;
                        }
                        
                        statusBadge.classList.add(newBadgeClass);
                        statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        
                        // Show success message
                        showAlert('Room status updated successfully!', 'success');
                    } else {
                        throw new Error(data.message || 'Failed to update status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Failed to update room status. Please try again.', 'danger');
                    // Revert select value
                    statusSelect.value = statusBadge.dataset.currentStatus;
                });
            });
        }
    }

    // Helper function to show alerts
    function showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        const container = document.querySelector('.container.mt-4') || document.body;
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
            if (alert) alert.close();
        }, 5000);
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

    // Initialize datepickers if any
    if (typeof flatpickr !== 'undefined') {
        flatpickr("[data-datepicker]", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            minDate: 'today',
            minuteIncrement: 15
        });
    }
});

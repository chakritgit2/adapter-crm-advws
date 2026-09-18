document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle room status changes
    const statusButtons = document.querySelectorAll('.status-btn');
    statusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const roomId = this.dataset.roomId;
            const newStatus = this.dataset.status;
            const statusBadge = document.querySelector(`#statusBadge-${roomId}`);
            
            if (!confirm(`Are you sure you want to mark this room as ${newStatus}?`)) {
                return;
            }
            
            // Show loading state
            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
            
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
                    statusBadge.className = 'badge';
                    
                    let newBadgeClass = 'bg-secondary';
                    switch(newStatus) {
                        case 'available': newBadgeClass = 'bg-success'; break;
                        case 'maintenance': newBadgeClass = 'bg-warning text-dark'; break;
                        case 'unavailable': newBadgeClass = 'bg-danger'; break;
                        case 'cleaning': newBadgeClass = 'bg-info'; break;
                    }
                    
                    statusBadge.classList.add(newBadgeClass);
                    statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    
                    // Update active state of buttons
                    document.querySelectorAll('.status-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    showAlert('Room status updated successfully!', 'success');
                } else {
                    throw new Error(data.message || 'Failed to update status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to update room status. Please try again.', 'danger');
            })
            .finally(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            });
        });
    });

    // Handle delete confirmation
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirmMessage || 'Are you sure you want to delete this room? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Handle image preview in modals
    const imageModal = document.getElementById('imageModal');
    if (imageModal) {
        imageModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const imageUrl = button.getAttribute('data-bs-image');
            const modalImage = imageModal.querySelector('.modal-body img');
            modalImage.src = imageUrl;
        });
    }

    // Handle amenities modal
    const amenitiesModal = document.getElementById('amenitiesModal');
    if (amenitiesModal) {
        const modal = new bootstrap.Modal(amenitiesModal);
        
        // Save selected amenities
        document.getElementById('saveAmenitiesBtn')?.addEventListener('click', function() {
            const selectedAmenities = [];
            document.querySelectorAll('#amenitiesList input[type="checkbox"]:checked').forEach(checkbox => {
                selectedAmenities.push(checkbox.value);
            });
            
            // Show loading state
            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
            
            // Submit form via AJAX
            const form = document.getElementById('updateAmenitiesForm');
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the amenities display
                    const amenitiesContainer = document.querySelector('.amenities-container');
                    if (amenitiesContainer) {
                        amenitiesContainer.innerHTML = data.amenities_html;
                    }
                    
                    // Show success message
                    showAlert('Amenities updated successfully!', 'success');
                    
                    // Close the modal
                    modal.hide();
                } else {
                    throw new Error(data.message || 'Failed to update amenities');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to update amenities. Please try again.', 'danger');
            })
            .finally(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            });
        });
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

    // Initialize any datepickers
    if (typeof flatpickr !== 'undefined') {
        flatpickr("[data-datepicker]", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            minDate: 'today',
            minuteIncrement: 15
        });
    }

    // Handle quick booking form submission
    const quickBookForm = document.getElementById('quickBookForm');
    if (quickBookForm) {
        quickBookForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Booking...';
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.error) {
                    throw new Error(data.error);
                } else {
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert(error.message || 'Failed to create booking. Please try again.', 'danger');
            })
            .finally(() => {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            });
        });
    }
});

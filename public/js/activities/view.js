document.addEventListener('DOMContentLoaded', function() {
    // --- อ่านข้อมูลจาก Data Island ใน HTML ---
    const dataElement = document.getElementById('page-data');
    if (!dataElement) {
        console.error('Page data island not found!');
        return;
    }
    const pageData = JSON.parse(dataElement.textContent);
    const activityId = pageData.activityId;
    const initialQuotations = pageData.initialQuotations;


    // ==========================================================
    // === Feature 1: Inline Edit Description
    // ==========================================================
    function initializeDescriptionEdit() {
        const editBtn = document.getElementById('editBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const descriptionView = document.getElementById('descriptionView');
        const descriptionForm = document.getElementById('descriptionForm');
        
        if (!editBtn || !cancelBtn || !descriptionView || !descriptionForm) return;
        
        const descriptionTextarea = descriptionForm.querySelector('textarea[name="description"]');
        const feedbackDiv = document.getElementById('description-feedback');

        editBtn.addEventListener('click', () => {
            descriptionView.style.display = 'none';
            descriptionForm.style.display = 'block';
        });

        cancelBtn.addEventListener('click', () => {
            descriptionForm.style.display = 'none';
            descriptionView.style.display = 'block';
            feedbackDiv.textContent = '';
        });

        descriptionForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const newDescription = descriptionTextarea.value;

            feedbackDiv.textContent = 'Saving...';
            feedbackDiv.className = 'mt-2 small text-muted';

            fetch('/mcvpro/public/activities/updateDescription', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ 'activity_id': activityId, 'description': newDescription })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    descriptionView.innerHTML = newDescription.replace(/\n/g, '<br>');
                    feedbackDiv.textContent = 'Saved successfully!';
                    feedbackDiv.className = 'mt-2 small text-success';
                    setTimeout(() => {
                        descriptionForm.style.display = 'none';
                        descriptionView.style.display = 'block';
                        feedbackDiv.textContent = '';
                    }, 2000);
                } else {
                    feedbackDiv.textContent = 'Error: ' + (data.message || 'Could not save.');
                    feedbackDiv.className = 'mt-2 small text-danger';
                }
            })
            .catch(err => {
                console.error('Fetch Error:', err);
                feedbackDiv.textContent = 'A network error occurred.';
                feedbackDiv.className = 'mt-2 small text-danger';
            });
        });
    }

    // ==========================================================
    // === Feature 2: Filter Quotations by Status
    // ==========================================================
    function initializeQuotationFilter() {
        const statusFilter = document.getElementById('status-filter');
        const quotationListDiv = document.getElementById('quotation-list');
        const loadingSpinner = document.getElementById('loading-spinner');

        if (!statusFilter || !quotationListDiv || !loadingSpinner) return;
        
        // Initial render of quotations from data island
        renderQuotations(initialQuotations);

        statusFilter.addEventListener('change', function() {
            const selectedStatus = this.value;
            loadingSpinner.classList.remove('d-none');
            quotationListDiv.style.opacity = '0.5';

            fetch(`/mcvpro/public/activities/getFilteredQuotations/${activityId}/${selectedStatus}`)
                .then(res => res.json())
                .then(quotations => renderQuotations(quotations, selectedStatus))
                .catch(err => {
                    console.error('Error fetching filtered quotations:', err);
                    quotationListDiv.innerHTML = '<p class="text-center text-danger mt-3 mb-0">Failed to load data.</p>';
                })
                .finally(() => {
                    loadingSpinner.classList.add('d-none');
                    quotationListDiv.style.opacity = '1';
                });
        });
    }

    // Helper function to render the quotation list
    function renderQuotations(quotations, statusFilter = 'all') {
        const quotationListDiv = document.getElementById('quotation-list');
        quotationListDiv.innerHTML = '';
        if (quotations && quotations.length > 0) {
            const ul = document.createElement('ul');
            ul.className = 'list-group list-group-flush';
            quotations.forEach(q => {
                const status = (q.status || 'unknown').toLowerCase();
                let badgeClass = 'secondary';
                if (status === 'approved') badgeClass = 'success';
                else if (['pending', 'in progress', 'draft'].includes(status)) badgeClass = 'warning text-dark';
                else if (['rejected', 'canceled'].includes(status)) badgeClass = 'danger';
                
                ul.innerHTML += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="/mcvpro/public/quotations/view/${q.encoded_id || ''}">
                            ${q.quotation_number || 'N/A'}
                        </a>
                        <span class="badge bg-${badgeClass}">${(q.status || 'unknown').charAt(0).toUpperCase() + (q.status || 'unknown').slice(1)}</span>
                    </li>`;
            });
            quotationListDiv.appendChild(ul);
        } else {
            const message = statusFilter === 'all' 
                ? 'No quotations have been created for this activity yet.'
                : `No quotations found with status "${statusFilter}".`;
            quotationListDiv.innerHTML = `<p class="text-center text-muted mt-3 mb-0">${message}</p>`;
        }
    }

    // --- Initialize all features on the page ---
    initializeDescriptionEdit();
    initializeQuotationFilter();
});

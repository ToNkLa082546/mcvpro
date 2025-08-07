document.addEventListener('DOMContentLoaded', function() {
    // --- 1. Search functionality for All Members Modal ---
    const memberSearchInput = document.getElementById('memberSearch');
    const allMembersTableBody = document.querySelector('#allMembersTable tbody');
    const memberRows = allMembersTableBody ? Array.from(allMembersTableBody.querySelectorAll('tr')) : [];

    if (memberSearchInput) {
        memberSearchInput.addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            memberRows.forEach(row => {
                const memberName = row.querySelector('.view-member-detail')?.textContent.toLowerCase();
                if (memberName && memberName.includes(searchText)) {
                    row.style.display = ''; // Show row
                } else {
                    row.style.display = 'none'; // Hide row
                }
            });
        });
    }

    // --- 2. Display Member Summary Modal ---
    const viewMemberDetailSpans = document.querySelectorAll('.view-member-detail');
    const memberSummaryModal = new bootstrap.Modal(document.getElementById('memberSummaryModal'));
    const memberSummaryBody = document.getElementById('memberSummaryBody');

    viewMemberDetailSpans.forEach(span => {
        span.addEventListener('click', function() {
            try {
                const memberData = JSON.parse(this.dataset.member);
                console.log('Member data:', memberData); // For debugging

                if (memberSummaryBody) {
                    // Build the content for the member summary modal
                    let content = `
                        <p><strong>Member Name:</strong> ${memberData.member_name}</p>
                        <p><strong>Username:</strong> ${memberData.username || 'N/A'}</p>
                        <p><strong>Email:</strong> ${memberData.email || 'N/A'}</p>
                        <p><strong>Role:</strong> ${memberData.role || 'N/A'}</p>
                        <hr>
                        <h6>Quotation Summary:</h6>
                        <ul class="list-group list-group-flush">
                    `;

                    // Check if quotation_details exist and are an array
                    if (memberData.quotation_details && Array.isArray(memberData.quotation_details)) {
                        memberData.quotation_details.forEach(detail => {
                            content += `
                                <li class="list-group-item">
                                    <strong>Quotation ID:</strong> ${detail.quotation_id} <br>
                                    <strong>Customer Name:</strong> ${detail.customer_name} <br>
                                    <strong>Status:</strong> <span class="badge ${getQuotationStatusBadge(detail.status)}">${detail.status}</span> <br>
                                    <strong>Amount:</strong> ${parseFloat(detail.total_amount).toLocaleString('en-US', { style: 'currency', currency: 'USD' })} <br>
                                    <strong>Date:</strong> ${new Date(detail.created_at).toLocaleDateString()}
                                </li>
                            `;
                        });
                    } else {
                        content += `<li class="list-group-item">No detailed quotation data available.</li>`;
                    }

                    content += `</ul>`;
                    memberSummaryBody.innerHTML = content;
                    memberSummaryModal.show(); // Show the modal
                }

            } catch (e) {
                console.error('Error parsing member data or building modal content:', e);
                if (memberSummaryBody) {
                    memberSummaryBody.innerHTML = '<p class="text-danger">Error loading member details. Please check console.</p>';
                    memberSummaryModal.show();
                }
            }
        });
    });

    // Helper function for badge colors (can be expanded)
    function getQuotationStatusBadge(status) {
        switch (status.toLowerCase()) {
            case 'pending':
                return 'bg-warning text-dark';
            case 'approved':
                return 'bg-success';
            case 'rejected':
                return 'bg-danger';
            case 'completed':
                return 'bg-primary';
            default:
                return 'bg-secondary';
        }
    }
});
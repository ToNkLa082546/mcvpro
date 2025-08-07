// staff-dashboard.js (เวอร์ชันแก้ไขที่ถูกต้อง)

$(document).ready(function() {
    // --- ส่วนของการเลือก Element ---
    const filterForm = $('#filter-form');
    const memberTableBody = $('#allMembersTable tbody');
    const dashboardDateRange = $('#dashboard-date-range');
    const totalQuotationsCard = $('#total-quotations-card');
    const totalApprovedCard = $('#total-approved-card');
    const totalMembersCard = $('#total-members-card');
    const startDateInput = $('#start-date');
    const endDateInput = $('#end-date');
    const datePreset = $('#date-range-preset');

    // --- ส่วนของฟังก์ชันหลัก (AJAX) ---
    function fetchAndRenderData(startDate, endDate) {
        memberTableBody.html('<tr><td colspan="8" class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading data...</p></td></tr>');
        
        $.ajax({
            url: window.location.pathname,
            type: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            dataType: 'json',
            success: function(response) {
                // อัปเดตการ์ดสรุปผล
                totalQuotationsCard.text(response.totalQuotations);
                totalApprovedCard.text('฿ ' + Number(response.totalApprovedValue).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                totalMembersCard.text(response.totalMembers);

                // อัปเดตข้อความแสดงช่วงวันที่
                // ใช้ค่าที่ Server ส่งกลับมาเพื่อให้แน่ใจว่าถูกต้อง
                const newRangeText = `Current Range: ${response.dateRange.start} to ${response.dateRange.end}`;
                dashboardDateRange.text(newRangeText);

                // สร้างตารางข้อมูลใหม่
                memberTableBody.empty();
                if (response.memberSummary && response.memberSummary.length > 0) {
                    response.memberSummary.forEach(function(summary) {
                    const row = `
                        <tr>
                            <td class="text-start fw-bold">${escapeHtml(summary.email_user)}</td> // <<< แก้ไขเป็น email_user
                            <td class="text-center">${summary.total_quotations}</td>
                            <td class="text-center">${summary.status_draft}</td>
                            <td class="text-center">${summary.status_pending}</td>
                            <td class="text-center text-success">${summary.status_approval}</td>
                            <td class="text-center text-info">${summary.status_revised}</td>
                            <td class="text-center text-danger">${summary.status_rejected}</td>
                            <td class="text-center text-muted">${summary.status_cancel}</td>
                            <td class="text-end fw-bold text-success">฿ ${Number(summary.total_approved_amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        </tr>
                    `;
                    memberTableBody.append(row);
                });
                } else {
                    memberTableBody.html('<tr><td colspan="8" class="text-center p-4">No data found for the selected period.</td></tr>');
                }
            },
            error: function() {
                memberTableBody.html('<tr><td colspan="8" class="text-center p-4 text-danger">Failed to load data. Please try again.</td></tr>');
            }
        });
    }

    // --- ส่วนของ Event Listeners ---

    // 1. จัดการฟอร์มกรองวันที่
    filterForm.on('submit', function(e) {
        e.preventDefault();
        const startDate = startDateInput.val();
        const endDate = endDateInput.val();
        fetchAndRenderData(startDate, endDate);
    });

    // 2. จัดการเมื่อเลือก Quick Select (This month/Last month)
    datePreset.on('change', function() {
        const selectedValue = $(this).val();
        let startDate, endDate;
        const today = new Date();
        
        if (selectedValue === 'this_month') {
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        } else if (selectedValue === 'last_month') {
            startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            endDate = new Date(today.getFullYear(), today.getMonth(), 0);
        }

        if (selectedValue !== 'custom') {
            startDateInput.val(formatDate(startDate));
            endDateInput.val(formatDate(endDate));
            filterForm.submit(); // สั่งให้ฟอร์มทำงานเพื่อดึงข้อมูลใหม่
        }
    });

    // 3. จัดการการค้นหาตามชื่อ (นำกลับมาอย่างถูกวิธี)
    // เราจะใส่ event listener นี้กับ element ที่มี ID 'memberSearch'
    // ซึ่งจะถูกเพิ่มเข้าไปใน Modal
    $(document).on('keyup', '#memberSearch', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#allMembersTable tbody tr').filter(function() {
            // ค้นหาจาก td แรก (ชื่อ member)
            const memberName = $(this).find('td:first').text().toLowerCase();
            $(this).toggle(memberName.indexOf(searchTerm) > -1);
        });
    });


    // --- ส่วนของ Helper Functions ---
    
    // format date เป็น YYYY-MM-DD
    function formatDate(date) {
        const d = new Date(date);
        let month = '' + (d.getMonth() + 1);
        let day = '' + d.getDate();
        const year = d.getFullYear();
        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;
        return [year, month, day].join('-');
    }

    // ป้องกัน XSS
    function escapeHtml(text) {
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
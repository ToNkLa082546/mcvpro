// ในไฟล์ /mcvpro/public/js/member-dashboard.js

$(document).ready(function() {
    // --- Element Selectors ---
    const filterForm = $('#filter-form');
    const startDateInput = $('#start-date');
    const endDateInput = $('#end-date');
    const datePreset = $('#date-range-preset');
    const dashboardDateRange = $('#dashboard-date-range');

    // --- Function to fetch and update UI ---
    function fetchAndRenderData(startDate, endDate) {
        $.ajax({
            url: window.location.pathname, // ส่งไปยัง URL ปัจจุบัน
            type: 'GET',
            data: { start_date: startDate, end_date: endDate },
            dataType: 'json',
            success: function(response) {
                const stats = response.stats;
                const goal = response.sales_goal;

                // Update summary cards
                $('#total-card').text(stats.total_quotations);
                $('#draft-card').text(stats.draft_quotations);
                $('#approved-card').text(stats.approved_quotations);
                $('#rejected-card').text(stats.rejected_quotations);
                $('#canceled-card').text(stats.canceled_quotations);

                // Update approved sum and progress bar
                const approvedSum = Number(stats.approved_sum_in_range);
                const progressPercentage = (goal > 0) ? (approvedSum / goal) * 100 : 0;
                
                $('#approved-sum-text').text('฿ ' + approvedSum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                
                const progressBar = $('#sales-progress-bar');
                progressBar.css('width', Math.min(progressPercentage, 100) + '%');
                progressBar.attr('aria-valuenow', progressPercentage);
                progressBar.text(Math.round(progressPercentage) + '%');

                // Update goal message
                const goalMessage = $('#goal-message');
                if (approvedSum >= goal) {
                    goalMessage.html('<i class="fas fa-trophy me-2"></i>Congratulations! Goal achieved!').removeClass('text-warning').addClass('text-success');
                } else {
                    goalMessage.html('<i class="fas fa-running me-2"></i>Keep going! You\'re almost there.').removeClass('text-success').addClass('text-warning');
                }

                // Update date range display text
                dashboardDateRange.text(`Showing data for: ${response.dateRange.start} - ${response.dateRange.end}`);
            },
            error: function() {
                alert('Failed to load dashboard data. Please try again.');
            }
        });
    }

    // --- Event Listeners ---
    filterForm.on('submit', function(e) {
        e.preventDefault();
        fetchAndRenderData(startDateInput.val(), endDateInput.val());
    });

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
            filterForm.submit();
        }
    });

     function formatDate(date) {
        const d = new Date(date);
        let month = '' + (d.getMonth() + 1);
        let day = '' + d.getDate();
        const year = d.getFullYear();
        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;
        return [year, month, day].join('-');
    }

    // --- Initial Load Logic ---
    // ✅ สร้างฟังก์ชันสำหรับอัปเดตข้อความ Goal เพื่อไม่ให้เขียนโค้ดซ้ำ
    function updateGoalMessage(currentSum, goal) {
        const goalMessage = $('#goal-message');
        if (goal > 0 && currentSum >= goal) {
            goalMessage.html('<i class="fas fa-trophy me-2"></i>Congratulations! Goal achieved!').removeClass('text-warning').addClass('text-success');
        } else {
            goalMessage.html('<i class="fas fa-running me-2"></i>Keep going! You\'re almost there.').removeClass('text-success').addClass('text-warning');
        }
    }

    // ✅ อ่านค่าเริ่มต้นจาก DOM ตอนที่หน้าเว็บโหลดเสร็จ
    const salesGoal = Number($('.container').data('goal')) || 0;
    const initialApprovedSum = Number($('#approved-sum-text').text().replace(/[^0-9.-]+/g,""));

    // ✅ เรียกใช้ฟังก์ชันเพื่อแสดงข้อความ Goal ที่ถูกต้องตั้งแต่แรก
    updateGoalMessage(initialApprovedSum, salesGoal);
});
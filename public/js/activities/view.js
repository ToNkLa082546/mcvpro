$(document).ready(function() {

    // --- ประกาศตัวแปรที่ใช้บ่อยไว้ด้านบน ---
    const descriptionView = $('#descriptionView');
    const descriptionForm = $('#descriptionForm');
    const editor = $('#descriptionEditor');
    const feedbackDiv = $('#description-feedback');
    const editBtn = $('#editBtn');
    const cancelBtn = $('#cancelBtn');
    const saveBtn = descriptionForm.find('button[type="submit"]');

    // --- Initialize Summernote ---
    editor.summernote({
        placeholder: 'กรอกรายละเอียดที่นี่...',
        height: 200,
        toolbar: [
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['fontname', ['fontname']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });
    
    // --- สลับไปโหมดแก้ไข ---
    editBtn.on('click', function() {
        descriptionView.hide();
        descriptionForm.show();
        // ทำให้ editor focus ทันทีที่เปิด
        editor.summernote('focus');
    });

    // --- สลับไปโหมดแสดงผล (ยกเลิก) ---
    cancelBtn.on('click', function() {
        descriptionForm.hide();
        descriptionView.show();
        feedbackDiv.html('').removeClass(); // ล้างข้อความ feedback

        // Reset เนื้อหาใน editor ให้กลับไปเป็นเหมือนเดิมก่อนแก้
        // โค้ดเดิมส่วนนี้ทำงานถูกต้องแล้ว คือดึงจาก View มาใส่
        editor.summernote('code', descriptionView.html());
    });

    // --- จัดการการ Submit Form (บันทึกข้อมูล) ---
    descriptionForm.on('submit', function(event) {
        event.preventDefault(); // ป้องกันการโหลดหน้าใหม่

        const newDescription = editor.summernote('code');
        const currentActivityId = $(this).data('activity-id');
        const originalButtonHtml = saveBtn.html(); // เก็บ HTML เดิมของปุ่มไว้

        // --- UX Improvement: ปิดการใช้งานปุ่มและแสดงสถานะกำลังโหลด ---
        saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        cancelBtn.prop('disabled', true);
        feedbackDiv.text('').removeClass(); // ล้าง feedback เก่า

        $.ajax({
            url: '/mcvpro/public/activities/updateDescription', // <-- URL ของคุณ
            type: 'POST',
            data: {
                activity_id: currentActivityId,
                description: newDescription
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // อัปเดตเนื้อหาที่แสดงผล
                    descriptionView.html(newDescription);
                    
                    // กลับไปที่ View mode
                    descriptionForm.hide();
                    descriptionView.show();

                    // แสดงข้อความว่าสำเร็จ
                    feedbackDiv.html('Saved successfully!').attr('class', 'text-start mb-2 text-success small');
                    setTimeout(() => { feedbackDiv.html('').removeClass(); }, 3000);
                } else {
                    // แสดงข้อความ Error จาก Server
                    feedbackDiv.html('Error: ' + (response.message || 'Could not save.')).attr('class', 'text-start mb-2 text-danger small');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error:', textStatus, errorThrown);
                feedbackDiv.html('A network or server error occurred.').attr('class', 'text-start mb-2 text-danger small');
            },
            complete: function() {
                // --- ส่วนนี้จะทำงานเสมอ ไม่ว่า success หรือ error ---
                // คืนค่าปุ่มให้กลับมาคลิกได้และเป็นเหมือนเดิม
                saveBtn.prop('disabled', false).html(originalButtonHtml);
                cancelBtn.prop('disabled', false);
            }
        });
    });


    // ==========================================================
    // === Feature 2: Filter Quotations (jQuery Version)
    // ==========================================================
    function initializeQuotationFilter() {
        const statusFilter = $('#status-filter');
        const quotationListDiv = $('#quotation-list');
        const loadingSpinner = $('#loading-spinner');

        renderQuotations(initialQuotations);

        statusFilter.on('change', function() {
            const selectedStatus = $(this).val();
            loadingSpinner.removeClass('d-none');
            quotationListDiv.css('opacity', '0.5');

            $.ajax({
                url: `/mcvpro/public/activities/getFilteredQuotations/${activityId}/${selectedStatus}`,
                type: 'GET',
                dataType: 'json',
                success: function(quotations) {
                    renderQuotations(quotations, selectedStatus);
                },
                error: function(err) {
                    console.error('Error fetching filtered quotations:', err);
                    quotationListDiv.html('<p class="text-center text-danger mt-3 mb-0">Failed to load data.</p>');
                },
                complete: function() {
                    loadingSpinner.addClass('d-none');
                    quotationListDiv.css('opacity', '1');
                }
            });
        });
    }

    function renderQuotations(quotations, statusFilter = 'all') {
        const quotationListDiv = $('#quotation-list');
        quotationListDiv.empty(); // ใช้ .empty() ของ jQuery

        if (quotations && quotations.length > 0) {
            const ul = $('<ul>').addClass('list-group list-group-flush');
            $.each(quotations, function(index, q) {
                const status = (q.status || 'unknown').toLowerCase();
                let badgeClass = 'secondary';
                if (status === 'approved') badgeClass = 'success';
                else if (['pending', 'in progress', 'draft'].includes(status)) badgeClass = 'warning text-dark';
                else if (['rejected', 'canceled'].includes(status)) badgeClass = 'danger';

                const li = `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="/mcvpro/public/quotations/view/${q.encoded_id || ''}">
                            ${q.quotation_number || 'N/A'}
                        </a>
                        <span class="badge bg-${badgeClass}">${(q.status || 'unknown').charAt(0).toUpperCase() + (q.status || 'unknown').slice(1)}</span>
                    </li>`;
                ul.append(li);
            });
            quotationListDiv.append(ul);
        } else {
            const message = statusFilter === 'all' 
                ? 'No quotations have been created for this activity yet.'
                : `No quotations found with status "${statusFilter}".`;
            quotationListDiv.html(`<p class="text-center text-muted mt-3 mb-0">${message}</p>`);
        }
    }

    // --- Initialize all features on the page ---
    initializeDescriptionEdit();
    initializeQuotationFilter();

});
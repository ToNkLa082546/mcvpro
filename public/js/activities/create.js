$(document).ready(function() {

    // --- ประกาศตัวแปรหลักที่ใช้ในหน้านี้ ---
    const descriptionEditor = $('#description');
    const customerSelect = $('#customer_id');
    const projectSelect = $('#project_id');
    const mainForm = $('form'); // อ้างอิงถึงฟอร์มหลัก

    // ==========================================================
    // === 1. Initialize Summernote Editor
    // ==========================================================
    descriptionEditor.summernote({
        placeholder: 'Enter activity description here...',
        height: 250, // เพิ่มความสูงเล็กน้อย
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['fontname', ['fontname']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });

    // ==========================================================
    // === 2. Dependent Dropdowns for Customer & Project
    // ==========================================================
    customerSelect.on('change', function() {
        const customerId = $(this).val();
        
        projectSelect.prop('disabled', true); // ปิดการใช้งานขณะโหลด

        if (customerId) {
            projectSelect.html('<option value="">Loading projects...</option>');

            // ใช้ Ajax เพื่อดึงโปรเจกต์ของลูกค้าที่เลือก
            $.ajax({
                // หมายเหตุ: ตรวจสอบให้แน่ใจว่า URL นี้ถูกต้องตามโครงสร้างโปรเจกต์ของคุณ
                url: '/mcvpro/public/projects/getProjectsByCustomer/' + customerId, 
                type: 'GET',
                dataType: 'json',
                success: function(projects) {
                    projectSelect.prop('disabled', false);
                    projectSelect.empty();
                    
                    if (projects.length > 0) {
                        projectSelect.append('<option value="" disabled selected>-- Select a project --</option>');
                        $.each(projects, function(index, project) {
                            projectSelect.append($('<option>', {
                                value: project.project_id,
                                text: project.project_name
                            }));
                        });
                    } else {
                        projectSelect.append('<option value="" disabled selected>-- No projects found for this customer --</option>');
                    }
                },
                error: function() {
                    projectSelect.html('<option value="" disabled selected>-- Error loading projects --</option>');
                    console.error('Failed to load projects for customer ' + customerId);
                }
            });
        } else {
            // ถ้าไม่ได้เลือก customer ให้ reset project dropdown
            projectSelect.html('<option value="" selected>-- Please select a customer first --</option>');
        }
    });

    // ==========================================================
    // === 3. Form Validation before Submission
    // ==========================================================
    mainForm.on('submit', function(event) {
        // ตรวจสอบว่า Summernote editor ว่างเปล่าหรือไม่
        if (descriptionEditor.summernote('isEmpty')) {
            // ถ้าว่าง ให้ยกเลิกการส่งฟอร์ม
            event.preventDefault(); 
            
            // แจ้งเตือนผู้ใช้
            alert('Please provide a description for the activity.');
            
            // Focus ที่ editor เพื่อให้ผู้ใช้กรอกข้อมูลง่ายขึ้น
            descriptionEditor.summernote('focus');
        }
        // ถ้าไม่ว่าง ก็ปล่อยให้ฟอร์ม submit ตามปกติ
    });

});
document.addEventListener('DOMContentLoaded', function() {
    
    // เรียกใช้ฟังก์ชันตั้งค่าสำหรับแต่ละส่วนของหน้า
    setupProjectFilter();
    setupAjaxFormLoader();
    setupUploadModal();
    setupMyFilesModal();
    // ****** เพิ่มโค้ดสำหรับลบไฟล์ตรงนี้ ******
    document.body.addEventListener('click', function(event) {
        const deleteButton = event.target.closest('.delete-file-btn');

        if (deleteButton) {
            console.log('Main script: Delete button clicked!');

            const fileId = deleteButton.dataset.fileId;
            console.log('Main script: File ID to delete:', fileId);

            if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบไฟล์นี้? การดำเนินการนี้ไม่สามารถย้อนกลับได้!')) {
                console.log('Main script: User confirmed deletion. Sending fetch request.');

                fetch(`/mcvpro/public/files/delete/${fileId}`, { // Path นี้ถูกต้องแล้ว
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Main script: Server response:', data);
                    if (data.status === 'success') {
                        alert('ลบไฟล์สำเร็จ!');
                        deleteButton.closest('.list-group-item').remove();

                        const fileListContainer = document.querySelector('.list-group');
                        if (fileListContainer && fileListContainer.children.length === 0) {
                            const parentOfList = fileListContainer.parentElement;
                            if (parentOfList) {
                                parentOfList.innerHTML = '<p class="text-center text-muted">คุณยังไม่ได้อัปโหลดไฟล์ใดๆ เลย</p>';
                            }
                        }
                    } else {
                        alert('เกิดข้อผิดพลาดในการลบไฟล์: ' + (data.message || 'ไม่ทราบสาเหตุ'));
                        console.error('Server error:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อหรือประมวลผล: ' + error.message);
                });
            } else {
                console.log('Main script: User cancelled deletion.');
            }
        }
    });

});

/**
 * ตั้งค่าฟิลเตอร์โปรเจกต์ที่ขึ้นกับลูกค้า
 */
function setupProjectFilter() {
    const customerSelect = document.getElementById('customer_id');
    const projectContainer = document.getElementById('project-filter-container');
    const projectSelect = document.getElementById('project_id');
    
    if (!customerSelect || !projectContainer || !projectSelect) return;

    const allProjectOptions = Array.from(projectSelect.options);
    function toggleProjectFilter() {
        const selectedCustomerId = customerSelect.value;
        projectContainer.style.display = selectedCustomerId ? 'block' : 'none';
        if (!selectedCustomerId) {
            projectSelect.value = '';
            return;
        }
        projectSelect.innerHTML = '';
        projectSelect.appendChild(allProjectOptions[0].cloneNode(true));
        allProjectOptions.forEach(option => {
            if (option.dataset.customerId === selectedCustomerId) {
                projectSelect.appendChild(option.cloneNode(true));
            }
        });
    }
    toggleProjectFilter();
    customerSelect.addEventListener('change', toggleProjectFilter);
}

/**
 * ตั้งค่าการโหลดฟอร์ม Duplicate แบบ AJAX
 */
function setupAjaxFormLoader() {
    const formContainer = document.getElementById('quotation-form-container');
    if (!formContainer) return;

    document.body.addEventListener('click', function(event) {
        const duplicateButton = event.target.closest('a.duplicate-quote-btn');
        if (duplicateButton) {
            event.preventDefault();
            const url = duplicateButton.href;
            const fetchUrl = url.replace('/duplicate/', '/getFormForDuplicate/');
            formContainer.innerHTML = '<div class="text-center p-5"><div class="spinner-border" role="status"></div></div>';
            
            fetch(fetchUrl)
                .then(response => response.text())
                .then(html => {
                    formContainer.innerHTML = html;
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    tempDiv.querySelectorAll('script').forEach(oldScript => {
                        const newScript = document.createElement('script');
                        Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                        newScript.textContent = oldScript.textContent;
                        document.body.appendChild(newScript).parentNode.removeChild(newScript);
                    });
                    formContainer.scrollIntoView({ behavior: 'smooth' });
                })
                .catch(error => console.error('Failed to load form:', error));
        }
    });
}

/**
 * ตั้งค่าการทำงานของ Upload Modal (ส่วนที่แก้ไขปัญหา)
 */
function setupUploadModal() {
    const uploadModalEl = document.getElementById('uploadModal');
    if (!uploadModalEl) return;

    const uploadForm = document.getElementById('file-upload-form');
    const uploadButton = document.getElementById('submit-upload-btn');
    const fileInput = document.getElementById('file-upload-input');
    const uploadModal = new bootstrap.Modal(uploadModalEl);

    if (!uploadButton || !uploadForm || !fileInput) return;

    uploadButton.addEventListener('click', function(event) {
        event.preventDefault();

        // Client-side validation
        const allowedExtensions = ['pdf', 'zip'];
        for (const file of fileInput.files) {
            const fileExtension = file.name.split('.').pop().toLowerCase();
            if (!allowedExtensions.includes(fileExtension)) {
                alert(`Error: File "${file.name}" is not allowed. Only PDF and ZIP files are accepted.`);
                fileInput.value = '';
                return;
            }
        }
        
        const formData = new FormData(uploadForm);
        uploadButton.disabled = true;
        uploadButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Uploading...`;

        fetch(uploadForm.action, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Files uploaded successfully!');
                    uploadModal.hide();
                    uploadForm.reset();
                } else {
                    alert('Error: ' + (data.message || 'Upload failed.'));
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                alert('A network error occurred.');
            })
            .finally(() => {
                uploadButton.disabled = false;
                uploadButton.innerHTML = 'Upload';
            });
    });
}

/**
 * ตั้งค่าการทำงานของ My Files Modal
 */
function setupMyFilesModal() {
    const myFilesModalEl = document.getElementById('myFilesModal');
    if (!myFilesModalEl) return;

    myFilesModalEl.addEventListener('show.bs.modal', function () {
        const fileListContainer = document.getElementById('my-files-list-container');
        fileListContainer.innerHTML = '<div class="text-center p-4"><div class="spinner-border"></div></div>';
        fetch('/mcvpro/public/files/myFiles')
            .then(response => response.text())
            .then(html => {
                fileListContainer.innerHTML = html;
            })
            .catch(error => console.error('Error fetching files:', error));
    });
}
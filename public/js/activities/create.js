
    document.addEventListener('DOMContentLoaded', function() {
        const customerSelect = document.getElementById('customer_id');
        const projectSelect = document.getElementById('project_id');
        
        customerSelect.addEventListener('change', function() {
            const selectedCustomerId = this.value;
            
            projectSelect.innerHTML = '<option value="" selected>Loading...</option>';
            projectSelect.disabled = true;

            if (selectedCustomerId) {
                const apiUrl = `/mcvpro/public/activities/getProjectsByCustomerJson/${selectedCustomerId}`;

                fetch(apiUrl)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(responseObject => { // เปลี่ยนชื่อตัวแปรเป็น responseObject เพื่อความชัดเจน
                        projectSelect.innerHTML = ''; 

                        // --- จุดแก้ไข ---
                        // เข้าถึงข้อมูลโปรเจกต์ผ่าน responseObject.data
                        const projects = responseObject.data; 

                        if (projects && projects.length > 0) {
                            projectSelect.add(new Option('-- Please select a project --', ''));
                            projects.forEach(project => {
                                projectSelect.add(new Option(project.project_name, project.project_id));
                            });
                            projectSelect.disabled = false;
                        } else {
                            projectSelect.innerHTML = '<option value="" selected>-- No projects available --</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching projects:', error);
                        projectSelect.innerHTML = '<option value="" selected>-- Error loading projects --</option>';
                    });
            } else {
                projectSelect.innerHTML = '<option value="" selected>-- Please select a customer first --</option>';
            }
        });
    });

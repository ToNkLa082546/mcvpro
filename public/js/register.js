document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const statusMessage = document.getElementById('password-match-status');

            function validatePasswords() {
                if (confirmPassword.value === '') {
                    statusMessage.textContent = '';
                    confirmPassword.classList.remove('is-valid', 'is-invalid');
                    return;
                }

                if (password.value === confirmPassword.value) {
                    statusMessage.textContent = '✅ รหัสผ่านตรงกัน';
                    statusMessage.style.color = 'green';
                    confirmPassword.classList.remove('is-invalid');
                    confirmPassword.classList.add('is-valid');
                } else {
                    statusMessage.textContent = '❌ รหัสผ่านไม่ตรงกัน';
                    statusMessage.style.color = 'red';
                    confirmPassword.classList.remove('is-valid');
                    confirmPassword.classList.add('is-invalid');
                }
            }

            // ตรวจสอบทุกครั้งที่มีการพิมพ์
            password.addEventListener('keyup', validatePasswords);
            confirmPassword.addEventListener('keyup', validatePasswords);

            // ป้องกันการส่งฟอร์มถ้ารหัสไม่ตรงกัน
            form.addEventListener('submit', function(event) {
                if (password.value !== confirmPassword.value) {
                    event.preventDefault(); // หยุดการส่งฟอร์ม
                    alert('กรุณายืนยันรหัสผ่านให้ถูกต้อง');
                    confirmPassword.focus();
                }
            });
        });
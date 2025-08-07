document.addEventListener('DOMContentLoaded', function() {
    const cooldownBtn = document.getElementById('cooldown-btn');

    if (cooldownBtn) {
        const cooldownEndTime = parseInt(cooldownBtn.dataset.cooldownEnd, 10);

        const timerInterval = setInterval(function() {
            const now = Math.floor(Date.now() / 1000);
            const secondsRemaining = cooldownEndTime - now;

            if (secondsRemaining > 0) {
                const minutes = Math.floor(secondsRemaining / 60);
                const seconds = secondsRemaining % 60;
                // จัดรูปแบบเวลาให้เป็น 00:00
                const displayTime = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                
                cooldownBtn.innerHTML = `<i class="fas fa-hourglass-half"></i> Please wait ${displayTime}`;
            } else {
                // เมื่อหมดเวลา Cooldown
                clearInterval(timerInterval);
                cooldownBtn.disabled = false;
                cooldownBtn.classList.remove('btn-secondary');
                cooldownBtn.classList.add('btn-info');
                cooldownBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send for Approval';

                // ทำให้ปุ่มที่ปลดล็อคแล้วกลายเป็นปุ่ม submit ได้
                cooldownBtn.type = 'submit';
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/mcvpro/public/quotations/request-approval/<?= $q['quotation_id'] ?>`;
                cooldownBtn.parentNode.insertBefore(form, cooldownBtn);
                form.appendChild(cooldownBtn);
                
            }
        }, 1000);
    }
});
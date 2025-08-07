document.addEventListener('DOMContentLoaded', function() {
    const notificationLinks = document.querySelectorAll('a.notification-item');

    notificationLinks.forEach(link => {
        link.addEventListener('click', function(event) {
             console.log('Notification link clicked! ID:', this.dataset.notificationId);
            event.preventDefault();
            const notificationItem = this; // เก็บ <a> ที่ถูกคลิก
            const notificationId = notificationItem.dataset.notificationId;
            const destinationUrl = notificationItem.href;

            // ส่ง Request ไปเบื้องหลัง
            fetch('/mcvpro/public/notifications/read/' + notificationId, {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
})
.then(response => {
    // โค้ดนี้จะทำงานเมื่อเซิร์ฟเวอร์ตอบกลับสำเร็จ
    if (response.ok) {
        console.log('✅ Server response is OK. Now attempting to update UI.');

        // ตรวจสอบว่าหา badge เจอหรือไม่
        const badge = document.getElementById('notification-badge');
        console.log('Badge element found:', !!badge); // ค่าจะเป็น true ถ้าเจอ, false ถ้าไม่เจอ
        
        if (badge) {
            let currentCount = parseInt(badge.textContent);
            let newCount = currentCount - 1;
            console.log(`Current count is ${currentCount}, new count will be ${newCount}.`);

            if (newCount > 0) {
                badge.textContent = newCount;
                console.log('Updated badge count to:', newCount);
            } else {
                badge.remove();
                console.log('Badge count is 0, removed badge element.');
            }
        }
        
        // ลบรายการที่คลิกออกจาก dropdown
        const listItem = notificationItem.closest('li');
        console.log('Attempting to remove list item:', listItem);
        if (listItem) {
            listItem.remove();
            console.log('List item removed successfully.');
        }

    } else {
        console.error('❌ Server response was not OK:', response.status);
    }
})
.catch(error => console.error('Error during fetch:', error))
.finally(() => {
    // Redirect ไปหน้าปลายทาง
    window.location.href = destinationUrl;
});
        });
    });
});

document.getElementById('clear-notifications')?.addEventListener('click', function () {
    if (confirm('คุณต้องการล้างการแจ้งเตือนทั้งหมดหรือไม่?')) {
      // ส่ง request ไปล้างการแจ้งเตือน (AJAX หรือ link)
      fetch('/mcvpro/public/notifications/clear', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        }
      }).then(response => {
        if (response.ok) {
          // อัปเดต UI
          document.getElementById('notification-dropdown').innerHTML = `
            <li class="dropdown-item-text text-muted text-center py-2">ไม่มีการแจ้งเตือนใหม่</li>
          `;
          const badge = document.getElementById('notification-badge');
          if (badge) badge.remove();
        }
      });
    }
  });
document.addEventListener('DOMContentLoaded', function() {
    
    // ฟังก์ชันสำหรับอัปเดตตัวเลขใน Stat Cards
    function updateStatCards(stats) {
        document.getElementById('total-users').textContent = stats.total_users;
        document.getElementById('total-customers').textContent = stats.total_customers;
        document.getElementById('total-projects').textContent = stats.total_projects;
        document.getElementById('unassigned-projects').textContent = stats.unassigned_projects;
    }

    // ฟังก์ชันสำหรับสร้างหรืออัปเดตกราฟ
    function renderProjectChart(chartData) {
        const ctx = document.getElementById('projectStatusChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: '# of Projects',
                    data: chartData.counts,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // เรียก API เพื่อดึงข้อมูลทั้งหมด
    fetch('/mcvpro/public/admin/stats')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(result => {
            if (result.success) {
                updateStatCards(result.stats);
                renderProjectChart(result.chart);
            } else {
                console.error('Failed to load dashboard data:', result.error);
            }
        })
        .catch(error => console.error('Error fetching dashboard data:', error));
});
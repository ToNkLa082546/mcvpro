<?php
// คำนวณค่าสำหรับ Pagination จากข้อมูลที่ Controller ส่งมา
$total_pages = ceil($data['total_quotations'] / $data['per_page']);
$current_page = $data['current_page'];
$statuses = ['Draft', 'Pending Approval', 'Approved', 'Rejected', 'Revised', 'Sent', 'Cancel'];

// รับค่าจาก URL มาเก็บในตัวแปรเพื่อง่ายต่อการใช้งาน
$searchQuery = $_GET['search'] ?? '';
$selectedStatus = $_GET['status'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>All Quotations</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/mcvpro/public/css/quotation-list.css">

</head>
<body style="background-color: #FFF5EE;">


<div class="container main-content my-5">
    


        

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header custom-header d-flex justify-content-between align-items-center">
    <h4 class="mb-0"><i class="fa-solid fa-diagram-project me-2"></i>Quotations</h4>
    <a href="/mcvpro/public/quotations/create" class="btn btn-light shadow-sm">
        <i class="fas fa-plus"></i> Create New
    </a>
</div>

        <div class="card-body">
            
            <form method="GET" action="/mcvpro/public/quotations">
    <div class="row g-3 align-items-end">
        
        <div class="col-md-3">
            <label for="search" class="form-label">Search</label>
            <input type="text" name="search" id="search" class="form-control" 
                   placeholder="Quote #, Customer, Project..." 
                   value="<?= htmlspecialchars($searchQuery) ?>">
        </div>

        <div class="col-md-2">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select">
                <option value="">All Statuses</option>
                <?php foreach ($statuses as $status) : ?>
                    <option value="<?= $status ?>" <?= ($selectedStatus === $status) ? 'selected' : '' ?>>
                        <?= $status ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label for="start_date" class="form-label">From Date</label>
            <input type="date" name="start_date" id="start_date" class="form-control" 
                   value="<?= htmlspecialchars($startDate) ?>">
        </div>
        <div class="col-md-2">
            <label for="end_date" class="form-label">To Date</label>
            <input type="date" name="end_date" id="end_date" class="form-control" 
                   value="<?= htmlspecialchars($endDate) ?>">
        </div>

        <div class="col-md-3 d-flex">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-search"></i> Filter</button>
            <a href="/mcvpro/public/quotations" class="btn btn-outline-secondary ms-2" title="Clear Filters"><i class="fas fa-times"></i></a>
        </div>

    </div>
</form>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Quotation #</th>
                            <th>Customer</th>
                            <th>Project</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Status</th>
                            <th>Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['quotations'])): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No quotations found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['quotations'] as $q): 
                                // ตั้งค่าสีของ Badge ตาม Status
                                $status_classes = [
                                    'Draft' => 'bg-secondary',
                                    'Pending Approval' => 'bg-info text-dark',
                                    'Approved' => 'bg-success',          
                                    'Rejected' => 'bg-danger',
                                    'Revised' => 'bg-warning text-dark',
                                    'Sent' => 'bg-primary',            
                                    'Cancel' => 'bg-dark',               
                                ];
                                $status_class = $status_classes[$q['status']] ?? 'bg-light text-dark';
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($q['quotation_number']) ?></strong></td>
                                    <td><a class="naked-link" href="/mcvpro/public/customers/view/<?= encodeId($q['customer_id']) ?>?from=list">
                                        <?= htmlspecialchars($q['company_name']) ?>
                                    </a>
                                    </td>
                                    <td><a class="naked-link" href="/mcvpro/public/customers/view/<?= encodeId($q['project_id']) ?>?from=list">
                                        <?= htmlspecialchars($q['project_name']) ?>
                                    </a>
                                    </td>
                                    <td class="text-end"><?= number_format($q['grand_total'], 2) ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $status_class ?>"><?= htmlspecialchars($q['status']) ?></span>
                                    </td>
                                    <td><?= date("d M Y", strtotime($q['created_at'])) ?></td>
                                    <td class="text-center">
                                        <a href="/mcvpro/public/quotations/view/<?= encodeId($q['quotation_id']) ?>" class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], [1, 2])): ?>
                                            <a href="/mcvpro/public/quotations/edit/<?= encodeId($q['quotation_id']) ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                            <a href="/mcvpro/public/quotations/delete/<?= encodeId($q['quotation_id']) ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this quotation?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
<nav aria-label="Quotation pagination">
    <ul class="pagination justify-content-center mt-3">

        <?php
        // --- 1. เตรียม Query String เดิม (สำหรับใช้กับฟังก์ชัน filter, search ในอนาคต) ---
        $queryParams = $_GET;
        unset($queryParams['page']); // เอา page ปัจจุบันออก
        $queryString = http_build_query($queryParams);
        $baseUrl = '?' . ($queryString ? $queryString . '&' : '');
        ?>

        <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $baseUrl ?>page=<?= $current_page - 1 ?>">&laquo;</a>
        </li>

        <?php
 
        $window = 2; // จำนวนหน้าที่จะแสดงรอบๆ หน้า ปัจจุบัน

        // หน้าแรกสุด
        if ($current_page > $window + 1) {
            echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . 'page=1">1</a></li>';
            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }

        // ตัวเลขหน้าในช่วง
        for ($i = max(1, $current_page - $window); $i <= min($total_pages, $current_page + $window); $i++) {
            $activeClass = ($i === $current_page) ? 'active' : '';
            echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="' . $baseUrl . 'page=' . $i . '">' . $i . '</a></li>';
        }
        
        // หน้าสุดท้าย
        if ($current_page < $total_pages - $window) {
            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . 'page=' . $total_pages . '">' . $total_pages . '</a></li>';
        }
        ?>

        <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $baseUrl ?>page=<?= $current_page + 1 ?>">&raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>
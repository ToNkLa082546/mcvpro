<?php
$totalPages = $data['totalPages'] ?? 1;
$page = $data['currentPage'] ?? 1;
?>


<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer List</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/mcvpro/public/css/customer.css">
    
</head>

<body style="background-color: #FFF5EE;">
    <div class="container my-5">
        <div class="card shadow-lg">
            <div class="card-header py-3 px-4">
                <h4><i class="fa-solid fa-building-user me-2"></i>Customer List</h4>
                <a href="/mcvpro/public/customers/create" class="btn btn-light border">
                    <i class="fas fa-plus"></i> Add New Customer
                </a>
            </div>

            <div class="card-body">
                <form method="GET" action="/mcvpro/public/customers" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Company Name</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Search by company name..."
                                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>

                        <div class="col-md-2">
                            <label for="start_date" class="form-label">From Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                        </div>

                        <div class="col-md-2">
                            <label for="end_date" class="form-label">To Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="/mcvpro/public/customers" class="btn btn-outline-secondary ms-2" title="Clear Filters">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Create Date</th>
                                <th>Create By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fa-solid fa-circle-exclamation me-2"></i>Customer information not found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($customer['company_name']) ?></td>
                                        <td class="text-nowrap">
                                            <?= $customer['created_at'] ? date("d F Y, H:i", strtotime($customer['created_at'])) : '-' ?>
                                        </td>
                                        <td><?= htmlspecialchars($customer['created_by_display'] ?? '-') ?></td>
                                        <td class="text-center action-btns">
                                            <a href="/mcvpro/public/customers/view/<?= encodeId($customer['customer_id']) ?>"
                                                class="btn btn-outline-info btn-sm" title="Details">
                                                <i class="fas fa-eye"></i>
                                                
                                            </a>
                                            <?php if (in_array($_SESSION['user_role'], [2, 3])): ?>
                                                <a href="/mcvpro/public/customers/delete/<?= $customer['customer_id'] ?>"
                                                    class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบลูกค้ารายนี้?');"
                                                    title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center mt-4">
                        <!-- Previous -->
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                &laquo;
                            </a>
                        </li>

                        <!-- Page numbers -->
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next -->
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                &raquo;
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
                </div>
            </div>

            <div class="card-footer text-end text-muted px-4 py-3">
                Showing <?= count($customers) ?> customers
            </div>
        </div>
    </div>

</body>

</html>

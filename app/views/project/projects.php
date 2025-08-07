<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project List (Customer)</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/mcvpro/public/css/project.css">
</head>


<body style="background-color: #FFF5EE;">
    <div class="container my-5">
        <div class="card shadow-lg">
            <div class="card-header py-3 px-4">
                <h4><i class="fa-solid fa-diagram-project me-2"></i>Project List</h4>
                <a href="/mcvpro/public/projects/create" class="btn btn-light shadow-sm">
                    <i class="fas fa-plus"></i> Add New Project
                </a>
            </div>

            <?php if (isset($_SESSION['success'])) : ?>
                <div class="alert alert-success alert-dismissible fade show m-4 mt-3 mb-0" role="alert">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card-body">
                <form method="GET" action="/mcvpro/public/projects" class="mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control"
                                   placeholder="Project Name, Company Name..."
                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>

                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Statuses</option>
                                <?php
                                $statuses = ['Pending', 'Approved', 'Closed'];
                                $selectedStatus = $_GET['status'] ?? '';
                                foreach ($statuses as $status) {
                                    $selected = ($selectedStatus === $status) ? 'selected' : '';
                                    echo "<option value='{$status}' {$selected}>{$status}</option>";
                                }
                                ?>
                            </select>
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

                        <div class="col-md-1 d-flex">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="/mcvpro/public/projects" class="btn btn-outline-secondary ms-2" title="Clear Filters">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Company Name</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th>Create By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($projects)) : ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">🚫 Project not found</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($projects as $project) : ?>
                                    <?php
                                    $status = strtolower($project['status']);
                                    $badgeClass = match ($status) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'closed' => 'secondary',
                                        default => 'dark'
                                    };
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($project['project_name']) ?></td>
                                        <td><?= htmlspecialchars($project['company_name']) ?></td>
                                        <td class="text-end"><?= number_format($project['project_price'], 2) ?> ฿</td>
                                        <td>
                                            <span class="badge bg-<?= $badgeClass ?> badge-status">
                                                <?= htmlspecialchars($project['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($project['created_by_name']) ?></td>
                                        <td class="text-center action-btns">
                                            <a href="/mcvpro/public/projects/view/<?= encodeId($project['project_id']) ?>" class="btn btn-outline-info btn-sm" title="Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/mcvpro/public/projects/delete/<?= encodeId($project['project_id']) ?>" class="btn btn-outline-danger btn-sm" title="Delete"
                                               onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบโปรเจกต์นี้?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php
                    // คำนวณค่าจาก Controller
                    $total_pages = ceil($data['total_projects'] / $data['per_page']);
                    $current_page = $data['current_page'];

                    if ($total_pages > 1): 
                    ?>
                    <nav aria-label="Project pagination">
                        <ul class="pagination justify-content-center mt-4">
                            <?php
                            // เตรียม Query String ของฟิลเตอร์ปัจจุบันเพื่อใช้ในลิงก์
                            $queryParams = $_GET;
                            unset($queryParams['page']);
                            $queryString = http_build_query($queryParams);
                            $baseUrl = '?' . ($queryString ? $queryString . '&' : '');
                            ?>

                            <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $baseUrl ?>page=<?= $current_page - 1 ?>">&laquo;</a>
                            </li>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= $baseUrl ?>page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $baseUrl ?>page=<?= $current_page + 1 ?>">&raquo;</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-footer text-end text-muted px-4 py-3">
                Showing <?= count($projects) ?> project(s)
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>


</html>

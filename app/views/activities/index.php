<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Activity List</title>
  <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/mcvpro/public/css/act_index.css">
</head>
<body style="background-color: #FFF5EE;">
<div class="container my-5">
  <div class="card shadow-sm">
    <div class="card-header custom-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0"><i class="fa-solid fa-diagram-project me-2"></i>Activity</h4>
      <a href="/mcvpro/public/activities/create" class="btn btn-light shadow-sm rounded-pill">
        <i class="fas fa-plus"></i> Create New
      </a>
    </div>

    <div class="card-body">

    <form action="" method="GET" class="mb-4 p-3 border rounded bg-light">
      <div class="row g-3 align-items-end">
          
          <div class="col-md-6">
              <label for="search" class="form-label">Search</label>
              <input type="text" name="search" id="search" class="form-control" 
                    placeholder="Enter Customer, Project, or Description..." 
                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
          </div>

          <div class="col-md-4">
              <label class="form-label">Date Range</label>
              <div class="input-group">
                  <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                  <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
              </div>
          </div>

          <div class="col-md-2">
              <button type="submit" class="btn btn-primary w-100">Filter</button>
          </div>
      </div>
  </form>

      <div class="row g-3">
        <?php if (!empty($data['activities'])): ?>
          <?php foreach ($data['activities'] as $activity): ?>
            <div class="col-md-4">
              <div class="card h-100 shadow-sm border-0 activity-card">
                <div class="card-body">
                  <p class="mb-1"><strong>Customer:</strong> 
                    <span class="badge bg-primary"><?= htmlspecialchars($activity['company_name']) ?></span>
                  </p>
                  <p class="mb-1"><strong>Project:</strong> 
                    <span class="badge bg-info text-dark"><?= htmlspecialchars($activity['project_name']) ?></span>
                  </p>
                  <p class="small text-muted">
                    <?= htmlspecialchars(mb_substr(strip_tags($activity['description']), 0, 40)) ?>...
                  </p>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center bg-white">
                  <small class="text-muted"><?= htmlspecialchars($activity['created_at']) ?></small>
                  <div>
                    <a href="/mcvpro/public/activities/view/<?= encodeId($activity['activity_id']) ?>" 
                       class="btn btn-sm btn-outline-primary btn-circle" 
                       data-bs-toggle="tooltip" title="View">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a href="/mcvpro/public/activities/delete/<?= encodeId($activity['activity_id']) ?>" 
                       class="btn btn-sm btn-outline-danger btn-circle ms-2" 
                       onclick="return confirm('Are you sure you want to delete this activity?');"
                       data-bs-toggle="tooltip" title="Delete">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12 text-center text-muted py-5">
            <i class="fa fa-folder-open fa-2x mb-3"></i>
            <p>No activities found.</p>
          </div>
        <?php endif; ?>
      </div>
    

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4">
    <?php
    // คำนวณจำนวนหน้าทั้งหมด
    $totalPages = ceil($data['total_activities'] / $data['per_page']);
    // กำหนดหน้าปัจจุบัน
    $page = $data['current_page'];
    ?>

    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                        Previous
                    </a>
                </li>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                        Next
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
  </div>
  <div class="card-footer text-end text-muted px-4 py-3">
                Showing <?= count($activities) ?> activities
            </div>
</div>
</div>
</body>
</html>

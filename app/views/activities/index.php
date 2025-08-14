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
    <div class="container main-content my-5">
        

    <div class="card shadow-sm">
        <div class="card-header custom-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fa-solid fa-diagram-project me-2"></i>Activity</h4>
        <a href="/mcvpro/public/activities/create" class="btn btn-light shadow-sm">
            <i class="fas fa-plus"></i> Create New
        </a>
        </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle text-center mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Project</th>
                <th>Description</th>
                <th>Created At</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($data['activities'])): ?>
                <?php foreach ($data['activities'] as $activity): ?>
                  <tr>
                    <td><?= htmlspecialchars($activity['activity_id']) ?></td>
                    <td><?= htmlspecialchars($activity['company_name']) ?></td>
                    <td><?= htmlspecialchars($activity['project_name']) ?></td>
                    <td><?= htmlspecialchars(mb_substr(strip_tags($activity['description']), 0, 20)) ?>...</td>
                    <td><span class="text-muted small"><?= htmlspecialchars($activity['created_at']) ?></span></td>
                    <td>
                      <a href="/mcvpro/public/activities/view/<?= encodeId($activity['activity_id']) ?>" 
                         class="btn btn-sm btn-outline-primary" 
                         title="View">
                        <i class="fas fa-eye"></i>
                      </a>
                      <a href="/mcvpro/public/activities/delete/<?= encodeId($activity['activity_id']) ?>" 
                         class="btn btn-sm btn-outline-danger" 
                         onclick="return confirm('Are you sure you want to delete this activity?');">
                        <i class="fas fa-trash-alt"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-muted text-center py-4">No activities found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

           <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4">
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php if ($data['current_page'] > 1): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?= $data['current_page'] - 1 ?>">Previous</a>
            </li>
          <?php endif; ?>

          <?php for ($i = 1; $i <= ceil($data['total_activities'] / $data['per_page']); $i++): ?>
            <li class="page-item <?= ($i == $data['current_page']) ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>

          <?php if ($data['current_page'] < ceil($data['total_activities'] / $data['per_page'])): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?= $data['current_page'] + 1 ?>">Next</a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
      </div>
   
    </div>

    
  </div>
</body>
</html>

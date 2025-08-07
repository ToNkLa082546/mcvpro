<?php
$memberSummary = $data['member_summary'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard</title>
  <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/mcvpro/public/css/dashboard/admin.css">
</head>
<body style="background-color: #FFF5EE;">

<div class="container py-4">
  <div class="dashboard-title border-bottom pb-2 mb-4">
    <h1 class="h2">Admin Dashboard</h1>
  </div>

 <!-- Stats Section -->
<div class="row g-5">
  <!-- Row 1 -->
  <div class="col-md-4">
    <div class="card stat-card bg-primary shadow" style="cursor: pointer;" onclick="location.href='/mcvpro/public/users/index'">
      <div class="card-body">
        <div class="stat-text">
          <h5><i class="fas fa-users me-2"></i> Total Users</h5>
          <div class="stat-number" id="total-users"><?= $data['stats']['total_users'] ?? '-' ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-users"></i></div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card stat-card bg-success shadow" style="cursor: pointer;" onclick="location.href='/mcvpro/public/customers/index'">
      <div class="card-body">
        <div class="stat-text">
          <h5><i class="fas fa-building me-2"></i> Total Customers</h5>
          <div class="stat-number" id="total-customers"><?= $data['stats']['total_customers'] ?? '-' ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-building"></i></div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card stat-card bg-secondary shadow" style="cursor: pointer;" onclick="location.href='/mcvpro/public/projects'">
      <div class="card-body">
        <div class="stat-text">
          <h5><i class="fas fa-folder-open me-2"></i> Total Projects</h5>
          <div class="stat-number" id="total-projects"><?= $data['stats']['total_projects'] ?? '-' ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card stat-card bg-warning shadow" style="cursor: pointer;" onclick="location.href='/mcvpro/public/quotations'">
      <div class="card-body">
        <div class="stat-text">
          <h5><i class="fas fa-folder me-2"></i> Total Quotations</h5>
          <div class="stat-number" id="total-quotations"><?= $data['stats']['total_quotations'] ?? '-' ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-folder"></i></div>
      </div>
    </div>
  </div>

<div class="col-md-4">
  <div class="card stat-card bg-info shadow" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#viewAllModal">
    <div class="card-body">
      <div class="stat-text">
        <h5><i class="fas fa-user-friends me-2"></i> Total Members</h5>
        <div class="stat-number"><?= count($memberSummary) ?></div>
      </div>
      <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
    </div>
  </div>
</div>


<div class="modal fade" id="viewAllModal" tabindex="-1" aria-labelledby="viewAllModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="viewAllModalLabel"><i class="fas fa-users me-2"></i> All Members</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" class="form-control mb-3" id="memberSearch" placeholder="🔍 Search by name...">
        <div class="table-responsive">
          <table class="table table-sm table-striped text-center" id="allMembersTable">
            <thead class="table-light">
              <tr>
                <th class="text-start">Member Name</th>
                <th>Total</th>
                </tr>
            </thead>
            <tbody>
              <?php foreach ($memberSummary as $summary): ?>
                <tr>
                  <td class="text-start">
                  <span class="view-member-detail fw-bold text-dark" style="cursor:pointer; text-decoration: none;"
                  data-member='<?= json_encode($summary) ?>'>
                  <?= htmlspecialchars($summary['member_name']) ?>
                  </span>
                  </td>
                  <td><?= $summary['total_quotation_count'] ?></td>
                  </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal รายละเอียดรายบุคคล -->
<div class="modal fade" id="memberSummaryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">Member Summary</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="memberSummaryBody">
        </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<script src="/mcvpro/public/js/staff-dashboard.js"></script>
<script src="/mcvpro/public/js/admin-dashboard.js"></script>
</body>
</html>

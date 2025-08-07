<?php
// ดึงค่าเริ่มต้นมาจาก Controller (เหมือนเดิม)
$totalQuotations = $data['total_quotations'] ?? 0;
$totalApprovedValue = $data['total_approved_value'] ?? 0;
$memberSummary = $data['member_summary'] ?? [];
$initialStartDate = $data['initial_start_date'] ?? date('Y-m-01');
$initialEndDate = $data['initial_end_date'] ?? date('Y-m-t');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Staff Dashboard</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/mcvpro/public/css/dashboard/staff.css">
</head>
<body style="background-color: #FFF5EE;">
<div class="container py-5">

    <h2 class="text-center dashboard-title mb-3">👩‍💼 Staff Dashboard</h2>
    
    <div class="card card-body mb-5 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-2">
             <h5 class="mb-0 text-primary">Data Filter</h5>
             <small class="text-muted" id="dashboard-date-range">
                Current Range: <?= date('d M Y', strtotime($initialStartDate)) ?> to <?= date('d M Y', strtotime($initialEndDate)) ?>
            </small>
        </div>
        <form id="filter-form" class="row gy-3 gx-4 align-items-end">
    <div class="col-lg-4 col-md-6">
        <label for="date-range-preset" class="form-label">🔄 Quick Select</label>
        <select id="date-range-preset" class="form-select shadow-sm rounded">
            <option value="custom">Custom Range</option>
            <option value="this_month" selected>This Month</option>
            <option value="last_month">Last Month</option>
        </select>
    </div>
    <div class="col-lg-3 col-md-6">
        <label for="start-date" class="form-label">📅 Start Date</label>
        <input type="date" id="start-date" class="form-control shadow-sm rounded" value="<?= htmlspecialchars($initialStartDate) ?>">
    </div>
    <div class="col-lg-3 col-md-6">
        <label for="end-date" class="form-label">📅 End Date</label>
        <input type="date" id="end-date" class="form-control shadow-sm rounded" value="<?= htmlspecialchars($initialEndDate) ?>">
    </div>
    <div class="col-lg-2 col-md-6">
        <button type="submit" class="btn btn-primary w-100 shadow-sm rounded">
            <i class="fas fa-filter"></i> Filter
        </button>
    </div>
</form>

    </div>
    
    <div class="row g-4 justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-file-invoice fa-2x text-primary mb-2"></i>
                    <h5 class="text-muted">Total Quotations</h5>
                    <p class="display-6 fw-bold text-primary" id="total-quotations-card"><?= $totalQuotations ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-handshake fa-2x text-success mb-2"></i>
                    <h5 class="text-muted">Approved Amount</h5>
                    <p class="display-6 fw-bold text-success" id="total-approved-card">฿ <?= number_format($totalApprovedValue, 2) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#viewAllModal">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x text-info mb-2"></i>
                    <h5 class="text-muted">Members (in range)</h5>
                    <p class="display-6 fw-bold text-info" id="total-members-card"><?= count($memberSummary) ?></p>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="viewAllModal" tabindex="-1" aria-labelledby="viewAllModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="viewAllModalLabel"><i class="fas fa-users me-2"></i> All Members Summary</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
               <div class="mb-3">
                    <input type="text" id="memberSearch" class="form-control" placeholder="🔍 Search member name...">
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="allMembersTable">
                        </table>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="allMembersTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-start">Member Name</th>
                                <th>Total</th>
                                <th>Draft</th>
                                <th>Pending</th>
                                <th>Approval</th>
                                <th>Revised</th>
                                <th>Rejected</th>
                                <th>Cancel</th>
                                <th class="text-end">Approved Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($memberSummary)): ?>
                                <tr><td colspan="8" class="text-center p-4">No data found for the selected period.</td></tr>
                            <?php else: ?>
                                <?php foreach ($memberSummary as $summary): ?>
                                <tr>
                                    <td class="text-start fw-bold"><?= htmlspecialchars($summary['email_user']) ?></td>
                                    <td class="text-center"><?= $summary['total_quotations'] ?></td>
                                    <td class="text-center"><?= $summary['status_draft'] ?></td>
                                    <td class="text-center"><?= $summary['status_pending'] ?></td>
                                    <td class="text-center text-success"><?= $summary['status_approval'] ?></td>
                                    <td class="text-center text-info"><?= $summary['status_revised'] ?></td>
                                    <td class="text-center text-danger"><?= $summary['status_rejected'] ?></td>
                                    <td class="text-center text-muted"><?= $summary['status_cancel'] ?></td>
                                    <td class="text-end fw-bold text-success">฿ <?= number_format($summary['total_approved_amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
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

<script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/mcvpro/public/vendor/jquery/jquery-3.6.0.min.js"></script>
<script src="/mcvpro/public/js/staff-dashboard.js"></script>
</body>
</html>
<?php
$stats = $data['stats'];
$goal = $data['sales_goal'];
$initialStartDate = $data['initial_start_date'] ?? date('Y-m-01');
$initialEndDate = $data['initial_end_date'] ?? date('Y-m-t');

$monthlySum = $stats['approved_sum_in_range'] ?? 0;
$progressPercentage = ($goal > 0) ? ($monthlySum / $goal) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Dashboard</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/mcvpro/public/css/dashboard/member.css">
</head>
<body style="background-color: #FFF5EE;">

<div class="container my-5" data-goal="<?= htmlspecialchars($goal) ?>">
    <h2 class="mb-3 text-center fw-bold"><i class="fas fa-user-chart text-info me-2"></i>My Dashboard</h2>
    <p class="text-center text-muted mb-4" id="dashboard-date-range">
        Showing data for: <?= date('d M Y', strtotime($initialStartDate)) ?> - <?= date('d M Y', strtotime($initialEndDate)) ?>
    </p>

    <div class="card card-body mb-5 shadow-sm">
        <form id="filter-form" class="row gy-3 gx-4 align-items-end">
    <div class="col-lg-4 col-md-6">
        <label for="date-range-preset" class="form-label fw-semibold text-muted">📅 Date Range</label>
        <select id="date-range-preset" class="form-select shadow-sm rounded">
            <option value="custom">Custom Range</option>
            <option value="this_month" selected>This Month</option>
            <option value="last_month">Last Month</option>
        </select>
    </div>
    <div class="col-lg-3 col-md-6">
        <label for="start-date" class="form-label fw-semibold text-muted">Start Date</label>
        <input type="date" id="start-date" class="form-control shadow-sm rounded" value="<?= htmlspecialchars($initialStartDate) ?>">
    </div>
    <div class="col-lg-3 col-md-6">
        <label for="end-date" class="form-label fw-semibold text-muted">End Date</label>
        <input type="date" id="end-date" class="form-control shadow-sm rounded" value="<?= htmlspecialchars($initialEndDate) ?>">
    </div>
    <div class="col-lg-2 col-md-6">
        <button type="submit" class="btn btn-primary w-100 shadow-sm rounded">
            <i class="fas fa-filter"></i> Filter
        </button>
    </div>
</form>

    </div>

    <div id="summary-cards-container" class="row g-3 justify-content-center mb-4">
        <?php
        $cards = [
            ['title' => 'Total', 'value' => $stats['total_quotations'] ?? 0, 'color' => 'primary', 'icon' => 'fa-layer-group', 'id' => 'total-card'],
            ['title' => 'Draft', 'value' => $stats['draft_quotations'] ?? 0, 'color' => 'secondary', 'icon' => 'fa-pen', 'id' => 'draft-card'],
            ['title' => 'Approved', 'value' => $stats['approved_quotations'] ?? 0, 'color' => 'success', 'icon' => 'fa-circle-check', 'id' => 'approved-card'],
            ['title' => 'Rejected', 'value' => $stats['rejected_quotations'] ?? 0, 'color' => 'danger', 'icon' => 'fa-circle-xmark', 'id' => 'rejected-card'],
            ['title' => 'Canceled', 'value' => $stats['canceled_quotations'] ?? 0, 'color' => 'dark', 'icon' => 'fa-ban', 'id' => 'canceled-card'],
        ];
        foreach ($cards as $card): ?>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card h-100 shadow-sm text-center border-0 bg-light rounded-4">
                <div class="card-body">
                    <h6 class="card-title text-<?= $card['color'] ?> fw-bold text-uppercase mb-2"><i class="fas <?= $card['icon'] ?> me-1"></i> <?= $card['title'] ?></h6>
                    <p class="card-text display-6 fw-bold" id="<?= $card['id'] ?>"><?= $card['value'] ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="row justify-content-center mt-5">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <h5 class="card-title text-muted mb-3"><i class="fas fa-coins me-2 text-success"></i>Approved Total (Selected Period)</h5>
                    <p class="h3 text-success fw-bold" id="approved-sum-text">฿ <?= number_format($monthlySum, 2) ?></p>
                    <div class="mt-4">
                        <small class="text-muted">Goal: ฿ <?= number_format($goal, 2) ?></small>
                        <div class="progress mt-1 rounded-pill" style="height: 20px;">
                            <div id="sales-progress-bar" class="progress-bar bg-success fw-semibold" role="progressbar" 
                                 style="width: <?= min($progressPercentage, 100) ?>%;" aria-valuenow="<?= $progressPercentage ?>">
                                 <?= round($progressPercentage) ?>%
                            </div>
                        </div>
                        <div id="goal-message" class="mt-3 fw-bold">
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/mcvpro/public/vendor/jquery/jquery-3.6.0.min.js"></script>
<script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/mcvpro/public/js/member-dashboard.js"></script>

</body>
</html>
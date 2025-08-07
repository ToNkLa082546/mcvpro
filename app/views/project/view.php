<?php include __DIR__ . '/../layout/sidebar.php';?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Project Details</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
</head>
<body style="background-color: #FFF5EE;">

<div class="container mt-5">
    <?php if (isset($project) && $project): ?>
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4>📄 Project Details: <?= htmlspecialchars($project['project_name']) ?></h4>
            </div>
            <div class="card-body">
                <p><strong>📝 Details:</strong></p>
                <div class="p-3 bg-light border rounded mb-3">
                    <?= nl2br(htmlspecialchars($project['description'] ?? 'No description.')) ?>
                </div>
                
                <p><strong>💰 Price:</strong> <?= number_format($project['project_price'], 2) ?> Baht</p>
                <p><strong>🏷️ Status:</strong> <span class="badge bg-info text-dark"><?= htmlspecialchars($project['status']) ?></span></p>
                <hr>
                <p><strong>🏢 Company:</strong> <?= $project['company_name'] ? htmlspecialchars($project['company_name']) : '<span class="text-muted">Not yet assigned to a customer</span>' ?></p>
                <p><strong>👤 Created By:</strong> <?= htmlspecialchars($project['created_by_name'] ?? 'N/A') ?></p>
                <p><strong>📅 Created At:</strong> <?= $project['created_at'] ? date("d F Y, H:i", strtotime($project['created_at'])) : '-' ?></p>
                <p><strong>🕒 Last Update:</strong> <?= $project['updated_at'] ? date("d F Y, H:i", strtotime($project['updated_at'])) : '-' ?></p>


                <div class="mt-4 border-top pt-3 text-center">
                    <a href="/mcvpro/public/projects" class="btn btn-secondary">↩️ Back to List</a>
                    <?php
                        if (!empty($project['customer_id'])) :
                        ?>
                    <a href="/mcvpro/public/quotations/create/<?= encodeId($project['project_id']) ?>" class="btn btn-success">Create Quotation</a>
                    <?php
                        endif;
                        // สิ้นสุดการตรวจสอบ
                        ?>
                <?php if (isset($_GET['from']) && $_GET['from'] === 'list'): ?>
                    <a href="/mcvpro/public/quotations" class="btn btn-secondary mb-3">↩️ Back to Quotatation List</a>
                <?php endif; ?>
                    <?php 
                    // ตรวจสอบสิทธิ์ที่ส่งมาจาก Controller
                    if ($canEditOrDelete): 
                    ?>
                        <a href="/mcvpro/public/projects/edit/<?= encodeId($project['project_id']) ?>" class="btn btn-warning">✏️ Edit</a>
                        <?php if ($_SESSION['user_role'] == 2): ?>
                            <a href="/mcvpro/public/projects/delete/<?= encodeId($project['project_id']) ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">🗑️ Delete</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger text-center">❌ Project not found.</div>
    <?php endif; ?>
</div>

</body>
</html>
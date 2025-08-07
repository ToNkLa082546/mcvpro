<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Customer Details<?= isset($data['customer']['company_name']) ? ': ' . htmlspecialchars($data['customer']['company_name']) : '' ?></title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
</head>
<body style="background-color: #FFF5EE;">

<div class="container mt-5">
    <?php if (isset($data['customer']) && $data['customer']): ?>
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">🏢 <?= htmlspecialchars($data['customer']['company_name']) ?></h4>
            
            <div>
                <?php 
                if ($data['canManage']): 
                ?>
                    <a href="/mcvpro/public/customers/edit/<?= encodeId($customer['customer_id']) ?>" class="btn btn-light btn-sm">✏️ Edit Info</a>
                <?php endif; ?>

                <?php 
                if ($data['isOwner']): 
                ?>
                    <a href="/mcvpro/public/customers/manage/<?= encodeId($customer['customer_id']) ?>" class="btn btn-warning btn-sm">⚙️ Manage</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-4">
            <h5><i class="text-primary"></i> Customer Information</h5>
            <table class="table table-bordered table-striped mb-4">
                <tbody>
                    <tr>
                        <th style="width: 150px;">Email</th>
                        <td><?= htmlspecialchars($data['customer']['customer_email'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td><?= htmlspecialchars($data['customer']['customer_phone'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td><?= nl2br(htmlspecialchars($data['customer']['customer_address'] ?? '-')) ?></td>
                    </tr>
                </tbody>
            </table>

            <h5 class="mt-4"><i class=" text-success"></i> Assigned Projects</h5>
            <ul class="list-group mb-4">
                <?php if (!empty($data['assignedProjects'])):
                    foreach ($data['assignedProjects'] as $project): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                           <a href="/mcvpro/public/projects/view/<?= $project['project_id'] ?>"><?= htmlspecialchars($project['project_name']) ?></a> 
                           <span class="badge bg-info rounded-pill"><?= htmlspecialchars($project['status']) ?></span>
                        </li>
                    <?php endforeach;
                else: ?>
                    <li class="list-group-item text-muted">No projects assigned to this company yet.</li>
                <?php endif; ?>
            </ul>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0"><i class=" text-danger"></i> Branch Offices</h5>
                <?php if ($data['canManage']): ?>
                    <a href="/mcvpro/public/branches/create/<?= encodeId($customer['customer_id']) ?>" class="btn btn-outline-success btn-sm">➕</a>
                <?php endif; ?>
            </div>
            <ul class="list-group mb-4">
                <?php if (!empty($data['branches'])): 
                    foreach ($data['branches'] as $branch): ?>
                        <li class="list-group-item">
                            <strong><?= htmlspecialchars($branch['branch_name']) ?></strong>
                            <small class="text-muted d-block"><?= htmlspecialchars($branch['branch_address'] ?? '-') ?></small>
                        </li>
                    <?php endforeach;
                else: ?>
                    <li class="list-group-item text-muted">No branches added yet.</li>
                <?php endif; ?>
            </ul>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0"><i class=" text-warning"></i> Contact Persons</h5>
                 <?php if ($data['canManage']): ?>
                    <a href="/mcvpro/public/contacts/create/<?= encodeId($customer['customer_id']) ?>" class="btn btn-outline-success btn-sm">➕</a>
                <?php endif; ?>
            </div>
            <ul class="list-group">
                 <?php if (!empty($data['contacts'])):
                    foreach ($data['contacts'] as $contact): ?>
                        <li class="list-group-item">
                            <strong><?= htmlspecialchars($contact['contact_name']) ?></strong> (<?= htmlspecialchars($contact['contact_position'] ?? 'N/A') ?>)
                            <small class="text-muted d-block"><i class="fas fa-envelope"></i> <?= htmlspecialchars($contact['contact_email'] ?? '-') ?></small>
                            <small class="text-muted d-block"><i class="fas fa-phone"></i> <?= htmlspecialchars($contact['contact_phone'] ?? '-') ?></small>
                        </li>
                    <?php endforeach;
                else: ?>
                    <li class="list-group-item text-muted">No contacts added yet.</li>
                <?php endif; ?>
            </ul>


        </div> <div class="card-footer text-end">
             <a href="/mcvpro/public/customers" class="btn btn-secondary">↩️ Back to Customer List</a>

             <?php if (isset($_GET['from']) && $_GET['from'] === 'list'): ?>
                <a href="/mcvpro/public/quotations" class="btn btn-secondary mb-3">↩️ Back to Quotatation List</a>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-danger text-center">❌ Could not load customer data.</div>
    <?php endif; ?>
</div>

</body>
</html>
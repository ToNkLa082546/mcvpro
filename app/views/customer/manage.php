<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Manage: <?= htmlspecialchars($data['customer']['company_name']) ?></title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
</head>
<body style="background-color: #FFF5EE;">

<div class="container my-5">
    <h2 class="mb-4">Manage: <?= htmlspecialchars($data['customer']['company_name']) ?></h2>

    <?php 
        if (isset($_SESSION['success'])) { echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>'; unset($_SESSION['success']); }
        if (isset($_SESSION['error'])) { echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>'; unset($_SESSION['error']); }
    ?>

    <?php if ($data['isOwner']): ?>
    <div class="card shadow-sm">
        <div class="card-header"><h5>⚙️ Manage Projects & Collaborators</h5></div>
        <div class="card-body">
            <h6 class="mt-2">📌 Assigned Projects</h6>
            <ul class="list-group mb-4">
                <?php // 1. ตรวจสอบและวนลูปแสดงโปรเจกต์ที่ผูกไว้แล้ว
                if (!empty($data['assignedProjects'])):
                    foreach ($data['assignedProjects'] as $project): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            
                            <span>
                            <a href="/mcvpro/public/projects/view/<?= $project['project_id'] ?>">
                                <?= htmlspecialchars($project['project_name']) ?>
                            </a> 
                            <span class="badge bg-info rounded-pill"><?= htmlspecialchars($project['status']) ?></span>
                            </span>
                            
                            <form action="/mcvpro/public/customers/unassign-project/<?= encodeId($customer['customer_id']) ?>/<?= $project['project_id'] ?>" method="post" onsubmit="return confirm('Are you sure you want to un-assign this project?');">
                                <button type="submit" class="btn btn-outline-warning btn-sm">Un-assign</button>
                            </form>

                        </li>
                    <?php endforeach;
                else: ?>
                    <li class="list-group-item text-muted">No projects have been assigned yet.</li>
                <?php endif; ?>
            </ul>

            <h6>➕ Assign a new project</h6>
            <form action="/mcvpro/public/customers/assign-project/<?= encodeId($customer['customer_id']) ?>" method="post">
                <div class="input-group">
                    <select name="project_id" class="form-select" required>
                        <option value="">-- Select your available projects --</option>
                        <?php if (!empty($data['unassignedProjects'])): ?>
                            <?php foreach ($data['unassignedProjects'] as $project): ?>
                                <option value="<?= $project['project_id'] ?>"><?= htmlspecialchars($project['project_name']) ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No available projects to assign</option>
                        <?php endif; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">💼 Assign</button>
                </div>
            </form>

            <hr>

            <h6 class="mt-4">👥 Collaborator Management</h6>
            <ul class="list-group mb-4">
                <?php if (empty($data['collaborators'])): ?>
                    <li class="list-group-item text-muted">No collaborators have been added yet.</li>
                <?php else: ?>
                    <?php foreach ($data['collaborators'] as $collab): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <?= htmlspecialchars($collab['fname_personal']) ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($collab['role_name']) ?></span>
                            </span>
                            <a href="/mcvpro/public/customers/remove-collaborator/<?= encodeId($customer['customer_id']) ?>/<?= $collab['id_user'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this collaborator?');">Remove</a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
             <h6>Add New Collaborator</h6>
            <form action="/mcvpro/public/customers/add-collaborator/<?= encodeId($customer['customer_id']) ?>" method="post">
                <div class="row g-2">
                    <div class="col-md-9">
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Select User to grant editing access --</option>
                            <?php foreach ($data['allUsers'] as $user): ?>
                                <option value="<?= $user['id_user'] ?>"><?= htmlspecialchars($user['fname_personal']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success w-100">Add Collaborator</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-warning">Only the customer owner can manage projects and collaborators.</div>
    <?php endif; ?>

    <a href="/mcvpro/public/customers/view/<?= encodeId($customer['customer_id']) ?>" class="btn btn-secondary">↩️ Back to Customer List</a>
</div>

</body>
</html>
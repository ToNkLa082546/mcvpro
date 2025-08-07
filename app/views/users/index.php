<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users (Admin)</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/mcvpro/public/css/user.css">
</head>

<body style="background-color: #FFF5EE;">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header py-3 px-4">
                <h4><i class="fa fa-users-cog me-2"></i>Manage user data</h4>
                <a href="/mcvpro/public/admin/users/create" class="btn btn-light shadow-sm">
                     <i class="fas fa-plus"></i> Add new user
                </a>
            </div>
            <div class="card-body p-4">
                <?php if (isset($_SESSION['flash_message'])) : ?>
                    <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['flash_message']['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['flash_message']); ?>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name - Surname</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)) : ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">User information not found</td>
                                </tr>
                            <?php else : ?>
                                <?php $i = 1; ?>
                                <?php foreach ($users as $user) : ?>
                                    <tr>
                                        <th scope="row"><?= $i++; ?></th>
                                        <td><?= htmlspecialchars($user['fname_personal'] . ' ' . $user['lname_personal']); ?></td>
                                        <td><?= htmlspecialchars($user['email_user']); ?></td>
                                        <td>
                                            <span class="badge bg-<?= $user['role_name'] === 'admin' ? 'danger' : ($user['role_name'] === 'staff' ? 'warning text-dark' : 'secondary') ?> badge-role">
                                                <?= htmlspecialchars($user['role_name']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center action-btns">
                                        <a href="/mcvpro/public/admin/users/view/<?= encodeId($user['id_user']) ?>" class="btn btn-outline-info btn-sm" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        
                                        <a href="/mcvpro/public/admin/users/delete/<?= encodeId($user['id_user']) ?>" class="btn btn-outline-danger btn-sm" title="Delete"
                                            onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้คนนี้? การกระทำนี้ไม่สามารถย้อนกลับได้');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end text-muted px-4 py-3">
                Found all users <?= count($users) ?> List (excluding your own accounts)
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

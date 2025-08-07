<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <title>User details</title>
</head>
<body>

<div class="container mt-4">
    <h4 class="mb-3"><i class="fas fa-user me-2"></i> User details</h4>

    <div class="card shadow-sm border-0 mx-auto" style="max-width: 1300px;">
        <div class="card-header bg-primary text-white py-2 px-3">
            <h6 class="mb-0">
                <i class="fas fa-user-circle me-2"></i>Information about: <?= htmlspecialchars($user['fullname']) ?>
            </h6>
        </div>

        <div class="card-body py-3 px-4">
            <div class="row align-items-start">
                <!-- ซ้าย: รูปโปรไฟล์ -->
                <div class="col-md-4 text-center">
                    <?php $imagePath = '/mcvpro/public/uploads/profiles/' . ($user['profile_image'] ?? 'default.png'); ?>
                    <img src="<?= $imagePath ?>" alt="Profile Picture" class="rounded-circle border mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                </div>

                <!-- ขวา: ข้อมูลผู้ใช้ -->
                <div class="col-md-8">
                    <table class="table table-sm table-borderless mb-0 small">
                        <tbody>
                            <tr>
                                <th class="text-muted"><i class="fas fa-id-badge me-2"></i> ID:</th>
                                <td><?= htmlspecialchars($user['id_user']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted"><i class="fas fa-user me-2"></i> FullName:</th>
                                <td><?= htmlspecialchars($user['fullname']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted"><i class="fas fa-envelope me-2"></i> Email:</th>
                                <td><?= htmlspecialchars($user['email_user']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted"><i class="fas fa-check-circle me-2"></i> Role:</th>
                                <td>
                                    <?php if ($user['is_verified']) : ?>
                                        <span class="badge bg-success">Confirmed</span>
                                    <?php else : ?>
                                        <span class="badge bg-warning text-dark">Not yet confirmed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted"><i class="fas fa-calendar-alt me-2"></i> register:</th>
                                <td><?= htmlspecialchars(date('d F Y, H:i', strtotime($user['created_at']))) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card-footer text-end bg-light py-2 px-3">
            <a href="/mcvpro/public/admin/users/edit/<?= encodeId($user['id_user']) ?>" class="btn btn-sm btn-warning me-2">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="/mcvpro/public/admin/users" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>



</body>
</html>

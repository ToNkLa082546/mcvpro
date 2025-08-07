<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit user information</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h4 class="mb-3"><i class="fas fa-user-edit me-2"></i> Edit user information</h4>
        

        <div class="card shadow-sm border-0 mx-auto" style="max-width: 1300px;">
            <div class="card-header bg-warning text-dark py-2">
                <h6 class="mb-0"><i class="fas fa-user-edit me-2"></i> Edit information for: <?= htmlspecialchars($user['fullname']) ?></h6>
            </div>

            <form action="/mcvpro/public/admin/users/update/<?= htmlspecialchars($user['id_user']) ?>" method="POST">
                <div class="card-body px-4 py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="fname_personal" class="form-label">FirstName</label>
                            <input type="text" class="form-control" id="fname_personal" name="fname_personal" value="<?= htmlspecialchars($user['fname_personal']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="lname_personal" class="form-label">LastName</label>
                            <input type="text" class="form-control" id="lname_personal" name="lname_personal" value="<?= htmlspecialchars($user['lname_personal']) ?>" required>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="email_user" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email_user" name="email_user" value="<?= htmlspecialchars($user['email_user']) ?>" required>
                    </div>

                    <div class="mt-3">
                        <label for="role_id" class="form-label">Role</label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <option value="1" <?= ($user['role_id'] == 1) ? 'selected' : '' ?>>Admin</option>
                            <option value="2" <?= ($user['role_id'] == 2) ? 'selected' : '' ?>>Staff</option>
                            <option value="3" <?= ($user['role_id'] == 3) ? 'selected' : '' ?>>Member</option>
                        </select>
                    </div>

                    <hr class="my-4">

                    <p class="text-muted mb-1"><i class="fas fa-key me-1"></i> Change password <small>(Leave blank if you do not want to change)</small></p>
                    <input type="password" class="form-control" id="password" name="password" placeholder="New password">

                </div>

                <div class="card-footer text-end bg-light py-2 px-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save
                    </button>
                    <a href="/mcvpro/public/admin/users/view/<?= encodeId($user['id_user']) ?>" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

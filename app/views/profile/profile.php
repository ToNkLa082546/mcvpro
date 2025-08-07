

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/mcvpro/public/css/profile.css">
</head>
<body class="profile-page" style="background-color: #FFF5EE;">


<div class="container">
    <div class="profile-container">
        <?php if (isset($user) && is_array($user)): ?>
            <div class="row align-items-center">
                <div class="col-md-4 text-center">
                    <?php $imagePath = '/mcvpro/public/uploads/profiles/' . ($user['profile_image'] ?? 'default.png'); ?>
                    <img src="<?= $imagePath ?>" alt="Profile Picture" class="profile-pic mb-3">
                </div>
                <div class="col-md-8 profile-info">
                    <h3><?= htmlspecialchars($user['fname_personal'] . ' ' . $user['lname_personal']) ?></h3>
                    <p class="text-muted"><?= htmlspecialchars($user['role_name']) ?></p>
                    <hr>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email_user']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone_personal'] ?? '-') ?></p>
                    <p><strong>Date of Birth:</strong> <?= $user['dob_personal'] ? date("d F Y", strtotime($user['dob_personal'])) : '-' ?></p>
                </div>
            </div>
            <div class="mt-4 border-top pt-4 text-center">
                 <a href="/mcvpro/public/profile/edit/" class="btn btn-primary">✏️ Edit Profile</a>
                 <a href="/mcvpro/public/home" class="btn btn-secondary">↩️ Back to Home</a>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">Could not load user profile data.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
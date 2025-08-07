

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/mcvpro/public/css/profile.css">
</head>
<body class="profile-page" style="background-color: #FFF5EE;">


<div class="container">
    <div class="profile-container">
        <h2 class="text-center mb-4">✏️ Edit Profile</h2>

        <?php 
            // ส่วนแสดงข้อความแจ้งเตือน
            if (isset($_SESSION['success'])) { echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>'; unset($_SESSION['success']); }
            if (isset($_SESSION['error'])) { echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>'; unset($_SESSION['error']); }
        ?>

        <div class="profile-pic-uploader text-center mb-4">
            <form action="/mcvpro/public/profile/upload" method="post" enctype="multipart/form-data" id="uploadForm">
                <input type="file" name="profile_image" id="fileInput" accept="image/*" class="d-none" onchange="document.getElementById('uploadForm').submit();">
                <label for="fileInput" style="cursor: pointer;">
                    <?php $imagePath = '/mcvpro/public/uploads/profiles/' . ($user['profile_image'] ?? 'default.png'); ?>
                    <img src="<?= $imagePath ?>" alt="Profile Picture" class="profile-pic">
                    <div class="text-muted small mt-2">Click image to change</div>
                </label>
            </form>
        </div>
        
        <form action="/mcvpro/public/profile/update" method="post">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email_user']) ?>" readonly disabled>
            </div>

            <hr class="my-4">

        <div>
            <h4 class="mb-3">Password & Security</h4>
            <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                <div>
                    <strong>Password</strong><br>
                    <span class="text-muted">••••••••</span>
                </div>
                <a href="/mcvpro/public/profile/security" class="btn btn-outline-secondary">Change</a>
            </div>
        </div>


            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="fname" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="fname" name="fname" value="<?= htmlspecialchars($user['fname_personal']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="lname" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="lname" name="lname" value="<?= htmlspecialchars($user['lname_personal']) ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="nname" class="form-label">Nickname</label>
                <input type="text" class="form-control" id="nname" name="nname" value="<?= htmlspecialchars($user['nname_personal']) ?>">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="dob" class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" id="dob" name="dob" value="<?= htmlspecialchars($user['dob_personal']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone_personal']) ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($user['address_personal']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description / Bio</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($user['description_personal']) ?></textarea>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" name="updateProfile" class="btn btn-primary">Save Changes</button>
                <a href="/mcvpro/public/profile" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
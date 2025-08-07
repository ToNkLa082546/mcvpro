<!DOCTYPE html>
<html lang="th">
<head>
    <title>Verify Identity</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/mcvpro/public/css/profile.css">
</head>
<body class="profile-page" style="background-color: #FFF5EE;">

<div class="container">
    <div class="profile-container" style="max-width: 500px;">
        <h2 class="text-center mb-3">Verify Your Identity</h2>
        <p class="text-center text-muted mb-4">For your security, please enter your current password to continue.</p>
        
        <?php 
            if (isset($_SESSION['error'])) { echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>'; unset($_SESSION['error']); }
        ?>

        <form action="/mcvpro/public/profile/verify-password" method="post">
            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required autofocus>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Continue</button>
                <a href="/mcvpro/public/profile/edit" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
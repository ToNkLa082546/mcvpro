<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Password</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/mcvpro/public/css/register.css">
</head>
<body style="background-color: #FFF5EE;" style="min-height: 100vh; display: flex; justify-content: center; align-items: center;">

    <div class="register-container shadow p-4 bg-white rounded-4" style="width: 100%; max-width: 500px;">
        <h2 class="text-center mb-4">New Password</h2>

        <?php
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
        ?>

        <form action="/mcvpro/public/password/update" method="post">
            <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['password_reset_email'] ?? '') ?>">

            <div class="mb-3">
                <label for="otp" class="form-label">OTP code</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                    <input type="text" class="form-control" id="otp" name="otp" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="/mcvpro/public/login" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save new password</button>
            </div>
        </form>
    </div>

</body>
</html>
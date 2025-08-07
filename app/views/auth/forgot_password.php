<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ลืมรหัสผ่าน</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/mcvpro/public/css/auth-form.css">
</head>
<body style="background-color: #FFF5EE;">

<div class="flex-center-container">

    <div class="auth-card">
        <h2>Forgot password</h2>
        <p class="text-muted">Please enter your email to receive OTP for resetting your password.</p>

        <?php
            if (isset($_SESSION['success'])) { echo '<div class="alert alert-success">'.htmlspecialchars($_SESSION['success']).'</div>'; unset($_SESSION['success']); }
            if (isset($_SESSION['error'])) { echo '<div class="alert alert-danger">'.htmlspecialchars($_SESSION['error']).'</div>'; unset($_SESSION['error']); }
        ?>

        <form action="/mcvpro/public/password/request" method="post">
            <div class="mb-3">
                <label for="email" class="form-label">Registered Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email Address" required>
                </div>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-auth-action">Send OTP to reset password</button>
            </div>
        </form>
         <div class="text-center mt-3">
            <a href="/mcvpro/public/auth/login">Back to the login page</a>
        </div>
    </div>

</div> </body>
</html>
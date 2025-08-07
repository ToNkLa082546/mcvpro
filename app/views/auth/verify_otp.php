<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?? 'Verify Account' ?></title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/mcvpro/public/css/auth-form.css">
    
    <style>
        .otp-input {
            font-size: 2rem;
            text-align: center;
            letter-spacing: 0.5rem;
            font-weight: 700;
        }
    </style>
</head>
<body style="background-color: #FFF5EE;" class="auth-page">

<div class="auth-card">
    <h2>Verify Your Account</h2>
    <?php
        function maskEmail($email) {
            if(strpos($email, '@') === false) return $email;
            list($user, $domain) = explode('@', $email);
            $userLength = strlen($user);
            $maskLength = $userLength > 3 ? $userLength - 3 : 0;
            $user = substr($user, 0, 3) . str_repeat('*', $maskLength);
            return $user . '@' . $domain;
        }

        if (isset($_SESSION['verify_email'])) {
            echo '<p class="text-muted text-center">Please enter the 6-digit code sent to<br><strong>' . htmlspecialchars(maskEmail($_SESSION['verify_email'])) . '</strong></p>';
        }
    ?>
    
    <?php
        if (isset($_SESSION['success'])) { echo '<div class="alert alert-success">'.htmlspecialchars($_SESSION['success']).'</div>'; unset($_SESSION['success']); }
        if (isset($_SESSION['error'])) { echo '<div class="alert alert-danger">'.htmlspecialchars($_SESSION['error']).'</div>'; unset($_SESSION['error']); }
    ?>

    <form action="/mcvpro/public/auth/register/verify" method="post">
        <div class="mb-3">
            <label for="otp" class="form-label visually-hidden">OTP Code</label>
            <input type="text" class="form-control otp-input" id="otp" name="otp" maxlength="6" inputmode="numeric" pattern="[0-9]*" required autofocus>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-auth-action">Verify Code</button>
        </div>
    </form>
    
    <div class="text-center mt-4">
        <p class="text-muted small">Didn't receive the code? <a href="/mcvpro/public/auth/register/resend-otp">Send again</a></p>
    </div>
</div>

</body>
</html>
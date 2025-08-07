







<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login</title>

  <!-- Bootstrap & FontAwesome -->
  <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">

  <!-- ✅ โหลด CSS เฉพาะหน้าล็อกอิน -->
  <link rel="stylesheet" href="/mcvpro/public/css/login.css">
</head>
<body style="background-color: #FFF5EE;">

<div class="login-box">
  <h1>🔐 Sign In</h1>
  <form action="/mcvpro/public/auth/login/authenticate" method="POST">
<?php
// ตรวจสอบว่ามี error message ใน session หรือไม่
if (isset($_SESSION['login_error'])) {
    // แสดงข้อความ error
    echo '<div class="alert alert-danger text-center">' . $_SESSION['login_error'] . '</div>';
    
    // ล้างค่า session ออกไป เพื่อไม่ให้แสดงซ้ำหลังรีเฟรช
    unset($_SESSION['login_error']);
}
?>

    <div class="input-group">
      <i class="fas fa-envelope"></i>
      <input type="email" name="email" placeholder="Email" required>
    </div>
    <div class="input-group">
      <i class="fas fa-lock"></i>
      <input type="password" name="password" placeholder="Password" required>
    </div>

    <div class="d-flex justify-content-between mb-3">
      <a href="/mcvpro/public/password/forgot" class="text-decoration-none">Forgot your password?</a>
    </div>

    <input type="submit" class="btn btn-primary btn-login" value="Sign In" name="signIn">
  </form>

  <div class="links mt-3">
    <p>Don't have an account yet? <a href="/mcvpro/public/register">Sign Up</a></p>
  </div>
</div>
<script src="/mcvpro/public/js/login.js"></script>
</body>
</html>

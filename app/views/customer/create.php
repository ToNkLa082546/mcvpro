<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Add New Customer</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/mcvpro/public/css/register.css">
</head>
<body style="background-color: #FFF5EE;">

<div class="container mt-5">
    <div class="register-container" style="max-width: 600px;">
        <h2 class="mb-4">➕ Add New Customer</h2>

        <?php
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
        ?>
        
        <form action="/mcvpro/public/customers/store" method="post">
            <div class="mb-3">
                <label for="company_name" class="form-label">Company Name</label>
                <input type="text" class="form-control" id="company_name" name="company_name" required>
            </div>
            
            <!-- เพิ่มฟิลด์สำหรับข้อมูลอื่นๆ -->
            <div class="mb-3">
                <label for="customer_phone" class="form-label">Phone</label>
                <input type="text" class="form-control" id="customer_phone" name="customer_phone">
            </div>

            <div class="mb-3">
                <label for="customer_email" class="form-label">Email</label>
                <input type="email" class="form-control" id="customer_email" name="customer_email">
            </div>

            <div class="mb-3">
                <label for="customer_address" class="form-label">Address</label>
                <textarea class="form-control" id="customer_address" name="customer_address" rows="3"></textarea>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Save Customer</button>
                <a href="/mcvpro/public/customers" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
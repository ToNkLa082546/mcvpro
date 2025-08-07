<!DOCTYPE html>
<html lang="th">
<head>
    <title>Edit Customer: <?= htmlspecialchars($customer['company_name']) ?></title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
</head>
<body style="background-color: #FFF5EE;">

<div class="container my-5">
    <div class="card shadow-sm" style="max-width: 800px; margin: auto;">
        <div class="card-header">
            <h2 class="mb-0">📝 Edit Information: <?= htmlspecialchars($customer['company_name']) ?></h2>
        </div>
        <div class="card-body">
            <form action="/mcvpro/public/customers/update/<?= $customer['customer_id'] ?>" method="post">
                <div class="mb-3">
                    <label for="company_name" class="form-label">Company Name</label>
                    <input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($customer['company_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="customer_email" class="form-label">Company Email</label>
                    <input type="email" class="form-control" name="customer_email" value="<?= htmlspecialchars($customer['customer_email'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="customer_phone" class="form-label">Company Phone</label>
                    <input type="text" class="form-control" name="customer_phone" value="<?= htmlspecialchars($customer['customer_phone'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="customer_address" class="form-label">Company Address</label>
                    <textarea class="form-control" name="customer_address" rows="3"><?= htmlspecialchars($customer['customer_address'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="/mcvpro/public/customers/view/<?= encodeId($customer['customer_id']) ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>
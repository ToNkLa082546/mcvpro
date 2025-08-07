<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Branch</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
</head>
<body style="background-color: #FFF5EE;">
    <div class="container my-5">
    <h2 class="mb-4">➕ Add New Branch</h2>
    <div class="card">
        <div class="card-body">
            <form action="/mcvpro/public/branches/store" method="post">
                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($data['customer_id']) ?>">
                <div class="mb-3">
                    <label for="branch_name" class="form-label">Branch Name</label>
                    <input type="text" class="form-control" name="branch_name" required>
                </div>
                <div class="mb-3">
                    <label for="branch_phone" class="form-label">Branch Phone</label>
                    <input type="text" class="form-control" name="branch_phone">
                </div>
                <div class="mb-3">
                    <label for="branch_address" class="form-label">Branch Address</label>
                    <textarea class="form-control" name="branch_address" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Branch</button>
                <a href="/mcvpro/public/customers/view/<?= $data['customer_id'] ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <title>New Contact</title>
</head>
<body style="background-color: #FFF5EE;">
    <div class="container my-5">
    <h2 class="mb-4">➕ Add New Contact for <?= htmlspecialchars($data['customer']['company_name']) ?></h2>
    <div class="card">
        <div class="card-body">
            <form action="/mcvpro/public/contacts/store" method="post">
                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($data['customer_id']) ?>">
                
                <div class="mb-3">
                    <label for="contact_name" class="form-label">Contact Name</label>
                    <input type="text" class="form-control" name="contact_name" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="contact_email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="contact_email">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="contact_phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" name="contact_phone">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="contact_position" class="form-label">Position</label>
                    <input type="text" class="form-control" name="contact_position">
                </div>
                
                <button type="submit" class="btn btn-primary">Save Contact</button>
                <a href="/mcvpro/public/customers/view/<?= $data['customer_id'] ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
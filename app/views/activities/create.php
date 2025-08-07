<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <title>Document</title>
</head>
<body>
    <div class="content-wrapper p-4" style="background-color: #f8f9fa;">
    <div class="content-header mb-4">
        <h2 class="fw-bold text-primary"><i class="fas fa-plus-circle me-2"></i>Create New Activity</h2>
        <hr>
    </div>

    <div class="content bg-white shadow-sm rounded p-4">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form action="/mcvpro/public/activities/store" method="post">
            <div class="row g-3">
                <?php if ($data['fromProject']): ?>
                    <input type="hidden" name="customer_id" value="<?= htmlspecialchars($data['customer']['customer_id']) ?>">
                    <input type="hidden" name="project_id" value="<?= htmlspecialchars($data['project']['project_id']) ?>">

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary">Customer</label>
                        <div class="form-control bg-light"><?= htmlspecialchars($data['customer']['company_name']) ?></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary">Project</label>
                        <div class="form-control bg-light"><?= htmlspecialchars($data['project']['project_name']) ?></div>
                    </div>
                <?php else: ?>
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label fw-bold text-secondary">1. Select Customer</label>
                        <select name="customer_id" id="customer_id" class="form-select" required>
                            <option value="" disabled selected>-- Select a customer --</option>
                            <?php foreach ($data['customers'] as $customer): ?>
                                <option value="<?= htmlspecialchars($customer['customer_id']) ?>">
                                    <?= htmlspecialchars($customer['company_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="project_id" class="form-label fw-bold text-secondary">2. Select Project</label>
                        <select name="project_id" id="project_id" class="form-select" required disabled>
                            <option value="" selected>-- Please select a customer first --</option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-md-12">
                    <label for="description" class="form-label fw-bold text-secondary">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="5" placeholder="Enter activity description..." required></textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Save Activity
                </button>
                <a href="/mcvpro/public/activities" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

 <!-- JavaScript for dependent dropdowns -->
    <?php if (!$data['fromProject']): ?>
    <script src="/mcvpro/public/js/activities/create.js"></script>
    <?php endif; ?>

</body>
</html>
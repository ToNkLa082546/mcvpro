<?php include __DIR__ . '/../layout/sidebar.php';?>
<!DOCTYPE html>
<html lang="th">
<head>
    <title>Browse Available Projects</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
</head>
<body style="background-color: #FFF5EE;">


<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">✨ Available Projects</h2>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row">
        <?php if (empty($projects)): ?>
            <div class="col-12">
                <p class="text-center text-muted">Sorry, there are no available projects at the moment.</p>
            </div>
        <?php else: ?>
            <?php foreach ($projects as $project): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($project['project_name']) ?></h5>
                            <p class="card-text text-muted flex-grow-1"><?= htmlspecialchars($project['description']) ?></p>
                            <h4 class="card-text text-end mb-3"><?= number_format($project['project_price'], 2) ?> ฿</h4>
                            
                            <form action="/mcvpro/public/projects/claim/<?= $project['project_id'] ?>" method="post">
                                <button type="submit" class="btn btn-primary w-100" onclick="return confirm('Are you sure you want to select this project?');">
                                    Select This Project
                                </button>
                            </form>
                        </div>
                        <div class="card-footer text-muted">
                            Posted on: <?= date("d M Y", strtotime($project['created_at'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
<?php include __DIR__ . '/../layout/sidebar.php';?>

<!DOCTYPE html>
<html lang="th">
<head>
    <title>Edit Project</title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/mcvpro/public/css/register.css">
</head>
<body style="background-color: #FFF5EE;">

<div class="container mt-5">
    <div class="register-container" style="max-width: 800px;">
        <h2 class="mb-4">📝 Edit Project</h2>
        <form action="/mcvpro/public/projects/update/<?= $project['project_id'] ?>" method="post">
            <div class="mb-3">
                <label for="project_name" class="form-label">Project Name</label>
                <input type="text" class="form-control" id="project_name" name="project_name" value="<?= htmlspecialchars($project['project_name']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($project['description']) ?></textarea>
            </div>
            <div class="mb-3">
                <label for="project_price" class="form-label">Price</label>
                <input type="number" step="0.01" class="form-control" id="project_price" name="project_price" value="<?= htmlspecialchars($project['project_price']) ?>" required>
            </div>
             <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <?php 
                        $statuses = ['Pending', 'In Progress', 'Completed', 'Cancel'];
                        foreach ($statuses as $status):
                            $isSelected = ($project['status'] == $status) ? 'selected' : '';
                            echo "<option value='{$status}' {$isSelected}>{$status}</option>";
                        endforeach; 
                    ?>
                </select>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="/mcvpro/public/projects" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
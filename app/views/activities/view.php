<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title ?? 'View Activity') ?></title>

     <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet" />
    <link href="/mcvpro/public/vendor/summernote/summernote-bs5.min.css" rel="stylesheet">
    
    <link href="/mcvpro/public/css/act_view.css" rel="stylesheet" />
</head>
<body>
  <div class="content-wrapper">
    <!-- Header -->
    <div class="content-header d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold text-primary mb-1">
          <i class="fas fa-clipboard-list me-2"></i>Activity Details
        </h2>
        <small class="text-muted">Activity #<?= htmlspecialchars($data['activity']['activity_id']) ?></small>
      </div>
      <div class="d-flex gap-2 ms-auto">
                <a href="/mcvpro/public/activities" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
                <?php if ($data['canEditOrDelete']): ?>
                    <a href="/mcvpro/public/activities/delete/<?= encodeId($data['activity']['activity_id']) ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this activity?');">
                        <i class="fas fa-trash-alt me-1"></i> Delete
                    </a>
                <?php endif; ?>
            </div>
    </div>

    <!-- Alert -->
    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success shadow-sm"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="row">
      <!-- Left Column -->
      <div class="col-lg-7">
        <div class="card shadow-sm mb-4 details-card">
          <div class="card-body">
            <h5 class="card-title mb-3">Information</h5>
            <ul class="list-group list-group-flush">
              <li class="list-group-item">
                <strong>Customer:</strong>
                <a href="/mcvpro/public/customers/view/<?= encodeId($data['activity']['customer_id']) ?>" class="text-decoration-none">
                  <?= htmlspecialchars($data['activity']['customer_name']) ?>
                </a>
              </li>
              <li class="list-group-item">
                <strong>Project:</strong>
                <a href="/mcvpro/public/projects/view/<?= encodeId($data['activity']['project_id']) ?>" class="text-decoration-none">
                  <?= htmlspecialchars($data['activity']['project_name']) ?>
                </a>
              </li>
              <li class="list-group-item">
                <strong>Created By:</strong>
                <?= htmlspecialchars($data['activity']['creator_name']) ?>
              </li>
              <li class="list-group-item">
                <strong>Created At:</strong>
                <?= date('d M Y, H:i', strtotime($data['activity']['created_at'])) ?>
              </li>
            </ul>
            <hr class="my-4" />
            <h5 class="card-title d-flex justify-content-between align-items-center">
                Description
                <button id="editBtn" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit me-1"></i>Edit
                </button>
            </h5>

            <?php
                $desc = $data['activity']['description'] ?? '';

                // ลบ inline style ที่ตั้ง text-align:center
                $desc = preg_replace('/style="[^"]*text-align\s*:\s*center[^"]*"/i', '', $desc);

                // ลบ tag <center>
                $desc = preg_replace('/<\s*center[^>]*>/i', '', $desc);
                $desc = preg_replace('/<\/\s*center\s*>/i', '', $desc);

                // ถ้าว่าง ให้ใส่ข้อความ default
                if (trim($desc) === '') {
                    $desc = '<em class="text-muted">No description provided.</em>';
                }
                ?>

                <div id="descriptionView" class="description-box">
                    <?= $desc ?>
            </div>


            <div id="description-feedback"></div>
            <!-- Edit Mode (Hidden by default) -->
            <form id="descriptionForm" data-activity-id="<?= htmlspecialchars($data['activity']['activity_id']) ?>" style="display: none;">
                <div class="mb-3">
                    <textarea id="descriptionEditor" name="description" class="form-control" rows="5"><?= htmlspecialchars($data['activity']['description']) ?></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save me-1"></i>Save</button>
                    <button type="button" class="btn btn-secondary btn-sm" id="cancelBtn"><i class="fas fa-times me-1"></i>Cancel</button>
                </div>
            </form>





            <div class="card shadow-sm mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0 text-secondary"><i class="fas fa-paperclip me-2"></i>Attached Files</h5>
            </div>
            <div class="card-body">
                <!-- File Upload Form -->
                <form action="/mcvpro/public/activities/uploadFile" method="post" enctype="multipart/form-data" class="mb-4 p-3 bg-light border rounded">
                    <input type="hidden" name="activity_id" value="<?= htmlspecialchars($data['activity']['activity_id']) ?>">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <label for="activityFile" class="visually-hidden">Upload File</label>
                            <input type="file" class="form-control" name="activityFile" id="activityFile" required accept=".pdf,application/pdf">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success w-100"><i class="fas fa-upload me-1"></i> Upload File</button>
                        </div>
                    </div>
                </form>

                <!-- File List -->
                <div id="file-list">
                    <?php if (!empty($data['files'])): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($data['files'] as $file): ?>
                                <li class="file-list-item">
                                    <div class="file-info">
                                        <i class="fas fa-file-alt fa-2x text-secondary"></i>
                                        <div>
                                            <a href="/mcvpro/public/<?= htmlspecialchars($file['file_path']) ?>" target="_blank" class="fw-bold text-decoration-none">
                                                <?= htmlspecialchars($file['original_filename']) ?>
                                            </a>
                                            <div class="file-meta">
                                                Uploaded by <?= htmlspecialchars($file['uploader_name'] ?? 'Unknown') ?> on <?= date('d M Y', strtotime($file['uploaded_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="/mcvpro/public/activities/deleteFile/<?= encodeId($file['file_id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this file?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-center text-muted mt-3">No files have been attached to this activity yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

          </div>
        </div>
        
      </div>


      
        <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-secondary"><i class="fas fa-file-invoice-dollar me-2"></i>Related Quotations</h5>
                        <!-- === จุดแก้ไข: เปลี่ยนเป็น Dropdown สำหรับกรองสถานะ === -->
                        <div class="d-flex align-items-center gap-2">
                            <label for="status-filter" class="form-label mb-0 small text-muted">Filter:</label>
                            <select class="form-select form-select-sm" id="status-filter" style="width: auto;">
                                <option value="all" selected>All Statuses</option>
                                <option value="Draft">Draft</option>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                                <option value="Canceled">Canceled</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body position-relative" id="quotation-list-container">
                        <!-- Loading Spinner (hidden by default) -->
                        <div class="loading-overlay d-none" id="loading-spinner">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        
                        <div id="quotation-list">
                            <?php if (!empty($data['quotations'])): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($data['quotations'] as $quotation): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <a href="/mcvpro/public/quotations/view/<?= encodeId($quotation['quotation_id'] ?? 0) ?>">
                                                <?= htmlspecialchars($quotation['quotation_number'] ?? 'N/A') ?>
                                            </a>
                                            <?php
                                                $status = strtolower($quotation['status'] ?? 'unknown');
                                                $badge_class = 'secondary';
                                                if ($status == 'approved') $badge_class = 'success';
                                                elseif (in_array($status, ['pending', 'in progress', 'draft'])) $badge_class = 'warning text-dark';
                                                elseif (in_array($status, ['rejected', 'canceled'])) $badge_class = 'danger';
                                            ?>
                                            <span class="badge bg-<?= $badge_class ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-center text-muted mt-3 mb-0">No quotations have been created for this activity yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
      </div>
    </div>
  </div>
<script id="page-data" type="application/json">
    <?= json_encode([
        'activityId' => $data['activity']['activity_id'],
        'initialQuotations' => $data['quotations'] ?? [] // เพิ่ม ?? [] เพื่อป้องกัน error หากไม่มี quotations
    ]) ?>
</script>

<script src="/mcvpro/public/vendor/jquery/jquery-3.6.0.min.js"></script>
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/mcvpro/public/vendor/summernote/summernote-bs5.min.js"></script>
    <script src="/mcvpro/public/js/activities/view.js"></script>
</body>
</html>

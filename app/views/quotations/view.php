<?php
// --- เตรียมข้อมูล ---
$q = $data['quotation'];
$items = $data['items'];
$userRole = $_SESSION['user_role'] ?? 0;
$quotationStatus = $q['status'] ?? 'Draft'; 
$finalSubTotal = 0;
foreach ($items as $item) {
    $finalSubTotal += $item['total'];
}

$finalVatAmount = $finalSubTotal * 0.07;
$finalGrandTotal = $finalSubTotal + $finalVatAmount;


// --- แปลง Grand Total เป็นข้อความ ---
$grandTotalInWords = '';
if (class_exists('NumberToWords\NumberToWords')) {
    $numberToWords = new NumberToWords\NumberToWords();
    $thaiTransformer = $numberToWords->getNumberTransformer('en');
    $grandTotalInWords = $thaiTransformer->toWords($finalGrandTotal);
}
$isOwner = isset($_SESSION['user_id']) && isset($q['created_by']) && ($_SESSION['user_id'] == $q['created_by']);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Quotation: <?= htmlspecialchars($q['quotation_number']) ?></title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/mcvpro/public/css/quotation-view.css">
</head>
<body style="background-color: #FFF5EE;">

<div class="container my-5 quotation-view">
    <!-- Action Buttons -->
    <div class="d-flex justify-content-end mb-3 gap-2 no-print">
        <?php
        // --- ปุ่มสำหรับ Member ---
        if ($isOwner && !in_array($userRole, [1, 2])) :

            if (in_array($quotationStatus, ['Draft', 'Rejected', 'Revised'])) :
                
                $cooldownActive = false;
                $cooldownEndTime = 0;

                if (!empty($q['last_sent_at'])) {
                    $lastCancelTime = new DateTime($q['last_sent_at']);
                    $now = new DateTime();
                    $secondsPassed = $now->getTimestamp() - $lastCancelTime->getTimestamp();
                    $cooldownSeconds = 180; // 3 นาที

                    if ($secondsPassed < $cooldownSeconds) {
                        $cooldownActive = true;
                        $cooldownEndTime = $lastCancelTime->getTimestamp() + $cooldownSeconds;
                    }
                }

                if ($cooldownActive) :
        ?>
                    <button type="button" class="btn btn-secondary" id="cooldown-btn" disabled
                            data-cooldown-end="<?= $cooldownEndTime; ?>">
                        <i class="fas fa-hourglass-half"></i> Please wait...
                    </button>
        <?php
                else :
        ?>
                    <form action="/mcvpro/public/quotations/request-approval/<?= encodeId($q['quotation_id']) ?>" method="POST">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-paper-plane"></i> Send for Approval
                        </button>
                    </form>
        <?php
                endif;
            endif;

            // ปุ่ม "Cancel Request"
            if ($quotationStatus === 'Pending Approval') :
        ?>
                <form action="/mcvpro/public/quotations/cancel-request/<?= encodeId($q['quotation_id']) ?>" method="POST">
                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to cancel this request?');">
                        <i class="fas fa-undo"></i> Cancel Request
                    </button>
                </form>
        <?php
            endif;
        endif;
        ?>

        <?php
        // --- Buttons for Staff/Admin ---
        if (in_array($userRole, [1, 2]) && $quotationStatus === 'Pending Approval') :
        ?>
            <form action="/mcvpro/public/quotations/approve/<?= encodeId($q['quotation_id']) ?>" method="POST" style="display: inline-block;">
                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Approve</button>
            </form>

            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="fas fa-times"></i> Reject
            </button>
        <?php
        endif;
        ?>

        <a href="/mcvpro/public/quotations/pdf/<?= encodeId($q['quotation_id']) ?>" class="btn btn-primary" target="_blank">
            <i class="fas fa-print"></i> Print / PDF
        </a>
        
        <?php if ($q['status'] !== 'Approved' && $q['status'] !== 'Cancel'): ?>
            <a href="/mcvpro/public/quotations/edit/<?= encodeId($q['quotation_id']) ?>" class="btn btn-warning">✏️ Edit</a>
        <?php endif; ?>
        
        <?php
        $canCancel = ($isOwner || in_array($userRole, [1, 2]));
        $isCancelableStatus = in_array($quotationStatus, ['Draft', 'Pending Approval']);

        if ($canCancel && $isCancelableStatus) :
        ?>
            <form action="/mcvpro/public/quotations/cancel/<?= encodeId($q['quotation_id']) ?>" method="POST" onsubmit="return confirm('Are you sure you want to cancel this quotation? This action cannot be undone.');">
                <button type="submit" class="btn btn-outline-danger">
                    <i class="fas fa-ban"></i> Cancel
                </button>
            </form>
        <?php
        endif;
        ?>
        
        <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], [1, 2])): ?>
            <a href="/mcvpro/public/quotations/delete/<?= encodeId($q['quotation_id']) ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">
                <i class="fas fa-trash"></i> Delete
            </a>
        <?php endif; ?>

        <a href="/mcvpro/public/quotations" class="btn btn-secondary">↩️ Back</a>
    </div>

    <!-- Main Card -->
    <div class="card shadow-sm">
        <div class="card-body p-4 p-md-5">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <div>
                    <h2 class="mb-1">[Your Company Name]</h2>
                    <p class="text-muted mb-0 small">[Your Address]<br>[Your Phone & Email]</p>
                </div>
                <div>
                    <h1 class="display-5 text-muted mb-0">QUOTATION</h1>
                </div>
            </div>

            <!-- Quotation Info -->
            <div class="row mb-4">
                <div class="col-md-7">
                    <h5 class="fw-bold">TO:</h5>
                    <address class="ms-3">
                        <strong><?= htmlspecialchars($q['company_name']) ?></strong><br>
                        <?= nl2br(htmlspecialchars($q['customer_address'] ?? 'N/A')) ?><br>
                        <i class="fas fa-phone-alt fa-fw me-2"></i><?= htmlspecialchars($q['customer_phone'] ?? 'N/A') ?><br>
                        <i class="fas fa-envelope fa-fw me-2"></i><?= htmlspecialchars($q['customer_email'] ?? 'N/A') ?>
                    </address>
                </div>
                <div class="col-md-5">
                    <table class="table table-sm table-borderless">
                        <tbody>
                        <tr>
                            <td class="fw-bold text-end">QUOTE #:</td>
                            <td><?= htmlspecialchars($q['quotation_number']) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-end">DATE:</td>
                            <td><?= date("d F Y", strtotime($q['created_at'])) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-end">VALID UNTIL:</td>
                            <td><?= $q['valid_until'] ? date("d F Y", strtotime($q['valid_until'])) : '-' ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <p><strong>Project:</strong> <?= htmlspecialchars($q['project_name']) ?></p>

            <!-- Items Header -->
            <div class="item-header">
                <div class="col-num">#</div>
                <div class="col-desc">Description</div>
                <div class="col-qty">Qty</div>
                <div class="col-unit-price">Unit Price</div>
                <div class="col-total">Total</div>
            </div>

            <!-- Items List -->
            <?php 
            $mainCounter = 0;
            foreach ($items as $item):

                // --- จุดที่ 1: แก้ไขเงื่อนไขให้เช็คจาก $item['type'] ---
                if ($item['type'] !== 'show') {
                    continue;
                }

                $mainCounter++;
                // คำนวณราคาต่อหน่วยจากยอดรวมที่ถูกบวกค่า sub-items ไปแล้ว
                $unitPrice = ($item['quantity'] > 0) ? ($item['total'] / $item['quantity']) : 0;
            ?>
                <div class="item-row">
                    <div class="col-num"><?= $mainCounter ?></div>
                    <div class="col-desc">
                        <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                        <?php if (!empty($item['description'])): ?>
                            <div class="text-muted small ps-2"><?= nl2br(htmlspecialchars($item['description'])) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-qty"><?= $item['quantity'] ?></div>
                    <div class="col-unit-price"><?= number_format($unitPrice, 2) ?></div>
                    <div class="col-total fw-bold"><?= number_format($item['total'], 2) ?></div>
                </div>

                <?php // --- Loop สำหรับ Sub-items ---
                if (!empty($item['children'])):
                    $subCounter = 0;
                    foreach ($item['children'] as $subItem):

                        // --- จุดที่ 2: เช็คเงื่อนไขของ Sub-item ---
                        if ($subItem['type'] !== 'show') {
                            continue;
                        }
                        $subCounter++; 
                ?>
                        <div class="item-sub-row ps-4">
                            <div class="col-num"></div>
                            <div class="col-desc">
                                <span class="text-muted"><?= $mainCounter . '.' . $subCounter ?></span>
                                <?= htmlspecialchars($subItem['item_name']) ?>
                                <?php if (!empty($subItem['description'])): ?>
                                    <div class="text-muted small ps-4"><?= nl2br(htmlspecialchars($subItem['description'])) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-qty"></div>
                            <div class="col-unit-price"></div>
                            <div class="col-total"></div>
                        </div>
                <?php 
                    endforeach; 
                endif; 
                ?>
            <?php endforeach; ?>

            <!-- Footer -->
            <div class="item-footer">
                <div class="d-flex justify-content-end">
                    <div class="me-2 text-end fw-bold" style="width:20%;">Sub Total:</div>
                    <div class="text-end" style="width:15%;"><?= number_format($finalSubTotal, 2) ?></div>
                </div>
                <div class="d-flex justify-content-end">
                    <div class="me-2 text-end">VAT (7%):</div>
                    <div class="text-end" style="width:15%;"><?= number_format($finalVatAmount, 2) ?></div>
                </div>
                <div class="d-flex justify-content-end bg-light py-2">
                    <div class="me-2 text-end fw-bold" style="width:20%; font-size: 1.2rem;">Grand Total:</div>
                    <div class="text-end fw-bold" style="width:15%; font-size: 1.2rem;"><?= number_format($finalGrandTotal, 2) ?> ฿</div>
                </div>
                <div class="text-center text-uppercase small mt-2">
                    <strong>(<?= htmlspecialchars($grandTotalInWords) ?> baht only)</strong>
                </div>
                
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/mcvpro/public/quotations/reject/<?= encodeId($q['quotation_id']) ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Reason for Rejection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Please provide a reason:</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/mcvpro/public/js/view_quotation.js"></script>
</body>
</html>

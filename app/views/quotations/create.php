<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <title>Create Quotation</title>
</head>

<body style="background-color: #FFF5EE;">
    <?php
    // --- 1. กำหนดค่าตัวแปรจาก Controller ---
    $fromProject = $data['from_project'] ?? false;
    $customer = $data['customer'] ?? null;
    $project = $data['project'] ?? null;
    $customers = $data['customers'] ?? [];
    $projects = $data['projects'] ?? [];
    ?>

    <div class="container-fluid mt-4 " style="padding-top: 70px;">
        <div class="card shadow mb-4 ">
            <div class="card-header py-3">
                <h4 class="m-0 font-weight-bold text-primary">
                    <?= htmlspecialchars($data['page_title']); ?>
                    <?php // บรรทัดนี้อาจไม่จำเป็นแล้ว ถ้า title ใน controller ถูกต้อง
                        // if (!$fromProject) echo " (Step 1 of 2)"; 
                    ?>
                </h4>
            </div>

            <div class="card-body">
                <form action="/mcvpro/public/quotations/store" method="POST" id="quotation-form">
                    <div class="row mb-4">
                        <?php if ($fromProject && !empty($project) && !empty($customer)) : ?>
                            <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customer['customer_id']) ?>">
                            <input type="hidden" name="project_id" value="<?= htmlspecialchars($project['project_id']) ?>">

                            <div class="col-md-6">
                                <label class="form-label"><strong>Customer</strong></label>
                                <p class="form-control-plaintext bg-light p-2 rounded"><?= htmlspecialchars($customer['company_name']) ?></p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"><strong>Project</strong></label>
                                <p class="form-control-plaintext bg-light p-2 rounded"><?= htmlspecialchars($project['project_name']) ?></p>
                            </div>
                        <?php else : ?>
                            <div class="col-md-6">
                                <label for="customer_id" class="form-label"><strong>1. Select Customer</strong></label>
                                <select id="customer_id" class="form-select" required>
                                    <option value="" disabled selected>-- Select a customer --</option>
                                    <?php foreach ($customers as $cust) : ?>
                                        <option value="<?= htmlspecialchars($cust['customer_id']) ?>">
                                            <?= htmlspecialchars($cust['company_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6" id="project-container" style="display: none;">
                                <label for="project_id" class="form-label"><strong>2. Select Project</strong></label>
                                <select name="project_id" id="project_id" class="form-select" required>
                                    <option value="" selected>-- Please select a project --</option>
                                    <?php foreach ($projects as $proj) : ?>
                                        
                                        <option value="<?= htmlspecialchars($proj['hashed_id']) ?>" data-customer-id="<?= htmlspecialchars($proj['customer_id']) ?>">
                                            <?= htmlspecialchars($proj['project_name']) ?>
                                        </option>

                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($fromProject) : ?>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="valid_until" class="form-label"><strong>Valid Until</strong></label>
                                <input type="date" name="valid_until" id="valid_until" class="form-control"
                                    value="<?= date('Y-m-d', strtotime('+30 days')); ?>" required>
                            </div>

                            <div class="col-md-8">
                                <label for="notes" class="form-label"><strong>Notes / Remark</strong></label>
                                <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <h5 class="text-primary">Quotation Items</h5>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="items-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">No.</th>
                                        <th style="width: 30%;">Item Name</th>
                                        <th style="width: 10%;">Type</th>
                                        <th style="width: 12%;">Cost</th>
                                        <th style="width: 10%;">Margin (%)</th>
                                        <th style="width: 13%;">Price / Unit</th>
                                        <th style="width: 10%;">Quantity</th>
                                        <th style="width: 15%;">Total</th>
                                        <th style="width: 10%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="items-container"> 
                                </tbody>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" id="add-item-btn" class="btn btn-outline-success btn-sm mt-2">➕ Add Item</button>
                        

                        <div class="row mt-4 justify-content-end">
                            <div class="col-md-5">
                                <table class="table">
                                    <tbody>
                                        <tr>
                                            <th class="text-end">Sub Total:</th>
                                            <td>
                                                <input type="text" id="sub-total" name="sub_total"
                                                    class="form-control-plaintext text-end" value="0.00" readonly>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="text-end">VAT (7%):</th>
                                            <td>
                                                <input type="text" id="vat-amount" name="vat_amount"
                                                    class="form-control-plaintext text-end" value="0.00" readonly>
                                            </td>
                                        </tr>
                                        <tr class="table-primary">
                                            <th class="text-end h5">Grand Total:</th>
                                            <td>
                                                <input type="text" id="grand-total" name="grand_total"
                                                    class="form-control-plaintext text-end h5" value="0.00" readonly>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                     

                        <hr>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save Quotation
                            </button>
                            <a href="/mcvpro/public/quotations" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    <?php else: ?>
                        <hr>
                        <div class="text-center">
                            <button type="button" class="btn btn-primary btn-lg" id="next-step-btn">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                            <a href="/mcvpro/public/quotations" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <?php if (isset($prefill_data)): ?>
    <script id="quotation-data" type="application/json">
        <?= json_encode($prefill_data) ?>
    </script>
<?php endif; ?>
    <script src="/mcvpro/public/js/Sortable.min.js"></script>
    <script src="/mcvpro/public/js/quotation.js"></script>
</body>

</html>

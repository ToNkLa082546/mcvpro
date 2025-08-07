<?php
// ดึงข้อมูลจาก $data มาใส่ในตัวแปรที่ใช้ง่าย
$quotation = $data['quotation'] ?? [];
$items = $data['items'] ?? [];
$page_title = "Create New Quotation (from " . htmlspecialchars($quotation['quotation_number'] ?? '') . ")";
?>

<script id="quotation-data" type="application/json">
    <?= json_encode($data); ?>
</script>

<link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">

<div class="card shadow-sm mt-4" id="loaded-quotation-form">
    <form action="/mcvpro/public/quotations/store" method="post" id="quotation-form" novalidate>
        
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= $page_title ?></h5>
            <button type="button" class="btn-close" aria-label="Close" onclick="document.getElementById('quotation-form-container').innerHTML = ''"></button>
        </div>

        <div class="card-body p-4">
            <input type="hidden" name="project_id" value="<?= htmlspecialchars($quotation['project_id'] ?? '') ?>">
            <input type="hidden" name="customer_id" value="<?= htmlspecialchars($quotation['customer_id'] ?? '') ?>">

            <div class="row mb-4">
                <div class="col-md-6"><p><strong>Customer:</strong> <?= htmlspecialchars($quotation['company_name'] ?? 'N/A') ?></p></div>
                <div class="col-md-6"><p><strong>Project:</strong> <?= htmlspecialchars($quotation['project_name'] ?? 'N/A') ?></p></div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Valid Until</label>
                    <input type="date" name="valid_until" class="form-control" value="<?= htmlspecialchars($quotation['valid_until'] ?? date('Y-m-d')) ?>">
                </div>
                 <div class="col-md-6">
 <label class="form-label">Status</label>
<select name="status" class="form-select">
 <option value="Draft" selected>Draft</option>
 <option value="Sent">Sent</option>
</select>
 </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($quotation['notes'] ?? '') ?></textarea>
            </div>
            
            <hr class="my-4">
            <h5 class="mb-3">Quotation Items</h5>
            
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Item Name</th>
                        <th style="width: 10%;">Type</th>
                        <th style="width:12%;">Cost</th>
                        <th style="width:10%;">Margin (%)</th>
                        <th style="width:10%;">Quantity</th>
                        <th style="width:15%;">Total</th>
                        <th style="width:10%;">Actions</th>
                    </tr>
                </thead>
                <tbody id="items-container">
                    </tbody>
            </table>
            <button type="button" id="add-item-btn" class="btn btn-outline-success btn-sm mt-2">➕ Add Item</button>
            
            <div class="row mt-4 justify-content-end">
<div class="col-md-5">
<table class="table table-sm table-borderless">
 <tbody>
<tr>
<th class="text-end">Sub Total:</th>
 <td><input type="text" id="sub-total" class="form-control-plaintext text-end" value="0.00" readonly></td>
 </tr>
 <tr>
 <th class="text-end">VAT (7%):</th>
 <td><input type="text" id="vat-amount" class="form-control-plaintext text-end" value="0.00" readonly></td>
 </tr>
 <tr class="table-primary">
 <th class="text-end h5">Grand Total:</th>
 <td><input type="text" id="grand-total" class="form-control-plaintext text-end h5" value="0.00" readonly></td>
 </tr>
</tbody>
</table>
</div>
 </div>
        </div>

        <div class="card-footer text-end">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('quotation-form-container').innerHTML = ''">Cancel</button>
            <button type="submit" class="btn btn-primary">Save as New Quotation</button>
        </div>
    </form>
</div>
<script src="/mcvpro/public/js/Sortable.min.js"></script>

<script src="/mcvpro/public/js/quotation-form.js"></script>
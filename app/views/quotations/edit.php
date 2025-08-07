<script id="quotation-data" type="application/json">
        <?= $data_for_js; ?>
    </script>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <link rel="stylesheet" href="/mcvpro/public/css/quotation.css">
</head>
<body style="background-color: #FFF5EE;">

<div class="container main-content my-5">
    
    

    <h2 class="mb-4"><?= $page_title ?></h2>
    <div class="card shadow-sm">
        <form action="/mcvpro/public/quotations/revise/<?= encodeId($data['quotation']['quotation_id']) ?>" method="post" id="quotation-form" novalidate>
            <div class="card-body p-4">
                <input type="hidden" name="project_id" value="<?= $data['quotation']['project_id'] ?>">
                <input type="hidden" name="customer_id" value="<?= $data['quotation']['customer_id'] ?>">
                <input type="hidden" name="quotation_number" value="<?= htmlspecialchars($data['quotation']['quotation_number']) ?>">

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Customer</strong></label>
                        <p class="form-control-plaintext bg-light p-2 rounded"><?= htmlspecialchars($data['quotation']['company_name']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Project</strong></label>
                        <p class="form-control-plaintext bg-light p-2 rounded"><?= htmlspecialchars($data['quotation']['project_name']) ?></p>
                    </div>
                </div>
                 <div class="row mb-4">
                    <div class="col-md-6"><label class="form-label">Valid Until</label><input type="date" name="valid_until" class="form-control" value="<?= htmlspecialchars($data['quotation']['valid_until']) ?>"></div>
                    <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><?php $statuses = ['Draft','Sent','Accepted','Rejected']; foreach($statuses as $status) { $selected = ($data['quotation']['status'] == $status) ? 'selected' : ''; echo "<option value='{$status}' {$selected}>{$status}</option>"; } ?></select></div>
                </div>
                <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($data['quotation']['notes']) ?></textarea></div>
                
                <hr class="my-4">
                <h5 class="mb-3">Quotation Items</h5>
                
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th style="width:5%;">No.</th>
                            <th>Item Name</th>
                            <th style="width: 10%;">Type</th>
                            <th style="width:12%;">Cost</th>
                            <th style="width:10%;">Margin (%)</th>
                            <th style="width:13%;">Price / Unit</th>
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
                <a href="/mcvpro/public/quotations" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save New Revision</button>
            </div>
        </form>
    </div>
</div>

<script src="/mcvpro/public/js/quotation.js"></script>
</body>
</html>

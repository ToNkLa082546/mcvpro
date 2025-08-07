<?php
// --- ดึงข้อมูลมาใช้ให้ง่ายขึ้น ---
$q = $data['quotation'];
$items = $data['items'];

// --- 1. คำนวณราคารวม (Roll-up) ---
foreach ($items as &$mainItem) {
    if (!empty($mainItem['children'])) {
        foreach ($mainItem['children'] as $subItem) {
            $mainItem['total'] += $subItem['total'];
        }
    }
}
unset($mainItem);

// --- 2. คำนวณยอดรวมสุดท้ายใหม่ ---
$finalSubTotal = 0;
foreach ($items as $item) {
    $finalSubTotal += $item['total'];
}
$finalVatAmount = $finalSubTotal * 0.07;
$finalGrandTotal = $finalSubTotal + $finalVatAmount;

// --- 3. แปลงเป็นตัวอักษร ---
$grandTotalInWords = '';
if (class_exists('NumberToWords\NumberToWords')) {
    $numberToWords = new NumberToWords\NumberToWords();
    $thaiTransformer = $numberToWords->getNumberTransformer('en');
    $grandTotalInWords = $thaiTransformer->toWords($finalGrandTotal);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Quotation: <?= htmlspecialchars($q['quotation_number']) ?></title>
    
    <link rel="stylesheet" href="/mcvpro/public/css/pdf.css">
</head>
<body>
    <div style="padding: 0.5cm;">
        <table style="margin-bottom: 2rem;">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <h2>[Your Company Name]</h2>
                    <p class="text-muted">[Your Address]<br>[Your Phone & Email]</p>
                </td>
                <td style="width: 40%; text-align: right; vertical-align: top;">
                    <h1 class="text-muted">QUOTATION</h1>
                </td>
            </tr>
        </table>
        
        <table style="margin-bottom: 2rem;">
             <tr>
                <td style="width: 55%; vertical-align: top;">
                    <p style="font-weight: bold; margin-bottom: 5px;">TO:</p>
                    <div style="margin-left: 1rem;">
                        <strong><?= htmlspecialchars($q['company_name']) ?></strong><br>
                        <?= nl2br(htmlspecialchars($q['customer_address'] ?? '')) ?><br>
                        Phone : <?= htmlspecialchars($q['customer_phone'] ?? '') ?><br>
                        Email : <?= htmlspecialchars($q['customer_email'] ?? '') ?>
                    </div>
                </td>
                <td style="width: 45%; vertical-align: top;">
                    <table class="table-borderless">
                        <tr><td class="fw-bold text-end">QUOTE #:</td><td><?= htmlspecialchars($q['quotation_number']) ?></td></tr>
                        <tr><td class="fw-bold text-end">DATE:</td><td><?= date("d F Y", strtotime($q['created_at'])) ?></td></tr>
                        <tr><td class="fw-bold text-end">VALID UNTIL:</td><td><?= $q['valid_until'] ? date("d F Y", strtotime($q['valid_until'])) : '-' ?></td></tr>
                    </table>
                </td>
            </tr>
        </table>
        <p><strong>Project:</strong> <?= htmlspecialchars($q['project_name']) ?></p>

        <table class="table-items">
            <thead>
                <tr>
                    <th class="text-center" style="width:5%;">#</th>
                    <th style="width:50%;">Description</th>
                    <th class="text-end" style="width:10%;">Qty</th>
                    <th class="text-end" style="width:15%;">Unit Price</th>
                    <th class="text-end" style="width:15%;">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $mainCounter = 0;
            // ใช้ตัวแปร $items หรือ $groupedItems ที่ได้จาก Controller
            foreach ($items as $mainItem):
            ?>
                
                <?php // --- 1. แสดง Main Item (ถ้า Type เป็น 'show') --- ?>
                <?php if ($mainItem['type'] === 'show'): 
                    $mainCounter++;
                    // คำนวณราคาต่อหน่วยใหม่จากยอดรวมที่ถูกบวกค่า sub-items ไปแล้ว
                    $unitPrice = ($mainItem['quantity'] > 0) ? ($mainItem['total'] / $mainItem['quantity']) : 0;
                ?>
                    <tr>
                        <td class="text-center fw-bold"><?= $mainCounter ?></td>
                        <td>
                            <strong class="fw-bold"><?= htmlspecialchars($mainItem['item_name']) ?></strong>
                            <?php if (!empty($mainItem['description'])): ?>
                                <div class="small text-muted" style="padding-left: 1rem;"><?= nl2br(htmlspecialchars($mainItem['description'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= htmlspecialchars($mainItem['quantity']) ?></td>
                        <td class="text-end"><?= number_format($unitPrice, 2) ?></td>
                        <td class="text-end fw-bold"><?= number_format($mainItem['total'], 2) ?></td>
                    </tr>
                <?php endif; ?>

                <?php // --- 2. แสดง Sub-items (ถ้ามี และ Type เป็น 'show' แต่ไม่แสดงราคา) --- ?>
                <?php if (!empty($mainItem['children'])):
                    $subCounter = 0;
                    foreach ($mainItem['children'] as $subItem):
                        if ($subItem['type'] === 'show'):
                            $subCounter++;
                ?>
                            <tr>
                                <td></td>
                                <td class="ps-4" colspan="4">  <?php /* ใช้ colspan เพื่อให้ข้อความเต็มความกว้าง */ ?>
                                    <span class="text-muted"><?= $mainCounter . '.' . $subCounter ?></span>
                                    <?= htmlspecialchars($subItem['item_name']) ?>
                                    <?php if (!empty($subItem['description'])): ?>
                                        <div class="small text-muted" style="padding-left: 1.5rem;"><?= nl2br(htmlspecialchars($subItem['description'])) ?></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php endforeach; ?>
        </tbody>
        </table>
        
        <div style="margin-top: 20px;">
             <table class="table-borderless">
                <tr>
                    <td style="width: 60%;"></td>
                    <td style="width: 40%;">
                        <table class="table-borderless">
                             <tr>
                                <td class="text-end fw-bold">Sub Total</td>
                                <td class="text-end" style="width: 35%;"><?= number_format($finalSubTotal, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="text-end">VAT (7%)</td>
                                <td class="text-end"><?= number_format($finalVatAmount, 2) ?></td>
                            </tr>
                            <tr style="font-size: 1.2em; border-top: 2px solid #333; border-bottom: 2px solid #333;">
                                <td class="text-end fw-bold">Grand Total</td>
                                <td class="text-end fw-bold"><?= number_format($finalGrandTotal, 2) ?> ฿</td>
                            </tr>
                        </table>
                    </td>
                </tr>
             </table>
        </div>
        <div style="text-align: center; margin-top: 10px;" class="text-uppercase small">
            <strong>(<?= htmlspecialchars($grandTotalInWords) ?> baht only)</strong>
        </div>

        <table style="width: 100%; margin-top: 5rem;">
            <tr>
                <!-- Authorized Signature -->
                <td style="width: 40%; text-align: center; vertical-align: top;">
                    <!-- ลายเซ็นภาพ -->
                    <img src="/mcvpro/public/images/signature.png" alt="Signature" style="max-height: 60px; margin-bottom: 5px;"><br>

                    <!-- เส้นลายเซ็น -->
                    <table style="width: 100%; border-top: 1px solid #333; margin-bottom: 5px;">
                        <tr><td>&nbsp;</td></tr>
                    </table>
                    <!-- คำอธิบาย -->
                    Authorized Signature
                </td>

                <!-- เว้นตรงกลาง -->
                <td style="width: 20%;"></td>
                                        
                <!-- Customer Signature -->
                <td style="width: 40%; text-align: center; vertical-align: top;">
                    <img src="/mcvpro/public/images/signature.png" alt="Signature" style="max-height: 60px; margin-bottom: 5px;"><br>
                    <table style="width: 100%; border-top: 1px solid #333; margin-bottom: 5px;">
                        <tr><td>&nbsp;</td></tr>
                    </table>
                    Customer Signature
                </td>
            </tr>
        </table>

    </div>
</body>
</html>
<?php
// 1. Setup และ Autoloading
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ต้องแน่ใจว่า Path นี้ถูกต้อง
require_once __DIR__ . '/../vendor/autoload.php';

// ประกาศใช้ Class ที่จำเป็น
use Mpdf\Mpdf;
use NumberToWords\NumberToWords;

// --- 2. ส่วนดึงข้อมูล (คุณต้องปรับส่วนนี้ให้เข้ากับระบบของคุณ) ---
// ส่วนนี้เป็นเพียงตัวอย่าง คุณต้องมีวิธีในการสร้าง instance ของ pdo และ models
/*
// ตัวอย่างการดึงข้อมูล
try {
    // $pdo = new PDO(...); // สร้าง PDO connection ของคุณ
    // $quotationModel = new App\Models\Quotation($pdo);
    // $itemModel = new App\Models\QuotationItem($pdo);

    $quotationId = $_GET['id'] ?? 0;
    if (!$quotationId) die("Quotation ID is required.");

    $q = $quotationModel->getById($quotationId);
    $itemsData = $itemModel->getAllForQuotation($quotationId);

    if (!$q) die("Quotation not found.");

} catch (Exception $e) {
    die("Error fetching data: " . $e->getMessage());
}
*/
// --- จบส่วนดึงข้อมูล ---


// 3. จัดกลุ่มข้อมูล Items และ Sub-items
$groupedItems = [];
$itemMap = [];
foreach ($itemsData as $item) {
    $itemMap[$item['item_id']] = $item;
}
foreach ($itemMap as &$item) {
    if (isset($item['parent_item_id']) && isset($itemMap[$item['parent_item_id']])) {
        $itemMap[$item['parent_item_id']]['children'][] = &$item;
    } else {
        $groupedItems[] = &$item;
    }
}
unset($item);

// 4. แปลงยอด Grand Total เป็นตัวอักษรภาษาไทย
$numberToWords = new NumberToWords();
$thaiTransformer = $numberToWords->getNumberTransformer('en');
$grandTotalInWords = $thaiTransformer->toWords($q['grand_total']);


// 5. เริ่มสร้าง HTML จาก Template โดยใช้ Output Buffering
ob_start();
// ส่งตัวแปรที่จำเป็นเข้าไปให้เทมเพลต
$data = [
    'quotation' => $q, 
    'items' => $groupedItems,
    'grand_total_words' => $grandTotalInWords
];
include '/../app/views/quotations/pdf_template.php'; // เรียกใช้ไฟล์เทมเพลต
$html = ob_get_clean();


// 6. สร้าง PDF ด้วย Mpdf
try {
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'default_font_size' => 12,
        'default_font' => 'thsarabun' // ใช้ฟอนต์สารบรรณ
    ]);

    $mpdf->WriteHTML($html);
    ob_end_clean(); 
    // แสดงผล PDF ในบราวเซอร์
    $mpdf->Output("quotation-{$q['quotation_number']}.pdf", \Mpdf\Output\Destination::INLINE);

} catch (\Mpdf\MpdfException $e) {
    die("Mpdf error: " . $e->getMessage());
}

exit();
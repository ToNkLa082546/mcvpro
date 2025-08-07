<?php

class PdfService 
{
    private $pdf;

    public function __construct()
    {
        // สร้าง instance ของ TCPDF
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // ตั้งค่าพื้นฐาน
        $this->pdf->SetCreator('MCVPro');
        $this->pdf->SetAuthor($_SESSION['user_fname'] ?? 'MCVPro');
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->SetMargins(5, 5, 5);
        $this->pdf->SetFont('thsarabunpsk', '', 12);
    }

    /**
     * สร้าง PDF สำหรับใบเสนอราคา
     * @param array $quotationData
     * @param array $itemsData
     */
    public function generateQuotation(array $quotationData, array $itemsData)
    {
        $q = $quotationData;

        $this->pdf->SetTitle('Quotation ' . htmlspecialchars($q['quotation_number']));
        $this->pdf->AddPage();

        $html = <<<EOD
<style>
    body { font-family: 'thsarabunpsk', sans-serif; font-size: 12pt; color: #000; }
    h1 { font-size: 22pt; text-align: right; color: #555; }
    h2 { font-size: 16pt; margin: 0; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .text-left { text-align: left; }
    .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .table th, .table td { border: 1px solid #ccc; padding: 6px; }
    .table th { background-color: #f2f2f2; }
    .summary-table td { padding: 5px; }
    .no-border td { border: none !important; }
    .section-title { background-color: #f2f2f2;  font-weight: bold; }
    word-wrap: break-word;
</style>

<table width="100%" cellpadding="0" border="0">
    <tr>
        <td width="50%" style="vertical-align: middle;">
            <h2>[Your Company Name]</h2>
            <p>[Your Address]<br>[Your Phone & Email]</p>
        </td>
        
        <td width="50%" style="vertical-align: middle;">
            <h1 style="text-align: right;">QUOTATION</h1>
        </td>
    </tr>
</table>

<hr><br>

<table width="100%" style="table-layout: auto;"> <!-- แก้ตรงนี้ -->
    <tr>
        <td width="60%" style="vertical-align: top; word-wrap: break-word;">
            <div class="section-title">TO: {$this->escape($q['company_name'])}</div>
            <p>{$this->escape($q['customer_address'])}</p>
            <p>📞 {$this->escape($q['customer_phone'])}<br>📧 {$this->escape($q['customer_email'])}</p>
        </td>
        <td width="40%" style="vertical-align: top;">
            <table class="table no-border">
                <tr>
                    <th>QUOTE #</th><td>{$this->escape($q['quotation_number'])}</td>
                </tr>
                <tr>
                    <th>DATE</th><td>{$this->formatDate($q['created_at'])}</td>
                </tr>
                <tr>
                    <th>VALID UNTIL</th><td>{$this->formatDate($q['valid_until'])}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>



<br><p><strong>Project:</strong> {$this->escape($q['project_name'])}</p><br>

<table class="table">
    <thead>
        <tr>
            <th width="5%">#</th>
            <th width="45%">Description</th>
            <th width="10%" class="text-center">Qty</th>
            <th width="20%" class="text-right">Unit Price</th>
            <th width="20%" class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
EOD;

        // รายการสินค้า
        $i = 1;
        foreach ($itemsData as $item) {
            $unitPrice = ($item['margin'] < 100 && $item['margin'] >= 0)
                ? $item['cost'] / (1 - ($item['margin'] / 100))
                : $item['cost'];

            $html .= '
<tr>
    <td class="text-center">' . $i++ . '</td>
    <td>' . $this->escape($item['item_name']) . '</td>
    <td class="text-center">' . (int)$item['quantity'] . '</td>
    <td class="text-right">' . number_format($unitPrice, 2) . '</td>
    <td class="text-right">' . number_format($item['total'], 2) . '</td>
</tr>';
        }

        $html .= '</tbody></table>';

        // Summary
        $html .= '
<table class="table no-border" style="margin-top:10px;">
    <tr>
        <td width="60%"></td>
        <td width="40%">
            <table class="summary-table" width="100%">
                <tr>
                    <td>Sub Total</td>
                    <td class="text-right">' . number_format($q['sub_total'], 2) . '</td>
                </tr>
                <tr>
                    <td>VAT (7%)</td>
                    <td class="text-right">' . number_format($q['vat_amount'], 2) . '</td>
                </tr>
                <tr style="font-weight:bold;">
                    <td>Grand Total</td>
                    <td class="text-right">' . number_format($q['grand_total'], 2) . ' ฿</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<br><br>
<div style="text-align:center; font-size:14pt;"><strong>' . $this->escape(ucfirst($q['grand_total_text'])) . ' baht only.</strong></div>
';

        // Notes
        if (!empty($q['notes'])) {
            $html .= '<br><br><div class="section-title">Notes / Terms & Conditions:</div>';
            $html .= '<p>' . nl2br($this->escape($q['notes'])) . '</p>';
        }

        $this->pdf->writeHTML($html, true, false, true, false, '');
        $this->pdf->Output('quotation-' . $this->escape($q['quotation_number']) . '.pdf', 'I');
    }

    /**
     * Escape HTML safely
     */
    private function escape($text)
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Format date or return "-"
     */
    private function formatDate($date)
    {
        if (empty($date) || $date === '0000-00-00') {
            return '-';
        }
        return date('d F Y', strtotime($date));
    }
}

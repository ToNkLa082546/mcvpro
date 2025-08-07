<h2>Quotation for <?= htmlspecialchars($member) ?></h2>
<table class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>Quotation No</th>
      <th>Status</th>
      <th>Value</th>
      <th>Date</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($quotations as $q): ?>
    <tr>
      <td><?= htmlspecialchars($q['quotation_no']) ?></td>
      <td><?= htmlspecialchars($q['status']) ?></td>
      <td>฿ <?= number_format($q['total'], 2) ?></td>
      <td><?= htmlspecialchars($q['created_at']) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

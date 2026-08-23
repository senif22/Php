<h2 style="margin:0 0 16px;font-size:20px;color:#1f2933;">Account status updated</h2>

<p style="margin:0 0 16px;">
    Hello <?= esc($customer['name']) ?>, the status on your Legacy CRM account has
    been changed.
</p>

<p style="margin:0 0 20px;font-size:15px;">
    <span style="display:inline-block;padding:4px 10px;border-radius:4px;background-color:#e4e7eb;color:#52606d;"><?= esc($oldStatus) ?></span>
    <span style="color:#7b8794;">&rarr;</span>
    <span style="display:inline-block;padding:4px 10px;border-radius:4px;background-color:#0d6efd;color:#ffffff;"><?= esc($newStatus) ?></span>
</p>

<p style="margin:0;color:#7b8794;font-size:13px;">
    If you have any questions, please contact your account manager.
</p>

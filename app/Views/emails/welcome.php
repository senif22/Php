<h2 style="margin:0 0 16px;font-size:20px;color:#1f2933;">Welcome, <?= esc($customer['name']) ?>!</h2>

<p style="margin:0 0 16px;">
    Thank you for joining Legacy CRM. Your account has been created and our team
    will be in touch shortly.
</p>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 20px;border-collapse:collapse;font-size:14px;">
    <tr>
        <td style="padding:8px 0;color:#7b8794;width:110px;">Name</td>
        <td style="padding:8px 0;"><?= esc($customer['name']) ?></td>
    </tr>
    <tr>
        <td style="padding:8px 0;color:#7b8794;">Email</td>
        <td style="padding:8px 0;"><?= esc($customer['email']) ?></td>
    </tr>
    <?php if (! empty($customer['company'])): ?>
    <tr>
        <td style="padding:8px 0;color:#7b8794;">Company</td>
        <td style="padding:8px 0;"><?= esc($customer['company']) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (! empty($customer['city'])): ?>
    <tr>
        <td style="padding:8px 0;color:#7b8794;">City</td>
        <td style="padding:8px 0;"><?= esc($customer['city']) ?></td>
    </tr>
    <?php endif; ?>
</table>

<?php if (! empty($assignedTo)): ?>
<p style="margin:0 0 16px;">
    Your account manager is <strong><?= esc($assignedTo) ?></strong>.
</p>
<?php endif; ?>

<p style="margin:0;color:#7b8794;font-size:13px;">
    If you did not expect this email, you can safely ignore it.
</p>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($subject ?? 'Legacy CRM') ?></title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;font-family:Helvetica,Arial,sans-serif;color:#1f2933;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e4e7eb;">
                    <tr>
                        <td style="background-color:#0d6efd;padding:20px 24px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;letter-spacing:0.3px;">Legacy CRM</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;font-size:15px;line-height:1.6;">
                            <?= $content ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px;background-color:#f8f9fa;border-top:1px solid #e4e7eb;font-size:12px;color:#7b8794;">
                            This is an automated message from Legacy CRM. Please do not reply to this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

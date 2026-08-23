<?php

namespace App\Commands;

use App\Models\CustomerModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class EmailPreview extends BaseCommand
{
    protected $group = 'Email';
    protected $name = 'email:preview';
    protected $description = 'Render a customer email template to writable/ without sending it.';
    protected $usage = 'email:preview [customerId] [welcome|status]';

    public function run(array $params)
    {
        $customerId = (int) ($params[0] ?? 1);
        $template = $params[1] ?? 'welcome';

        $customer = (new CustomerModel())->find($customerId);

        if ($customer === null) {
            CLI::error('Customer ' . $customerId . ' not found');

            return EXIT_ERROR;
        }

        if ($template === 'welcome') {
            $subject = 'Welcome to Legacy CRM, ' . $customer['name'];
            $content = view('emails/welcome', ['customer' => $customer, 'assignedTo' => 'Sales User']);
        } else {
            $subject = 'Your Legacy CRM account status has changed';
            $content = view('emails/customer_status_changed', [
                'customer' => $customer,
                'oldStatus' => 'pending',
                'newStatus' => 'active',
            ]);
        }

        $html = view('emails/layout', ['subject' => $subject, 'content' => $content]);
        $path = WRITEPATH . 'email-preview-' . $template . '.html';
        file_put_contents($path, $html);

        CLI::write('Subject : ' . $subject);
        CLI::write('To      : ' . $customer['email']);
        CLI::write('Bytes   : ' . strlen($html));
        CLI::write('Saved   : ' . $path);

        return EXIT_SUCCESS;
    }
}

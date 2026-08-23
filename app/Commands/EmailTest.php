<?php

namespace App\Commands;

use App\Models\CustomerModel;
use App\Services\EmailService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class EmailTest extends BaseCommand
{
    protected $group = 'Email';
    protected $name = 'email:test';
    protected $description = 'Send the welcome email for a customer through the configured SMTP server.';
    protected $usage = 'email:test [customerId]';

    public function run(array $params)
    {
        $customer = (new CustomerModel())->find((int) ($params[0] ?? 1));

        if ($customer === null) {
            CLI::error('Customer not found');

            return EXIT_ERROR;
        }

        $sent = (new EmailService())->sendWelcome($customer);

        CLI::write('sendWelcome() returned: ' . var_export($sent, true));
        CLI::write($sent ? 'Check your Mailtrap inbox.' : 'Not sent - see writable/logs for the reason.');

        return EXIT_SUCCESS;
    }
}

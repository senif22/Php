<?php

namespace App\Services;

use App\Models\UserModel;
use CodeIgniter\Email\Email;
use Throwable;

class EmailService
{
    protected Email $email;

    protected bool $enabled;

    public function __construct(?Email $email = null)
    {
        $this->email = $email ?? service('email');
        $this->enabled = (bool) (env('email.enabled') ?? true);
    }

    public function sendWelcome(array $customer): bool
    {
        if (empty($customer['email'])) {
            log_message('warning', 'Welcome email skipped: customer {id} has no email address', [
                'id' => $customer['id'] ?? '?',
            ]);

            return false;
        }

        return $this->send(
            $customer['email'],
            'Welcome to Legacy CRM, ' . $customer['name'],
            'emails/welcome',
            [
                'customer' => $customer,
                'assignedTo' => $this->assignedName($customer),
            ]
        );
    }

    public function sendStatusChanged(array $customer, string $oldStatus, string $newStatus): bool
    {
        if (empty($customer['email']) || $oldStatus === $newStatus) {
            return false;
        }

        return $this->send(
            $customer['email'],
            'Your Legacy CRM account status has changed',
            'emails/customer_status_changed',
            [
                'customer' => $customer,
                'oldStatus' => $oldStatus,
                'newStatus' => $newStatus,
            ]
        );
    }

    public function send(string $to, string $subject, string $template, array $data = []): bool
    {
        if (! $this->enabled) {
            log_message('info', 'Email disabled, skipped "{subject}" to {to}', [
                'subject' => $subject,
                'to' => $to,
            ]);

            return false;
        }

        try {
            $body = view('emails/layout', [
                'subject' => $subject,
                'content' => view($template, $data),
            ]);

            $this->email->clear(true);
            $this->email->setTo($to);
            $this->email->setSubject($subject);
            $this->email->setMessage($body);
            $this->email->setMailType('html');

            if ($this->email->send(false) === false) {
                log_message('error', 'Email "{subject}" to {to} failed: {debug}', [
                    'subject' => $subject,
                    'to' => $to,
                    'debug' => strip_tags($this->email->printDebugger(['headers'])),
                ]);

                return false;
            }
        } catch (Throwable $e) {
            log_message('error', 'Email "{subject}" to {to} threw {class}: {message}', [
                'subject' => $subject,
                'to' => $to,
                'class' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        log_message('info', 'Email "{subject}" sent to {to}', [
            'subject' => $subject,
            'to' => $to,
        ]);

        return true;
    }

    protected function assignedName(array $customer): ?string
    {
        if (empty($customer['assigned_to'])) {
            return null;
        }

        $user = (new UserModel())->find($customer['assigned_to']);

        return $user['name'] ?? null;
    }
}

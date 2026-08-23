<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UserModel;

class UserSeeder extends Seeder
{
    public function run()
    {
        $userModel = new UserModel();

        $this->db->query('UPDATE `customers` SET `assigned_to` = NULL');
        $this->db->query('DELETE FROM `users`');
        $this->db->query('ALTER TABLE `users` AUTO_INCREMENT = 1');

        $adminId = $this->createUser($userModel, 'Admin User', 'admin@crm.test', 'admin123', 'admin', null);
        $managerId = $this->createUser($userModel, 'Manager User', 'manager@crm.test', 'manager123', 'manager', null);
        $salesId = $this->createUser($userModel, 'Sales User', 'sales@crm.test', 'sales123', 'sales', $managerId);
        $soloSalesId = $this->createUser($userModel, 'Solo Sales', 'solo@crm.test', 'solo1234', 'sales', null);

        $customerIds = array_column(
            $this->db->table('customers')->select('id')->orderBy('id')->get()->getResultArray(),
            'id'
        );

        $buckets = [
            $salesId => array_slice($customerIds, 0, 40),
            $soloSalesId => array_slice($customerIds, 40, 30),
            $managerId => array_slice($customerIds, 70, 20),
        ];

        foreach ($buckets as $userId => $ids) {
            if ($ids === []) {
                continue;
            }

            $this->db->table('customers')->whereIn('id', $ids)->update(['assigned_to' => $userId]);
        }

        $unassigned = $this->db->table('customers')->where('assigned_to', null)->countAllResults();

        echo 'Seeded 4 users (admin, manager, sales, solo sales).' . PHP_EOL;
        echo 'Assigned customers: sales=40, solo=30, manager=20, unassigned=' . $unassigned . PHP_EOL;
    }

    protected function createUser(UserModel $model, string $name, string $email, string $password, string $role, ?int $managerId): int
    {
        $inserted = $model->insert([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'manager_id' => $managerId,
        ]);

        if ($inserted === false) {
            throw new \RuntimeException('Could not seed ' . $email . ': ' . implode(' ', $model->errors()));
        }

        return (int) $model->getInsertID();
    }
}

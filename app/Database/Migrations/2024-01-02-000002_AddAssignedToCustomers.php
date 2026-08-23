<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAssignedToCustomers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('customers', [
            'assigned_to' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'status',
            ],
        ]);

        $this->db->query('ALTER TABLE `customers` ADD KEY `customers_assigned_to` (`assigned_to`)');
        $this->db->query('ALTER TABLE `customers` ADD CONSTRAINT `customers_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `customers` DROP FOREIGN KEY `customers_assigned_to_foreign`');
        $this->forge->dropColumn('customers', 'assigned_to');
    }
}

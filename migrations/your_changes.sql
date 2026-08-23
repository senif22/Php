-- Schema changes for the RBAC work (Part 4).
-- Same thing the two CI4 migrations do, if you'd rather run SQL directly:
--   2024-01-02-000001_CreateUsersTable
--   2024-01-02-000002_AddAssignedToCustomers
--
-- Run against the database that already has `customers` and
-- `customer_activities` from the original migrations.

-- ---------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','sales') NOT NULL DEFAULT 'sales',
  `manager_id` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `users_manager_id_foreign` (`manager_id`),
  CONSTRAINT `users_manager_id_foreign`
    FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `manager_id` points at another user. That is how "their team" is defined
-- for the manager role - a manager's team is every user whose manager_id
-- is that manager.

-- ---------------------------------------------------------------------
-- customers.assigned_to
-- ---------------------------------------------------------------------

ALTER TABLE `customers`
  ADD COLUMN `assigned_to` int unsigned DEFAULT NULL AFTER `status`;

ALTER TABLE `customers`
  ADD KEY `customers_assigned_to` (`assigned_to`);

ALTER TABLE `customers`
  ADD CONSTRAINT `customers_assigned_to_foreign`
  FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- Nullable on purpose. Existing customers stay valid, and deleting a user
-- unassigns their customers instead of deleting them.

-- ---------------------------------------------------------------------
-- Seed data - test users
-- ---------------------------------------------------------------------
-- Passwords are bcrypt hashes of:
--   admin@crm.test   admin123
--   manager@crm.test manager123
--   sales@crm.test   sales123
--   solo@crm.test    solo1234

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `manager_id`, `created_at`, `updated_at`) VALUES
(1, 'Admin User',   'admin@crm.test',   '$2y$10$TjWvmO9u7NO5K8ZRMjOvOeHAcbXkXHCpNb6HzVGbfqCyuLxw7GwHy', 'admin',   NULL, NOW(), NOW()),
(2, 'Manager User', 'manager@crm.test', '$2y$10$cbsyp9NJqvKDGj4bgGvKmeTg6oXqymRTyfLYpZD72rakV.XeDx0ga', 'manager', NULL, NOW(), NOW()),
(3, 'Sales User',   'sales@crm.test',   '$2y$10$v5Y4qfeHVhokW6iXqvYo8.qkMUgbu/Cw23wB.oT6qVDdEuB3JG4se', 'sales',   2,    NOW(), NOW()),
(4, 'Solo Sales',   'solo@crm.test',    '$2y$10$tsdlO978HJpczPMiNrDgye3aqXUgMzEbTdl5ePAwc1FZBKXFU8wYK', 'sales',   NULL, NOW(), NOW());

-- Solo Sales has no manager on purpose. Without a sales user outside the
-- manager's team, "manager sees everything" and "manager sees only their
-- team" give the same result and the RBAC test proves nothing.

-- ---------------------------------------------------------------------
-- Seed data - customer assignment
-- ---------------------------------------------------------------------
-- Matches app/Database/Seeds/UserSeeder.php:
--   customers 1-40   -> Sales User (3)
--   customers 41-70  -> Solo Sales (4)
--   customers 71-90  -> Manager User (2)
--   customers 91-100 -> unassigned, admin only

UPDATE `customers` SET `assigned_to` = 3 WHERE `id` BETWEEN 1  AND 40;
UPDATE `customers` SET `assigned_to` = 4 WHERE `id` BETWEEN 41 AND 70;
UPDATE `customers` SET `assigned_to` = 2 WHERE `id` BETWEEN 71 AND 90;

-- ---------------------------------------------------------------------
-- Rollback
-- ---------------------------------------------------------------------

-- ALTER TABLE `customers` DROP FOREIGN KEY `customers_assigned_to_foreign`;
-- ALTER TABLE `customers` DROP COLUMN `assigned_to`;
-- DROP TABLE `users`;

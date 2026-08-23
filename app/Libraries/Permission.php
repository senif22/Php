<?php

namespace App\Libraries;

use App\Models\UserModel;

class Permission
{
    public const ADMIN = 'admin';
    public const MANAGER = 'manager';
    public const SALES = 'sales';

    public static function role(): ?string
    {
        return session()->get('role');
    }

    public static function userId(): ?int
    {
        $id = session()->get('user_id');

        return $id === null ? null : (int) $id;
    }

    public static function isAdmin(): bool
    {
        return self::role() === self::ADMIN;
    }

    public static function isManager(): bool
    {
        return self::role() === self::MANAGER;
    }

    public static function visibleUserIds(): ?array
    {
        if (self::isAdmin() || self::isManager()) {
            return null;
        }

        return [self::userId()];
    }

    public static function editableUserIds(): ?array
    {
        if (self::isAdmin()) {
            return null;
        }

        if (self::isManager()) {
            return (new UserModel())->teamMemberIds(self::userId());
        }

        return [self::userId()];
    }

    protected static function assignedToOneOf(array $customer, ?array $ids): bool
    {
        if ($ids === null) {
            return true;
        }

        return $customer['assigned_to'] !== null
            && in_array((int) $customer['assigned_to'], $ids, true);
    }

    public static function canView(array $customer): bool
    {
        return self::assignedToOneOf($customer, self::visibleUserIds());
    }

    public static function canEdit(array $customer): bool
    {
        return self::assignedToOneOf($customer, self::editableUserIds());
    }

    public static function canDelete(array $customer): bool
    {
        return self::isAdmin();
    }

    public static function canCreate(): bool
    {
        return self::role() !== null;
    }
}

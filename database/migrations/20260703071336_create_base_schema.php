<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class CreateBaseSchema extends AbstractMigration
{
    public function change(): void
    {
        // 1. Hotel Groups
        $tableHotelGroups = $this->table('hotel_groups', ['id' => true, 'signed' => false]);
        $tableHotelGroups->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->create();

        // 2. Hotels
        $tableHotels = $this->table('hotels', ['id' => true, 'signed' => false]);
        $tableHotels->addColumn('hotel_group_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('address', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addForeignKey('hotel_group_id', 'hotel_groups', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();

        // 3. Users
        $tableUsers = $this->table('users', ['id' => true, 'signed' => false]);
        $tableUsers->addColumn('username', 'string', ['limit' => 100])
            ->addColumn('password_hash', 'string', ['limit' => 255])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('phone', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addIndex(['username'], ['unique' => true])
            ->addIndex(['email'], ['unique' => true])
            ->addIndex(['phone'], ['unique' => true])
            ->create();

        // 4. Roles
        $tableRoles = $this->table('roles', ['id' => true, 'signed' => false]);
        $tableRoles->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addIndex(['name'], ['unique' => true])
            ->create();

        // 5. Permissions
        $tablePermissions = $this->table('permissions', ['id' => true, 'signed' => false]);
        $tablePermissions->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['name'], ['unique' => true])
            ->create();

        // 6. Role Permissions
        $tableRolePermissions = $this->table('role_permissions', ['id' => false, 'primary_key' => ['role_id', 'permission_id']]);
        $tableRolePermissions->addColumn('role_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('permission_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('permission_id', 'permissions', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        // 7. User Roles
        $tableUserRoles = $this->table('user_roles', ['id' => false, 'primary_key' => ['user_id', 'role_id']]);
        $tableUserRoles->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('role_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        // 8. User Hotel Access
        $tableUserHotelAccess = $this->table('user_hotel_access', ['id' => false, 'primary_key' => ['user_id', 'hotel_id']]);
        $tableUserHotelAccess->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        // 9. Audit Logs
        $tableAuditLogs = $this->table('audit_logs', ['id' => false, 'primary_key' => ['id']]);
        $tableAuditLogs->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('entity_type', 'string', ['limit' => 100])
            ->addColumn('entity_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('hotel_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('action', 'string', ['limit' => 100])
            ->addColumn('old_value', 'json', ['null' => true])
            ->addColumn('new_value', 'json', ['null' => true])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('hotel_id', 'hotels', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }
}

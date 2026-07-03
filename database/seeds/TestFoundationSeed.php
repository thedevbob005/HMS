<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class TestFoundationSeed extends AbstractSeed
{
    public function getDependencies(): array
    {
        return [];
    }

    public function run(): void
    {
        // 1. Hotel Group
        $hotelGroupsTable = $this->table('hotel_groups');
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $hotelGroupsTable->truncate();
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

        $hotelGroupsTable->insert([
            [
                'id' => 1,
                'name' => 'Grand Palace Group'
            ]
        ])->saveData();

        // 2. Hotel
        $hotelsTable = $this->table('hotels');
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $hotelsTable->truncate();
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

        $hotelsTable->insert([
            [
                'id' => 1,
                'hotel_group_id' => 1,
                'name' => 'Grand Palace Hotel',
                'address' => '123 Luxury Avenue, New Delhi'
            ]
        ])->saveData();

        // 3. User
        $usersTable = $this->table('users');
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $usersTable->truncate();
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

        // password is 'password' hashed with bcrypt
        $passwordHash = password_hash('password', PASSWORD_BCRYPT);
        $usersTable->insert([
            [
                'id' => 1,
                'username' => 'testmanager',
                'password_hash' => $passwordHash,
                'email' => 'manager@grandpalace.com',
                'phone' => '9999999999',
                'is_active' => 1
            ],
            [
                'id' => 2,
                'username' => 'unauthorizeduser',
                'password_hash' => $passwordHash,
                'email' => 'unauthorized@grandpalace.com',
                'phone' => '8888888888',
                'is_active' => 1
            ]
        ])->saveData();

        // 4. Role
        $rolesTable = $this->table('roles');
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $rolesTable->truncate();
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

        $rolesTable->insert([
            [
                'id' => 1,
                'name' => 'manager',
                'description' => 'Hotel Manager'
            ]
        ])->saveData();

        // 5. Permission
        $permissionsTable = $this->table('permissions');
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $permissionsTable->truncate();
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

        $permissionsTable->insert([
            [
                'id' => 1,
                'name' => 'sample.view',
                'description' => 'View the protected sample endpoint'
            ]
        ])->saveData();

        // 6. Role Permission Links
        $rolePermissionsTable = $this->table('role_permissions');
        $rolePermissionsTable->truncate();
        $rolePermissionsTable->insert([
            [
                'role_id' => 1,
                'permission_id' => 1
            ]
        ])->saveData();

        // 7. User Role Links
        $userRolesTable = $this->table('user_roles');
        $userRolesTable->truncate();
        $userRolesTable->insert([
            [
                'user_id' => 1,
                'role_id' => 1
            ]
        ])->saveData();

        // 8. User Hotel Access Links
        $userHotelAccessTable = $this->table('user_hotel_access');
        $userHotelAccessTable->truncate();
        $userHotelAccessTable->insert([
            [
                'user_id' => 1,
                'hotel_id' => 1
            ]
        ])->saveData();

        // 9. User Token (active test Bearer token)
        $userTokensTable = $this->table('user_tokens');
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $userTokensTable->truncate();
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

        $tomorrow = date('Y-m-d H:i:s', strtotime('+1 day'));
        $userTokensTable->insert([
            [
                'id' => 1,
                'user_id' => 1,
                'token' => 'test_token_123456',
                'expires_at' => $tomorrow
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'token' => 'unauthorized_token_789',
                'expires_at' => $tomorrow
            ]
        ])->saveData();
    }
}

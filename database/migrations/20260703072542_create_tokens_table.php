<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class CreateTokensTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('user_tokens', ['id' => true, 'signed' => false]);
        $table->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('token', 'string', ['limit' => 100])
            ->addColumn('expires_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['token'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}

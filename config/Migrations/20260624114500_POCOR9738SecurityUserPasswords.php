<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9738SecurityUserPasswords extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('DROP TABLE IF EXISTS `security_user_passwords`');

        $table = $this->table('security_user_passwords', [
            'comment' => 'Stores previous passwords for password rotation',
            'id' => false,
            'primary_key' => 'id',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $table
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'null' => false,
            ])
            ->addColumn('security_user_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to security_users.id',
            ])
            ->addColumn('old_password', 'char', [
                'limit' => 60,
                'null' => false,
                'default' => '',
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => false,
                'limit' => 11,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['security_user_id'])
            ->addIndex(['created_user_id'])
            ->create();

        $this->execute(
            'ALTER TABLE `security_user_passwords` ' .
            'ADD CONSTRAINT `secur_user_passw_fk_user_id` FOREIGN KEY (`security_user_id`) REFERENCES `security_users` (`id`)'
        );
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE `security_user_passwords` DROP FOREIGN KEY `secur_user_passw_fk_user_id`');
        $this->execute('DROP TABLE IF EXISTS `security_user_passwords`');
    }
}

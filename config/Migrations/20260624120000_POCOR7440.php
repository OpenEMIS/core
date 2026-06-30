<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR7440 extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_9738_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_9738_config_items` SELECT * FROM `config_items`');
        $this->execute('DROP TABLE IF EXISTS `security_user_passwords`');

        $exists = $this->fetchRow("SELECT `id` FROM `config_items` WHERE `code` = 'password_rotation'");
        if (empty($exists)) {
            $this->execute("INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
                ('Password Rotation', 'password_rotation', 'Password', 'Password Rotation', '0', '', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', CURRENT_TIMESTAMP)");
        }


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
        $this->execute("DELETE FROM `config_items` WHERE `code` = 'password_rotation'");
        $this->execute('DROP TABLE IF EXISTS `zz_9738_config_items`');

         $this->execute('ALTER TABLE `security_user_passwords` DROP FOREIGN KEY `secur_user_passw_fk_user_id`');
        $this->execute('DROP TABLE IF EXISTS `security_user_passwords`');
    }
}

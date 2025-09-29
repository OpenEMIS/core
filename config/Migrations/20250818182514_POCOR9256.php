<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9256 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // --- Backup: Create backup table if it doesn't exist ---
        if (!$this->hasTable('z_9256_security_functions')) {
            $this->execute('CREATE TABLE `z_9256_security_functions` LIKE `security_functions`');
            $this->execute('INSERT INTO `z_9256_security_functions` SELECT * FROM `security_functions`');
        }


        // --- Build the Update statement
        $this->execute(" UPDATE `security_functions`
            SET 
                `_view` = 'Credentials.index|Credentials.view',
                `_edit` = 'Credentials.edit',
                `_add` = 'Credentials.add',
                `_delete` = 'Credentials.remove'
            WHERE 
                `name` = 'Credentials'
                AND `controller` = 'Credentials'
                AND `module` = 'Administration'
        ");
    }

    public function down()
    {
        // --- Rollback: If the backup table exists, drop the current table and rename the backup ---

        if ($this->hasTable('z_9256_security_functions')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `security_functions`');
            $this->execute('RENAME TABLE `z_9256_security_functions` TO `security_functions`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}

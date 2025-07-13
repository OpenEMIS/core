<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8875 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void
    {
        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_8875_labels` LIKE `labels`');
        $this->execute('INSERT INTO `z_8875_labels` SELECT * FROM `labels`');

        //Insert Data into labels tables
        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, code, name, visible, created_user_id, created) VALUES (uuid(), 'OutcomeCriterias', 'code', 'Outcome -> Criterias', 'Code', NULL, NULL, 1, 1, NOW())");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `labels`');
        $this->execute('RENAME TABLE `z_8875_labels` TO `labels`');
    }
}

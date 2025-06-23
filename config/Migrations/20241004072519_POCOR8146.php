<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8146 extends AbstractMigration
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
        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_8146_labels` LIKE `labels`');
        $this->execute('INSERT INTO `z_8146_labels` SELECT * FROM `labels`');
        // End

        //Insert Data into labels tables
        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'Institution Assessments', 'total_mark', 'Institutions > Performance > Assessments', 'Total Mark', 1, 1, NOW())");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `labels`');
        $this->execute('RENAME TABLE `z_8146_labels` TO `labels`');
    }
}

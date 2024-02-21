<?php
use Migrations\AbstractMigration;

class POCOR8056 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_8056_labels` LIKE `labels`');
        $this->execute('INSERT INTO `z_8056_labels` SELECT * FROM `labels`');
        // End

        //Insert Data into labels tables
        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'Curriculars', 'institution_curriculars', 'Institutions > Curriculars', 'Institution Curriculars', 1, 1, NOW())");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `labels`');
        $this->execute('RENAME TABLE `z_8056_labels` TO `labels`');
    }
}

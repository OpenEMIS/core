<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8646 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_8646_labels` LIKE `labels`');
        $this->execute('INSERT INTO `z_8646_labels` SELECT * FROM `labels`');
        // End
        //Insert Data into labels tables
        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionStaffAdd', 'openemis_no', 'Institutions > Staff > Add', 'OpenEMIS ID', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
            values (uuid(), 'InstitutionStudentAdd', 'openemis_no', 'Institutions > Students > Add', 'OpenEMIS ID', 1, 1, NOW())");
        }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `labels`');
        $this->execute('RENAME TABLE `z_8646_labels` TO `labels`');
    }
}

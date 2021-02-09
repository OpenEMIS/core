<?php
use Migrations\AbstractMigration;

class POCOR5913 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_5913_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_5913_import_mapping` SELECT * FROM `import_mapping`');

        // End
        $this->execute("UPDATE `import_mapping` SET `description` = 'Guardian National ID', `lookup_column` = 'identity_number' WHERE `model` = 'Student.StudentGuardians' AND `column_name` = 'guardian_id'");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_5913_import_mapping` TO `import_mapping`');
    }
}

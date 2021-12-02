<?php
use Migrations\AbstractMigration;

class POCOR5808 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_5808_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_5808_import_mapping` SELECT * FROM `import_mapping`');

        $this->execute("UPDATE `import_mapping` SET `description` = NULL WHERE `model` = 'Student.Extracurriculars' AND `column_name` = 'openemis_no' ");
    }
     // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_5808_import_mapping` TO `import_mapping`');
    }
}

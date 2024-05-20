<?php
use Migrations\AbstractMigration;

class POCOR7684 extends AbstractMigration
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
        //Backup import_mapping table
        $this->execute('CREATE TABLE `zz_7684_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_7684_import_mapping` SELECT * FROM `import_mapping`');
        //Update import_mapping
        $this->execute("UPDATE `import_mapping` SET `lookup_model` = 'ShiftOptions' WHERE `import_mapping`.`column_name` = 'shift_id';");
        
    }
    public function down()
    {
        // Field Options
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_7684_import_mapping` TO `import_mapping`');
    }
}
